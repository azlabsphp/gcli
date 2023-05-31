<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\GCli\Extensions\Providers;

use Drewlabs\GCli\Extensions\Console\Commands\MakeClassCommand;
use Drewlabs\GCli\Extensions\Console\Commands\MakeControllerCommand;
use Drewlabs\GCli\Extensions\Console\Commands\MakeDTOClassCommand;
use Drewlabs\GCli\Extensions\Console\Commands\MakeModelCommand;
use Drewlabs\GCli\Extensions\Console\Commands\MakeProjectComponentsCommand;
use Drewlabs\GCli\Extensions\Console\Commands\MakeServiceCommand;
use Drewlabs\GCli\Extensions\Console\Commands\MakeViewModelCommand;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;

class ServiceProvider extends SupportServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeProjectComponentsCommand::class,
                MakeClassCommand::class,
                MakeControllerCommand::class,
                MakeDTOClassCommand::class,
                MakeModelCommand::class,
                MakeServiceCommand::class,
                MakeViewModelCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
