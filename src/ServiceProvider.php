<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

namespace ProVision\Laravel\Searchable;

use Illuminate\Support\ServiceProvider as ServiceProviderAlias;
use ProVision\Laravel\Searchable\Commands\IndexCommand;
use ProVision\Laravel\Searchable\Commands\UnIndexCommand;

class ServiceProvider extends ServiceProviderAlias
{
    protected $commands = [
        IndexCommand::class,
        UnIndexCommand::class,
    ];

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/searchable.php',
            'searchable'
        );

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../config/searchable.php' => config_path('searchable.php'),
                ],
                'searchable'
            );

            $this->publishes(
                [
                    __DIR__ . '/../database/migrations' => database_path('migrations'),
                ],
                'searchable'
            );

            $this->commands($this->commands);
        }
    }
}
