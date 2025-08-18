<?php
namespace Abigah\BotCop\Middleware;

use Closure;
use Illuminate\Http\Request;

class BotCopMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $ip = $request->getClientIp();

        if ($response->status() === 404) {

            // Check if the IP is whitelisted, if it is, return the expected response.
            foreach (config('bot-cop.whitelist-ips', []) as $whitelistedIp) {
                if ($this->isIpInRange($ip, $whitelistedIp)) {
                    return $response;
                }
            }

            // Check if the request path is in the blacklist paths
            foreach (config('bot-cop.blacklist-paths', []) as $blacklistedPath) {
                if (str_contains($request->path(), $blacklistedPath)) {

                    foreach (config('bot-cop.enabled', []) as $service) {
                        app(config("bot-cop.services." . $service . ".service"))->addIp($ip, $request->path());
                    }

                    // Return a 403 on a blacklist match, regardless of services enabled.
                    return response('Forbidden', 403);
                }
            }
        }
        return $response;
    }

    private function isIpInRange($ip, $range)
    {
        if (strpos($range, '/') !== false) {
            list($range, $netmask) = explode('/', $range);
            $ip = ip2long($ip);
            $range = ip2long($range);
            $netmask = ~((1 << (32 - $netmask)) - 1);
            return ($ip & $netmask) === ($range & $netmask);
        }
        return $ip === ip2long($range);
    }
}
