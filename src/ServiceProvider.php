<?php

namespace Abigah\BotCop;

use Statamic\Statamic;
use Abigah\BotCop\Services\ForgeService;
use Abigah\BotCop\Services\LoggingService;
use Statamic\Providers\AddonServiceProvider;
use Abigah\BotCop\Services\CloudflareService;
use Abigah\BotCop\Middleware\BotCopMiddleware;

class ServiceProvider extends AddonServiceProvider
{
    protected $middlewareGroups = [
        'web' => [
            BotCopMiddleware::class
        ],
    ];

    protected $publishAfterInstall = false;

    public function bootAddon()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bot-cop.php', 'bot-cop');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bot-cop.php' => config_path('bot-cop.php'),
            ], 'bot-cop');
        }

        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish', ['--tag' => 'bot-cop']);
        });

        $this->app->make('config')->set('logging.channels.bot-cop', [
            'driver' => 'daily',
            'path' => storage_path('logs/'.config('bot-cop.services.logging.log-name', 'bot-cop').'.log'),
            'level' => 'debug',
            'days' => config('bot-cop.services.logging.delete-log-after', 7),
        ]);

        $this->app->bind(LoggingService::class, function ($app) {
            return new LoggingService(
                $app->make('config')->get('bot-cop.services.logging.log_name'),
                $app->make('config')->get('bot-cop.services.logging.delete_log_after')
            );
        });

        $this->app->bind(ForgeService::class, function ($app) {
            return new ForgeService(
                $app->make('config')->get('bot-cop.services.forge.api_token'),
                $app->make('config')->get('bot-cop.services.forge.server_id'),
                $app->make('config')->get('bot-cop.services.forge.rule_name'),
                $app->make('config')->get('bot-cop.services.forge.remove_after')
            );
        });

        $this->app->bind(CloudflareService::class, function ($app) {
            return new CloudflareService(
                $app->make('config')->get('bot-cop.services.cloudflare.api_token'),
                $app->make('config')->get('bot-cop.services.cloudflare.account_id'),
                $app->make('config')->get('bot-cop.services.cloudflare.list_id'),
                $app->make('config')->get('bot-cop.services.cloudflare.rule_name'),
                $app->make('config')->get('bot-cop.services.cloudflare.remove_after')
            );
        });
    }

    protected function schedule($schedule)
    {
        $schedule->command('statamic:addons:abigah:bot-cop:remove-ips')->everyMinute(1)->withoutOverlapping();
    }
}
