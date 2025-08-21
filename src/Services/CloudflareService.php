<?php

namespace Abigah\BotCop\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Abigah\BotCop\Services\ServiceContract;

class CloudflareService implements ServiceContract
{
    public function __construct(
        protected string $apiToken,
        protected string $accountId,
        protected string $listId,
        protected string $ruleName,
        protected int $removeAfter,
    ) { }

    /**
     * Add an IP address to the Cloudflare IP list.
     *
     * @param string $ip
     * @param string $path
     * @return \Illuminate\Http\Response
     */

    public function addIp(string $ip, string $host, string $path) {
        // Add the IP address to the Cloudflare IP list.

        if ($this->apiToken && $this->listId) {
            $response = Http::withToken($this->apiToken)
                ->post("https://api.cloudflare.com/client/v4/accounts/" . $this->accountId . "/rules/lists/" . $this->listId . "/items", [
                    [
                        "ip" => $ip,
                        "comment" => $this->ruleName . " - " . $host,
                    ],
            ]);

            if ($response->successful()) {
                Log::channel('bot-cop')->info('Added IP ' . $ip  . ' to Cloudflare IP list.');
                return response('Forbidden', 403);
            }
            if ($response->failed()) {
                Log::channel('bot-cop')->error('Failed to add IP ' . $ip . ' to Cloudflare IP list.');
                Log::channel('bot-cop')->error($response->json());
                return response('Forbidden', 403);
            }
        }
    }

    /**
     * Remove IPs from the Cloudflare IP list.
     */

    public function removeIps(){
        if ($this->removeAfter && $this->listId) {
            $response = Http::withToken($this->apiToken)
                ->get("https://api.cloudflare.com/client/v4/accounts/" . $this->accountId . "/rules/lists/" . $this->listId . "/items");

            // Loop through the response and check if the IPs are older than the configured remove-after time
            $removeIps = [];
            foreach ($response->json()['result'] as $item) {
                $createdOn = \Carbon\Carbon::parse($item['created_on'])->copy()->shiftTimezone('UTC');
                $now = \Carbon\Carbon::now()->copy()->setTimezone('UTC');
                $diff = $createdOn->diffInMinutes($now);

                if((str_contains($item["comment"], $this->ruleName)) && ($diff >= $this->removeAfter)) {
                    // Add IP to the remove Ips array
                    $removeIps[] = ["id" => $item['id']];
                }
            }

            // Remove the IP from the Cloudflare list
            if (!empty($removeIps)) {
                $deleteResponse = Http::withToken($this->apiToken)
                    ->delete("https://api.cloudflare.com/client/v4/accounts/" . $this->accountId . "/rules/lists/" . $this->listId . "/items", [
                        "items" => $removeIps
                    ]);

                if ($deleteResponse->successful()) {
                    Log::channel('bot-cop')->info('Removed IPs from Cloudflare list.');
                }
                if ($deleteResponse->failed()) {
                    Log::channel('bot-cop')->error('Failed to remove IPs from Cloudflare list.');
                    Log::channel('bot-cop')->error($deleteResponse->json());
                }
            }
        }
    }
}
