<?php

namespace Abigah\BotCop;

use Statamic\Statamic;
use Statamic\Providers\AddonServiceProvider;
use Abigah\BotCop\Middleware\BotCopMiddleware;

class ServiceProvider extends AddonServiceProvider
{
    protected $middlewareGroups = [
        'web' => [
            BotCopMiddleware::class
        ],
    ];
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
            'path' => storage_path('logs/'.config('bot-cop.log-name', 'bot-cop').'.log'),
            'level' => 'debug',
            'days' => config('bot-cop.delete-after', 7),
        ]);
    }

    protected function schedule($schedule)
    {
        $schedule->command('statamic:addons:abigah:bot-cop:remove-banned-ip')->everyMinute(1)->withoutOverlapping();
    }
}
