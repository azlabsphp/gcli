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

namespace Drewlabs\GCli\Extensions\Console\Commands;

use Drewlabs\GCli\Builders\ORMModelBuilder;
use Drewlabs\GCli\Helpers\ComponentBuilder;

use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;

use Illuminate\Console\Command;

use Illuminate\Container\Container;

/**
 * @property \Illuminate\Contracts\Foundation\Application app
 */
class MakeViewModelCommand extends Command
{
    /** @var string */
    protected $signature = 'gcli:make:viewmodel '
        .'{name=TestViewModel : View model class name }'
        .'{--namespace= : View model namespace}'
        .'{--path= : Project source code path}'
        .'{--model= : Model attached to the view model class }'
        .'{--single : Makes the view model single action validator}'
        .'{--rules=* : List of default rules to apply to the view model }'
        .'{--updateRules=* : List of rules to apply on update actions }'
        .'{--http : Whether to create an HTTP Viewmodel or a simple Viewmodel }';

    /** @var string */
    protected $description = 'Creates a Drewlabs package MVC controller';

    public function __construct()
    {
        $this->app = ($this->getLaravel() ?? Container::getInstance());
        parent::__construct();
    }

    public function handle()
    {
        // Parameters initialization
        $name = $this->argument('name') ?? null;
        $model = $this->option('model') ? ORMModelBuilder::defaultClassPath($this->option('model')) : null;
        $namespace = $this->option('namespace') ?? (bool) ($this->option('http')) ? '\\App\\Http\\ViewModels' : '\\App\\ViewModels';
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        $rules = $this->option('rules') ?? [];
        $updateRules = $this->option('updateRules') ?? [];
        // # End of parameters initialization
        ComponentsScriptWriter($basePath)->write(
            ComponentBuilder::buildViewModelDefinition(
                $this->option('single') ?? false,
                $rules,
                $updateRules,
                $name,
                $namespace,
                $model,
                $basePath,
                (bool) ($this->option('http')) ?: false
            )
        );
        $this->info("View Model class successfully generated\n");
    }
}
