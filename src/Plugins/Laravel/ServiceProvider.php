<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\GCli\Plugins\Laravel;

use Drewlabs\GCli\Plugins\Laravel\Console\Commands\MakeClassCommand;
use Drewlabs\GCli\Plugins\Laravel\Console\Commands\MakeControllerCommand;
use Drewlabs\GCli\Plugins\Laravel\Console\Commands\MakeDTOClassCommand;
use Drewlabs\GCli\Plugins\Laravel\Console\Commands\MakeModelCommand;
use Drewlabs\GCli\Plugins\Laravel\Console\Commands\MakeProjectComponentsCommand;
use Drewlabs\GCli\Plugins\Laravel\Console\Commands\MakeServiceCommand;
use Drewlabs\GCli\Plugins\Laravel\Console\Commands\MakeTsModuleCommand;
use Drewlabs\GCli\Plugins\Laravel\Console\Commands\MakeViewModelCommand;
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
                MakeTsModuleCommand::class,
            ]);
        }
    }
}
