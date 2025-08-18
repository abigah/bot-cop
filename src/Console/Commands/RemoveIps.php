<?php

namespace Abigah\botcop\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;

class RemoveIPs extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:addons:abigah:bot-cop:remove-ips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove Temporary IPs from Bot Cop';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach (config('bot-cop.enabled', []) as $service) {
            app(config("bot-cop.services." . $service . ".service"))->removeIps();
        }
    }
}
