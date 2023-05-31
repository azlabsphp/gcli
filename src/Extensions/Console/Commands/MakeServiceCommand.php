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

namespace Drewlabs\GCli\Extensions\Console\Commands;

use Drewlabs\GCli\Builders\EloquentORMModelBuilder;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;

use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;

use Illuminate\Console\Command;
use Illuminate\Container\Container;

use Illuminate\Contracts\Foundation\Application;

class MakeServiceCommand extends Command
{
    protected $signature = 'gcli:make:service '
        .'{name=TestService : Service name }'
        .'{--namespace= : Controller namespace}'
        .'{--path= : Project source code path}'
        .'{--model= : Model attached to the controller generated code}'
        .'{--asCRUD : Generate a CRUD Service }';

    protected $description = 'Creates a Drewlabs package MVC controller';
    /**
     * @var Application
     */
    private $app;

    public function __construct()
    {
        $this->app = ($this->getLaravel() ?? Container::getInstance());
        parent::__construct();
    }

    public function handle()
    {

        // Parameters initialization
        $name = $this->argument('name') ?? null;
        $model = $this->option('model') ?
            EloquentORMModelBuilder::defaultClassPath($this->option('model')) :
            null;
        $namespace = $this->option('namespace') ?? '\\App\\Services';
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        // # End of parameters initialization
        ComponentsScriptWriter($basePath)->write(
            ComponentBuilderHelpers::buildServiceDefinition(
                $this->option('asCRUD'),
                $name,
                $namespace,
                $model
            )
        );
        $this->info("Service class successfully generated\n");
    }
}
