<?php

namespace Drewlabs\ComponentGenerators\Extensions\Providers;

use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeClassCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeControllerCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeDTOClassCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeModelCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeServiceCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\MakeViewModelCommand;
use Drewlabs\ComponentGenerators\Extensions\Console\Commands\ReverseEngineerMVCComponents;
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
                ReverseEngineerMVCComponents::class,
                MakeClassCommand::class,
                MakeControllerCommand::class,
                MakeDTOClassCommand::class,
                MakeModelCommand::class,
                MakeServiceCommand::class,
                MakeViewModelCommand::class
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
        //
    }
}
