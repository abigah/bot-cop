<?php
namespace Abigah\BotCop\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BotCopMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $ip = $request->getClientIp();

        if ($response->status() === 404) {

            // Check if the current IP is in the whitelist
            foreach (config('bot-cop.allowed-ips', []) as $allowedIp) {
                    if (str_contains($allowedIp, '/')) {
                        // Handle CIDR notation (e.g., 192.168.1.0/24 or 2001:db8::/32)
                        if (filter_var($ip, FILTER_VALIDATE_IP) && $this->ipInCidr($ip, $allowedIp)) {
                            if($request->header('X-Forwarded-For')){
                                $ip = $request->header('X-Forwarded-For') ?: $request->ip();
                            } else {
                                return $response;
                            }
                            
                        }
                    } else {
                        // Handle single IP addresses
                        if ($ip === $allowedIp) {
                            if($request->header('X-Forwarded-For')){
                                $ip = $request->header('X-Forwarded-For') ?: $request->ip();
                            } else{
                                return $response;
                            }
                        }
                    }
            }

            // Check if the request path is in the blocked paths
            foreach (config('bot-cop.blocked-paths', []) as $blockedPath) {
                if (str_contains($request->path(), $blockedPath)) {

                    foreach (config('bot-cop.enabled', []) as $service) {
                        app(config("bot-cop.services." . $service . ".service"))->addIp($ip, $request->host(), $request->path());
                    }

                    // Return a 403 on a blocked path match, regardless of services enabled.
                    return response('Forbidden', 403);
                }
            }
            Log::channel('bot-cop')->info('LoggingService: 404 for IP: ' . $ip . ' URL: ' . $request->host() . '/' . $request->path());
        }
        return $response;
    }

    /**
     * Checks if an IP address is within a given CIDR range.
     */
    protected function ipInCidr($ip, $cidr)
    {
        // Split CIDR into IP and mask
        list($subnet, $mask) = explode('/', $cidr);

        // Convert IPs to binary form
        $ipBinary = inet_pton($ip);
        $subnetBinary = inet_pton($subnet);

        // Check if both are valid IP addresses
        if ($ipBinary === false || $subnetBinary === false) {
            return false;
        }

        // Determine IP version and byte length
        $ipVersion = str_contains($ip, ':') ? 6 : 4;
        $byteLength = ($ipVersion === 4) ? 4 : 16;

        // Create the mask
        $maskBinary = str_repeat(chr(255), floor($mask / 8)) . chr(255 << (8 - ($mask % 8))) . str_repeat(chr(0), $byteLength - ceil($mask / 8));

        // Compare
        return ($ipBinary & $maskBinary) === ($subnetBinary & $maskBinary);
    }
}
