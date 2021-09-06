<?php

namespace Drewlabs\ComponentGenerators\Extensions\Providers;

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
                ReverseEngineerMVCComponents::class
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
