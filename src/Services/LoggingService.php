<?php

namespace Abigah\BotCop\Services;

use Illuminate\Support\Facades\Log;
use Abigah\BotCop\Services\ServiceContract;

class LoggingService implements ServiceContract
{
    public function __construct(
        protected string $logName,
        protected int $deleteLogAfter
    ) { }

    public function addIp(string $ip, string $path) {
        Log::alert('BotCop is banning IP: ' . $ip . ' for path: ' . $path);
        Log::channel('bot-cop')->info('LoggingService: Bad Bot IP Added: ' . $ip);
    }

    public function removeIps() {
        Log::channel('bot-cop')->info('LoggingService: Bad Bot IPs Removed');
    }
}
