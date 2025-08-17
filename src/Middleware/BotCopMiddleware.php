<?php
namespace Abigah\BotCop\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class BotCopMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (config('bot-cop.log_enabled', false) && $response->status() === 404) {

            // Check if the IP is whitelisted
            $whitelistIps = config('bot-cop.whitelist-ips', []);
            if (in_array($request->ip(), $whitelistIps)) {
                return $response;
            }

            // Check if the request path is in the blacklist paths
            $blacklistPaths = config('bot-cop.blacklist-paths', []);
            foreach ($blacklistPaths as $path) {
                if (str_contains($request->path(), $path)) {

                    // Add the IP address to the Cloudflare IP list.
                    if (config('bot-cop.cloudflare_ip_list_enabled', true)) {
                        ray('Adding IP to Cloudflare IP list: ' . $request->getClientIp());
                        if (config('bot-cop.cloudflare-api-token') && config('bot-cop.cloudflare-list-id')) {
                            $response = Http::withToken( config('bot-cop.cloudflare-api-token'))
                            ->post("https://api.cloudflare.com/client/v4/accounts/" . env('BOT_COP_CLOUDFLARE_ACCOUNT_ID') . "/rules/lists/" . env('BOT_COP_CLOUDFLARE_LIST_ID') . "/items",[
                                [
                                    "ip" => $request->getClientIp(),
                                    "comment" => "Blocked by BotCop"
                                ],
                            ]);

                            if ($response->successful()) {
                                ray($response->json());
                                Log::channel('bot-cop')->info('Added IP ' . $request->getClientIp() . ' to Cloudflare IP list.');
                                return response('Forbidden', 403);
                            }
                            if ($response->failed()) {
                                Log::channel('bot-cop')->error('Failed to add IP ' . $request->getClientIp() . ' to Cloudflare IP list.');
                                Log::channel('bot-cop')->error($response->json());
                                return response('Forbidden', 403);
                            }
                        }
                    }

                    // Ban the IP address using the Forge API
                    if (config('bot-cop.forge_block_enabled', true)) {
                        if (config('bot-cop.forge-api-token') && config('bot-cop.forge-server-id')) {
                            $response = Http::withHeaders([
                                                'Accept' => 'application/json',
                                                'Content-Type' => 'application/json',
                                            ])
                                            ->withToken( config('bot-cop.forge-api-token') )
                                            ->post("https://forge.laravel.com/api/v1/servers/" . config('bot-cop.forge-server-id') . "/firewall-rules", [
                                                'name' => config('bot-cop.firewall-rule-name'),
                                                'ip_address' => $request->getClientIp(),
                                                'port' => '80:443',
                                                'type' => 'deny',
                                            ]);

                            if ($response->successful()) {
                                ray($response->json());
                                Log::channel('bot-cop')->info('Ban IP ' . $request->getClientIp() . ' to Forge Firewall.');
                                return response('Forbidden', 403);
                            }
                            if ($response->failed()) {
                                ray($response->json());
                                Log::channel('bot-cop')->error('Failed to ban IP ' . $request->getClientIp() . ' to Forge Firewall.');
                                Log::channel('bot-cop')->error($response->json());
                                return response('Forbidden', 403);
                            }
                        }
                    }
                }
            }
        }
        return $response;
    }
}
