<?php

namespace Abigah\BotCop\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Abigah\BotCop\Services\ServiceContract;

class ForgeService implements ServiceContract
{
    public function __construct(
        protected string $apiToken,
        protected string $serverId,
        protected string $ruleName,
        protected int $removeAfter,
    ) { }

    /**
     * Add an IP address to the Forge firewall.
     *
     * @param string $ip
     * @param string $path
     * @return \Illuminate\Http\Response
     */

    public function addIp(string $ip, string $host, string $path) {

        if ($this->apiToken && $this->serverId) {
            $response = Http::withHeaders([
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                            ])
                            ->withToken( $this->apiToken )
                            ->post("https://forge.laravel.com/api/v1/servers/" . $this->serverId . "/firewall-rules", [
                                'name' => $this->ruleName . " - " . $host,
                                'ip_address' => $ip,
                                'port' => '80:443',
                                'type' => 'deny',
                            ]);

            if ($response->successful()) {
                Log::channel('bot-cop')->info('Added IP ' . $ip . ' to Forge Firewall.');
                return response('Forbidden', 403);
            }
            if ($response->failed()) {
                Log::channel('bot-cop')->error('Failed to ban IP ' . $ip . ' to Forge Firewall.');
                Log::channel('bot-cop')->error($response->json());
                return response('Forbidden', 403);
            }
        }
    }

    public function removeIps(){
        if ($this->removeAfter && $this->serverId) {
            $response = Http::withHeaders([
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                            ])
                            ->withToken( $this->apiToken )
                            ->get("https://forge.laravel.com/api/v1/servers/" . $this->serverId . "/firewall-rules");

            Log::channel('bot-cop')->warning('Getting banned IPs from Forge service.');

            // Loop through the response and check if the IPs are older than the configured remove-after time
            foreach ($response->json()['rules'] as $rule) {

                $createdAt = \Carbon\Carbon::parse($rule['created_at'])->copy()->shiftTimezone('UTC');
                $now = \Carbon\Carbon::now()->copy()->setTimezone('UTC');
                $diff = $createdAt->diffInMinutes($now);

                // For debugging timezone
                if ((str_contains($rule["name"], $this->ruleName))) {
                    Log::channel('bot-cop')->info('IP: ' . $rule['ip_address'] . '. '
                        . ' - Created at: ' . $createdAt
                        . ' - Now: ' . $now
                        . ' - Diff: ' . $diff);
                }

                if ((str_contains($rule["name"], $this->ruleName)) && ($diff >= $this->removeAfter)) {
                    // Remove the IP from Forge
                    $deleteResponse = Http::withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->withToken($this->apiToken)
                    ->delete("https://forge.laravel.com/api/v1/servers/" . $this->serverId . "/firewall-rules/" . $rule['id']);

                    if ($deleteResponse->successful()) {
                        Log::channel('bot-cop')->info('Removed IP ' . $rule['ip_address'] . ' from Forge Firewall.');
                    }
                    if ($deleteResponse->failed()) {
                        Log::channel('bot-cop')->error('Failed to remove IP ' . $rule['ip_address'] . ' from Forge Firewall.');
                        Log::channel('bot-cop')->error($deleteResponse->json());
                    }
                }
            }
        }
    }
}
