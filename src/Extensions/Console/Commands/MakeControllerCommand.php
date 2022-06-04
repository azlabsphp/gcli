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

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Closure;
use Drewlabs\CodeGenerator\Exceptions\PHPVariableException;
use Drewlabs\ComponentGenerators\Builders\DtoAttributesFactory;
use Drewlabs\ComponentGenerators\Builders\EloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\Builders\ViewModelRulesFactory;
use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\Extensions\Console\ComponentCommandsHelpers;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\MVCControllerBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Pluralizer;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

class MakeControllerCommand extends Command
{
    protected $signature = 'drewlabs:mvc:make:controller '
        . '{name=TestController : Controller name}'
        . '{--namespace= : Controller namespace}'
        . '{--path= : Project source code path}'
        . '{--model= : Model attached to the controller generated code}'
        . '{--invokable : Creates an invokable controller }'
        . '{--service= : Service class to bind to the controller definition}'
        . '{--viewModel= : View model class to bind to the controller definition}'
        . '{--dtoClass= : Data transfert object to bind to the controller definition}';

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
        $name = $this->option('name') ?? null;
        $model = $this->option('model') ? EloquentORMModelBuilder::defaultClassPath($this->option('model')) : null;
        $namespace = $this->option('namespace') ?? '\\App\\Http\\Controllers';
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
            static::createComponents($namespace, $name, $basePath, function (string $value) {
                return Pluralizer::plural($value);
            }, $model, $service, $dto, $viewModel);
        }
        $this->info("Controller successfully generated \n");
    }

    /**
     * 
     * @param mixed $namespace 
     * @param mixed $name 
     * @param mixed $basePath 
     * @param Closure $pluralizer 
     * @param mixed $model 
     * @param mixed $service 
     * @param mixed $dto 
     * @param mixed $viewModel 
     * @return void 
     * @throws UnableToRetrieveMetadataException 
     * @throws PHPVariableException 
     */
    public static function createComponents($namespace, $name, $basePath, \Closure $pluralizer, $model = null, $service = null, $dto = null, $viewModel = null)
    {
        $modelClassPath = $model;
        $modelComponent = null;
        if (null !== $model && !class_exists($model) && !class_exists(EloquentORMModelBuilder::defaultClassPath($model))) {
            $model_name = Str::contains($model, '\\') ? Str::afterLast('\\', $model) : $model;
            $model_namespace = sprintf("\\%s\\Models", ComponentCommandsHelpers::getBaseNamespace($namespace) ?? "App");
            $modelComponent = ComponentBuilderHelpers::createModelBuilder(
                $pluralizer($model_name),
                $columns ?? [],
                $model_namespace,
            );
            ComponentsScriptWriter($basePath)->write($modelComponent->build());
            $modelClassPath = sprintf("%s\\%s", $model_namespace, $model_name);
            $service = $service ?? sprintf("%sService", $model_name);
            $dto = $dto ?? sprintf("%sDto", $model_name);
            $viewModel = $viewModel ?? sprintf("%sViewModel", $model_name);
        }
        $definition = $modelComponent->getDefinition();
        $serviceClass = ComponentCommandsHelpers::createService($namespace, $basePath, $modelClassPath, $service);
        $dtoClass = ComponentCommandsHelpers::createDto($namespace, $basePath, $modelClassPath, $dto, $definition instanceof DtoAttributesFactory ? $definition->createDtoAttributes() : []);
        $viewModelClass = ComponentCommandsHelpers::createViewModel($namespace, $basePath, $modelClassPath, $viewModel, $definition instanceof ViewModelRulesFactory ? $definition->createRules() : [], $definition instanceof ViewModelRulesFactory ? $definition->createRules(true) : []);
        ComponentsScriptWriter($basePath)->write(
            ComponentBuilderHelpers::buildController(
                $modelClassPath,
                $serviceClass,
                $viewModelClass,
                $dtoClass,
                $name,
                $namespace,
            )
        );
    }
}
