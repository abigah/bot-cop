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

    public function addIp(string $ip, string $host, string $path) {
        Log::channel('bot-cop')->alert('BotCop is banning IP: ' . $ip . ' for url: ' . $host . '/' . $path);
    }

    public function removeIps() {
        Log::channel('bot-cop')->info('LoggingService: Bad Bot IP Removing');
    }
}
