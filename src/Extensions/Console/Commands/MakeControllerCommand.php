<?php

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Drewlabs\ComponentGenerators\Builders\EloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\MVCControllerBuilder;

class MakeControllerCommand extends Command
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

    protected $signature = 'drewlabs:mvc:make:controller '
        . '{--namespace= : Controller namespace}'
        . '{--path= : Project source code path}'
        . '{--name= : Controller name}'
        . '{--model= : Model attached to the controller generated code}'
        . '{--invokable : Creates an invokable controller }'
        . '{--service= : Service class to bind to the controller definition}'
        . '{--viewModel= : View model class to bind to the controller definition}'
        . '{--dtoClass= : Data transfert object to bind to the controller definition}';

    protected $description = 'Creates a Drewlabs package MVC controller';

    public function handle()
    {
        $name = $this->option('name') ?? null;
        $model = EloquentORMModelBuilder::defaultClassPath($this->option('model'));
        $namespace = $this->option('namespace') ?? "App\\Http\\Controllers";
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        $service = $this->option('service') ?? null;
        $viewModel = $this->option('viewModel') ?? null;
        $dto = $this->option('dtoClass') ?? null;
        // # End of parameters initialization
        if ($this->option('invokable')) {
            ComponentsScriptWriter($basePath)->write(
                MVCControllerBuilder($name, $namespace)
                    ->asInvokableController()->build()
            );
        } else {
            ComponentsScriptWriter($basePath)->write(
                ComponentBuilderHelpers::buildController(
                    $model,
                    $service,
                    $viewModel,
                    $dto,
                    $name,
                    $namespace,
                )
            );
        }
        $this->info("Controller successfully generated \n");
    }
}
