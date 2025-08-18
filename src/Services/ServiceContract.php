<?php
namespace Abigah\BotCop\Services;

interface ServiceContract
{
    public function addIp(string $ip, string $path);
    public function removeIps();
}
