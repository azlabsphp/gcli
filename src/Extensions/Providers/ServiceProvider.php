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

namespace Drewlabs\ComponentGenerators\Extensions\Providers;

use Drewlabs\ComponentGenerators\Extensions\Console\Commands\CreateMVCComponentsCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeClassCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeControllerCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeDTOClassCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeModelCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeServiceCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeViewModelCommand;
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
                CreateMVCComponentsCommand::class,
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
