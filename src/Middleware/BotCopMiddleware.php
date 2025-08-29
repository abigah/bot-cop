<?php
namespace Abigah\BotCop\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class BotCopMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->getClientIp();

        // Check if the current IP is in the allowed_ips configuration
        // Or in the extended_allowed_ips configuration
        $allowedIPs = array_filter(array_merge(config('bot-cop.allowed-ips', []), config('bot-cop.extended_allowed_ips', [])));
        foreach ($allowedIPs as $allowedIp) {
            if (str_contains($allowedIp, '/')) {

                // Handle CIDR notation (e.g., 192.168.1.0/24 or 2001:db8::/32)
                if (filter_var($ip, FILTER_VALIDATE_IP) && $this->ipInCidr($ip, $allowedIp)) {

                    // Treat allowed IPs as trusted but anyone using X-Forwarded-For goes on.
                    if($request->header('X-Forwarded-For')){
                        $ip = $request->header('X-Forwarded-For') ?: $request->ip();
                    } else {
                        return $next($request);
                    }

                }
            } else {
                // Handle single IP addresses
                if ($ip === $allowedIp) {
                    // Treat allowed IPs as trusted but anyone using X-Forwarded-For goes on.
                    if($request->header('X-Forwarded-For')){
                        $ip = $request->header('X-Forwarded-For') ?: $request->ip();
                    } else {
                        return $next($request);
                    }
                }
            }
        }

        // if rate_limit_toggle is true
        if (config('bot-cop.rate_limit_toggle', true)) {

            $allowedPaths = array_filter(array_merge(config('bot-cop.rate_limit_allowed_paths', []), config('bot-cop.rate_limit_extended_allowed_paths', [])));
            // If the request-path() is not in the rate limit allowed_paths
            if (!in_array($request->path(), $allowedPaths)) {
                if (RateLimiter::tooManyAttempts('page-hit:'.$ip, config('bot-cop.hits_per_minute', 20))) {
                    return $this->rate_limit_block_log($ip, $request->url(), $request->path(), 'Speeding, wait 1 minute and try again.');
                }

                RateLimiter::increment('page-hit:'.$ip);
            }
        }

        $response = $next($request);

        if ($response->status() === 404) {
            $blockedPaths = array_filter(array_merge(config('bot-cop.blocked-paths', []), config('bot-cop.extended_blocked_paths', [])));
            // Check if the request path is in the blocked paths
            foreach ($blockedPaths as $blockedPath) {
                if (str_contains($request->path(), $blockedPath)) {
                    RateLimiter::increment('page-hit:'.$ip);
                    // When trespassing, only allow 1 hit
                    if (RateLimiter::tooManyAttempts('page-hit:'.$ip,1)) {
                        foreach (config('bot-cop.enabled', []) as $service) {
                            app(config("bot-cop.services." . $service . ".service"))->addIp($ip, $request->host(), $request->path());
                        }
                        return $this->rate_limit_block_log($ip, $request->url(), $request->path(), 'Trespassing, you have been blocked by our firewall for approximately 90 minutes.');
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
     * Log the reason someone has been rate limited and display.
     */
    protected function rate_limit_block_log($ip, $url, $path, $type){
        Log::channel('bot-cop')->info('LoggingService: ' . $type . ' 429 for IP: ' . $ip . ' URL: ' . $url . '/' . $path);
        return response()->make($type, '429')->withHeaders([
            'Retry-After' => 60,
        ]);
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
