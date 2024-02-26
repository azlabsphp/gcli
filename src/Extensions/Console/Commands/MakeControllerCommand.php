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

use Drewlabs\CodeGenerator\Exceptions\PHPVariableException;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Builders\ORMModelBuilder;
use Drewlabs\GCli\Contracts\DtoAttributesFactory;
use Drewlabs\GCli\Contracts\ViewModelRulesFactory;
use Drewlabs\GCli\Extensions\Console\ComponentCommandsHelpers;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;

use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;

use function Drewlabs\GCli\Proxy\MVCControllerBuilder;

use Illuminate\Console\Command;
use Illuminate\Container\Container;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Pluralizer;

class MakeControllerCommand extends Command
{
    protected $signature = 'gcli:make:controller '
        . '{name=TestController : Controller name}'
        . '{--namespace= : Controller namespace}'
        . '{--path= : Project source code path}'
        . '{--model= : Model attached to the controller generated code}'
        . '{--invokable : Creates an invokable controller }'
        . '{--service= : Service class to bind to the controller definition}'
        . '{--viewModel= : View model class to bind to the controller definition}'
        . '{--dtoClass= : Data transfert object to bind to the controller definition}'
        . '{--authorizable : Add policy handlers to the controller class}'
        . '{--primaryKey=id : Set the value for the primary key used for the controller}';

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
        $name = $this->argument('name') ?? null;
        $model = $this->option('model') ? ORMModelBuilder::defaultClassPath($this->option('model')) : null;
        $namespace = $this->option('namespace') ?? '\\App\\Http\\Controllers';
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        $service = $this->option('service') ?? null;
        $viewModel = $this->option('viewModel') ?? null;
        $dto = $this->option('dtoClass') ?? null;
        $primaryKey = $this->option('primaryKey') ?? 'id';
        // # End of parameters initialization
        if ($this->option('invokable')) {
            ComponentsScriptWriter($basePath)->write(MVCControllerBuilder($name, $namespace)->asInvokableController()->build());
        } else {
            static::createComponents(
                $namespace,
                $name,
                $basePath,
                static function (string $value) {
                    return Pluralizer::plural($value);
                },
                $model,
                $service,
                $dto,
                $viewModel,
                $this->option('authorizable'),
                $primaryKey
            );
        }
        $this->info("Controller successfully generated \n");
    }

    /**
     * @param mixed $namespace
     * @param mixed $name
     * @param mixed $basePath
     * @param mixed $model
     * @param mixed $service
     * @param mixed $dto
     * @param mixed $viewModel
     *
     * @throws \Exception
     * @throws PHPVariableException
     *
     * @return void
     */
    public static function createComponents(
        $namespace,
        $name,
        $basePath,
        \Closure $pluralizer,
        $model = null,
        $service = null,
        $dto = null,
        $viewModel = null,
        bool $authorizable = false,
        string $primaryKey = 'id'
    ) {
        $modelClassPath = $model;
        $modelComponent = null;
        $modelName = Str::contains($modelClassPath, '\\') ? Str::afterLast('\\', $modelClassPath) : $modelClassPath;
        $modelNamespace = sprintf('\\%s\\Models', ComponentCommandsHelpers::getBaseNamespace($namespace) ?? 'App');
        if (null !== $model && !class_exists($model) && !class_exists(ORMModelBuilder::defaultClassPath($model))) {
            $modelComponent = ComponentBuilderHelpers::createModelBuilder(
                $pluralizer($modelName),
                $columns ?? [],
                $modelNamespace,
            );
            ComponentsScriptWriter($basePath)->write($modelComponent->build());
            $modelClassPath = sprintf('%s\\%s', $modelNamespace, $modelName);
            $service = $service ?? sprintf('%sService', $modelName);
            $dto = $dto ?? sprintf('%sDto', $modelName);
            $viewModel = $viewModel ?? sprintf('%sViewModel', $modelName);
        }
        if ($modelComponent) {
            $definition = $modelComponent->getDefinition();
            $serviceClass = ComponentCommandsHelpers::createService($namespace, $basePath, $modelClassPath, $service);
            $dtoClass = ComponentCommandsHelpers::createDto($namespace, $basePath, $modelClassPath, $dto, $definition instanceof DtoAttributesFactory ? $definition->createDtoAttributes() : []);
            $viewModelClass = ComponentCommandsHelpers::createViewModel($namespace, $basePath, $modelClassPath, $viewModel, $definition instanceof ViewModelRulesFactory ? $definition->createRules() : [], $definition instanceof ViewModelRulesFactory ? $definition->createRules(true) : []);
        } else {
            $serviceClass = sprintf('%s\\%s', sprintf('\\%s\\Services', ComponentCommandsHelpers::getBaseNamespace($namespace) ?? 'App'), "{$modelName}Service");
            $dtoClass = sprintf('%s\\%s', sprintf('\\%s\\Dto', ComponentCommandsHelpers::getBaseNamespace($namespace) ?? 'App'), "{$modelName}Dto");
            $viewModelClass = sprintf('%s\\%s', sprintf('\\%s\\Http\\ViewModels', ComponentCommandsHelpers::getBaseNamespace($namespace) ?? 'App'), "{$modelName}ViewModel");
        }
        ComponentsScriptWriter($basePath)->write(
            ComponentBuilderHelpers::buildController(
                $modelClassPath,
                $serviceClass,
                $viewModelClass,
                $dtoClass,
                $name,
                $namespace,
                true,
                $authorizable,
                $primaryKey
            )
        );
    }
}
