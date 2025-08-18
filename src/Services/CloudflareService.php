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

    public function addIp(string $ip, string $path) {
        // Add the IP address to the Cloudflare IP list.

        if ($this->apiToken && $this->listId) {
            $response = Http::withToken($this->apiToken)
                ->post("https://api.cloudflare.com/client/v4/accounts/" . $this->accountId . "/rules/lists/" . $this->listId . "/items", [
                    [
                        "ip" => $ip,
                        "comment" => $this->ruleName
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
            foreach ($response->json()['result'] as $item) {
                if(($this->ruleName == $item["comment"]) && (\Carbon\Carbon::parse($item['created_on'])->diffInMinutes(now()) >= $this->removeAfter)) {
                    // Remove the IP from the Cloudflare list
                    $deleteResponse = Http::withToken($this->apiToken)
                        ->delete("https://api.cloudflare.com/client/v4/accounts/" . $this->accountId . "/rules/lists/" . $this->listId . "/items", [
                            "items" => [
                                [
                                    "id" => $item['id'],
                                ],
                            ],
                        ]);

                    if ($deleteResponse->successful()) {
                        Log::channel('bot-cop')->info('Removed IP ' . $item["ip"] . ' from Cloudflare list.');
                    }
                    if ($deleteResponse->failed()) {
                        Log::channel('bot-cop')->error('Failed to remove IP ' . $item["ip"] . ' from Cloudflare list.');
                        Log::channel('bot-cop')->error($deleteResponse->json());
                    }
                }
            }
        }
    }
}
