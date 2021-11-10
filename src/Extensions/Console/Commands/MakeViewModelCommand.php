<?php

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Drewlabs\ComponentGenerators\Builders\EloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;

class MakeViewModelCommand extends Command
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

    protected $signature = 'drewlabs:mvc:make:viewmodel '
        . '{--namespace= : View model namespace}'
        . '{--path= : Project source code path}'
        . '{--name= : Generated view model name}'
        . '{--columns=* : List of model table fillable columns }'
        . '{--model= : Model attached to the view model class }'
        . '{--single : Makes the view model single action validator}'
        . '{--rules=* List of default rules to apply to the view model }'
        . '{--updateRules=* List of rules to apply on update actions }';

    protected $description = 'Creates a Drewlabs package MVC controller';

    public function handle()
    {
        // Parameters initialization
        $name = $this->option('name') ?? null;
        $single = $this->option('single') ?? false;
        $model = EloquentORMModelBuilder::defaultClassPath($this->option('model'));
        $namespace = $this->option('namespace') ?? "\\App\\Http\\ViewModels";
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        $rules = $this->option('rules') ?? [];
        $updateRules = $this->option('updateRules') ?? [];
        // # End of parameters initialization
        ComponentsScriptWriter($basePath)->write(
            ComponentBuilderHelpers::buildViewModelDefinition(
                $single,
                $rules,
                $updateRules,
                $name,
                $namespace,
                $model
            )
        );
        $this->info("View Model class successfully generated\n");
    }
}
