<?php

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Drewlabs\ComponentGenerators\Builders\EloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;

class MakeServiceCommand extends Command
{
    /**
     *
     * @var Application
     */
    private $app;

    public function __construct()
    {
        $this->app = ($this->getLaravel() ?? Container::getInstance());
        parent::__construct();
    }

    protected $signature = 'drewlabs:mvc:make:service '
        . '{--namespace= : Controller namespace}'
        . '{--path= : Project source code path}'
        . '{--name= : Controller name}'
        . '{--model= : Model attached to the controller generated code}'
        . '{--asCRUD : Generate a CRUD Service }';

    protected $description = 'Creates a Drewlabs package MVC controller';

    public function handle()
    {

        // Parameters initialization
        $name = $this->option('name') ?? null;
        $model = $this->option('model') ?
            EloquentORMModelBuilder::defaultClassPath($this->option('model')) :
            null;
        $namespace = $this->option('namespace') ?? "\\App\\Services";
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