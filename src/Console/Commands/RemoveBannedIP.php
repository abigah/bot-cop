<?php

namespace Abigah\botcop\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RemoveBannedIP extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:addons:abigah:bot-cop:remove-banned-ip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove banned IPs from Bot Cop';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Get a list of banned IPs from the Cloudflare list API and remove any that are older than the configured remove-after time
        if (config('bot-cop.remove-after') && config('bot-cop.cloudflare-list-id')) {

            Log::channel('bot-cop')->warning('Getting banned IPs from Cloudflare list.');

            $response = Http::withToken(config('bot-cop.cloudflare-api-token'))
                ->get("https://api.cloudflare.com/client/v4/accounts/" . env('BOT_COP_CLOUDFLARE_ACCOUNT_ID') . "/rules/lists/" . env('BOT_COP_CLOUDFLARE_LIST_ID') . "/items");
            Log::channel('bot-cop')->info($response->json());
            // Loop through the response and check if the IPs are older than the configured remove-after time
            foreach ($response->json()['result'] as $item) {
                if (\Carbon\Carbon::parse($item['created_on'])->diffInMinutes(now()) >= config('bot-cop.remove-after', 60)) {
                    // Remove the IP from the Cloudflare list
                    $deleteResponse = Http::withToken(config('bot-cop.cloudflare-api-token'))
                        ->delete("https://api.cloudflare.com/client/v4/accounts/" . env('BOT_COP_CLOUDFLARE_ACCOUNT_ID') . "/rules/lists/" . env('BOT_COP_CLOUDFLARE_LIST_ID') . "/items", [
                            "items" => [
                                [
                                    "id" => $item['id'],
                                ],
                            ],
                        ]);

                    if ($deleteResponse->successful()) {
                        Log::channel('bot-cop')->info('Removed IP ' . $item['id'] . ' from Cloudflare list.');
                    }
                    if ($deleteResponse->failed()) {
                        Log::channel('bot-cop')->error('Failed to remove IP ' . $item['id'] . ' from Cloudflare list.');
                        Log::channel('bot-cop')->error($deleteResponse->json());
                    }
                }
            }
        }


        // Get a list of banned IPs from the Forge API and remove any that are older than the configured remove-after time
        if (config('bot-cop.remove-after') && config('bot-cop.forge-server-id')) {
            $response = Http::withHeaders([
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                            ])
                            ->withToken( config('bot-cop.forge-api-token') )
                            ->get("https://forge.laravel.com/api/v1/servers/" . config('bot-cop.forge-server-id') . "/firewall-rules");

            Log::channel('bot-cop')->warning('Getting banned IPs from Forge service.');
            Log::channel('bot-cop')->info($response->json());
            // Loop through the response and check if the IPs are older than the configured remove-after time
            foreach ($response->json()['rules'] as $rule) {
                if ($rule['name'] === config('bot-cop.firewall-rule-name') && $rule['type'] === 'deny') {
                    if (\Carbon\Carbon::parse($rule['created_at'])->diffInMinutes(now()) >= config('bot-cop.remove-after', 60)) {
                        // Remove the IP from the Bot Cop service
                        $deleteResponse = Http::withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ])
                        ->withToken(config('bot-cop.forge-api-token'))
                        ->delete("https://forge.laravel.com/api/v1/servers/" . config('bot-cop.forge-server-id') . "/firewall-rules/" . $rule['id']);

                        if ($deleteResponse->successful()) {
                            Log::channel('bot-cop')->info('Removed IP ' . $rule['id'] . ' from Forge Firewall.');
                        }
                        if ($deleteResponse->failed()) {
                            Log::channel('bot-cop')->error('Failed to remove IP ' . $rule['id'] . ' from Forge Firewall.');
                            Log::channel('bot-cop')->error($deleteResponse->json());
                        }
                    }
                }
            }
        }
    }
}
