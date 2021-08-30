<?php

namespace Drewlabs\ComponentGenerators\Extensions\Command;

use Drewlabs\ComponentGenerators\Builders\EloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

class GenerateMVComponent extends Command
{

    /**
     * 
     * @var Application
     */
    private $app;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drewlabs:mvc:generate {--type= : The name or the type of component}'
        . '{--increments : Makes the model primary key incrementable}'
        . '{--namespace= : Component namespace}'
        . '{--primary-key= : Model primary key}'
        . '{--path= : Project source code path}'
        . '{--name= : Generated component name}'
        . '{--columns=* : List of model table fillable columns}'
        . '{--as-viewmodel : Generate the model a a view model class}'
        . '{--model= : For controllers, service and Dto object, this bind the component to the specified model}'
        . '{--single : Makes the view model single action validator}'
        . '{--rules=* List of default rules to apply to the model}'
        . '{--update-rules=* List of rules applied on update action calls}'
        . '{--json-attributes=* List of serialization class fillable properties}'
        . '{--hidden-attributes=* List of serialization class hidden properties}'
        . '{--guarded-attributes=* List of serialization class guarded properties}'
        . '{--service-class= : Model service class to bind to the controller definition}'
        . '{--view-model-class= : View model class to bind to the controller definition}'
        . '{--dto-class= : Data transfert object to bind to the controller definition}'; // 

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a drewlabs MVC controller class';

    public function __construct()
    {
        $this->app = ($this->getLaravel() ?? Container::getInstance());
        parent::__construct();
    }

    public function handle()
    {
        switch (strtolower($this->option('type') ?? '')) {
            case 'model':
                return $this->callModelGenerator();
            case 'service':
                return $this->callServiceGenerator();
            case 'view-model':
                return $this->callViewModelGeneratorClass();
            case 'model-value':
                return $this->callDtoBuilder();
            default:
                # code...
                break;
        }
    }

    private function callModelGenerator()
    {
        if (null === ($table = $this->option('table') ?? null)) {
            return $this->error('Model generator requires at least the table name');
        }
        // Parameters initialization
        $primaryKey = $this->option('primary-key') ?? 'id';
        $increments = $this->option('increments') ?? false;
        $namespace = $this->option('namespace') ?? "App\\Models";
        $columns = $this->option('columns') ?? [];
        $vm = $this->option('as-viewmodel') ?? false;
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        // # End of parameters initialization
        ComponentBuilderHelpers::buildModelDefinition($table, $basePath, $columns ?? [], $namespace, $primaryKey, $increments, $vm);
    }

    private function callServiceGenerator()
    {
        // Parameters initialization
        $name = $this->option('name') ?? null;
        $model = EloquentORMModelBuilder::defaultClassPath($this->option('model'));
        $namespace = $this->option('namespace') ?? null;
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        // # End of parameters initialization
        ComponentBuilderHelpers::buildServiceDefinition($basePath, $name, $namespace, $model);
    }

    public function callViewModelGeneratorClass()
    {
        // Parameters initialization
        $name = $this->option('name') ?? null;
        $single = $this->option('single') ?? false;
        $model = EloquentORMModelBuilder::defaultClassPath($this->option('model'));
        $namespace = $this->option('namespace') ?? null;
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        $rules = $this->option('rules') ?? [];
        $updateRules = $this->option('update-rules') ?? [];
        // # End of parameters initialization
        ComponentBuilderHelpers::buildViewModelDefinition($basePath, $single, $rules, $updateRules, $name, $namespace, $model);
    }

    public function callDtoBuilder()
    {
        // Parameters initialization
        $name = $this->option('name') ?? null;
        $model = EloquentORMModelBuilder::defaultClassPath($this->option('model'));
        $namespace = $this->option('namespace') ?? null;
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        $attributes = $this->option('json-attributes') ?? [];
        $hidden = $this->option('hidden-attributes') ?? []; //
        $guarded = $this->option('guarded-attributes') ?? [];
        // # End of parameters initialization
        ComponentBuilderHelpers::buildDtoObjectDefinition(
            $basePath,
            $attributes,
            $hidden,
            $guarded,
            $name,
            $namespace,
            $model
        );
    }

    public function callControllerBuilder()
    {
        // Parameters initialization
        $name = $this->option('name') ?? null;
        $model = EloquentORMModelBuilder::defaultClassPath($this->option('model'));
        $namespace = $this->option('namespace') ?? null;
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        $service = $this->option('service-class') ?? null;
        $viewModel = $this->option('view-model-class') ?? null; //
        $dto = $this->option('dto-class') ?? null;
        // # End of parameters initialization
        ComponentBuilderHelpers::buildController(
            $basePath,
            $model,
            $service,
            $viewModel,
            $dto,
            $name,
            $namespace,
        );
    }
}
