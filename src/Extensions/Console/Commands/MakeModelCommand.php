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
use Drewlabs\GCli\Contracts\DtoAttributesFactory;
use Drewlabs\GCli\Contracts\ORMModelDefinition;
use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\Contracts\ViewModelRulesFactory;
use Drewlabs\GCli\Extensions\Console\ComponentCommandsHelpers;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;

use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;

use Illuminate\Console\Command;
use Illuminate\Container\Container;

use Illuminate\Contracts\Foundation\Application;

class MakeModelCommand extends Command
{
    protected $signature = 'gcli:make:model '
        .'{--increments : Makes the model primary key incrementable}'
        .'{--asViewModel : Generate the model as a view model class}'
        .'{--comment= Comment to be added to the model }'
        .'{--table= : Table name to attached to the model }'
        .'{--namespace= : Component namespace }'
        .'{--primaryKey= : Model primary key }'
        .'{--path= : Project source code path }'
        .'{--columns=* : List of model table fillable columns. (column|type) }'
        .'{--hidden=* List of hidden properties}'
        .'{--appends=* List of properties to append to the model }'
        .'{--all : Creates service, dto, view model and controller classes }';

    protected $description = 'Creates a model using Drewlabs package model definitions';
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
        if (null === ($table = $this->option('table') ?? null)) {
            return $this->error('Model generator requires at least the table name');
        }
        // Parameters initialization
        $primaryKey = $this->option('primaryKey') ?? 'id';
        $increments = $this->option('increments') ?? false;
        $namespace = $this->option('namespace') ?? '\\App\\Models';
        $columns = $this->option('columns') ?? [];
        $vm = $this->option('asViewModel') ?? false;
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        // # End of parameters initialization
        $builder = ComponentBuilderHelpers::createModelBuilder(
            $table,
            $columns ?? [],
            $namespace,
            $primaryKey,
            $increments,
            $vm,
            $this->option('hidden'),
            $this->option('appends') ?? [],
            $this->option('comment') ?? null
        );
        $definition = $builder->getDefinition();
        $component = $builder->build();
        ComponentsScriptWriter($basePath)->write($component);
        if ($this->option('all')) {
            static::createComponents($basePath, $component, $definition, $primaryKey);
        }
        $this->info("Model successfully generated for table : $table\n");
    }

    /**
     * @param mixed $basePath
     *
     * @throws PHPVariableException
     *
     * @return void
     */
    public static function createComponents($basePath, SourceFileInterface $component, ORMModelDefinition $definition = null, string $primaryKey = 'id')
    {
        $modelClassPath = sprintf('\\%s\\%s', $component->getNamespace(), $component->getName());
        $service = $service ?? sprintf('%sService', $component->getName());
        $dto = $dto ?? sprintf('%sDto', $component->getName());
        $viewModel = $viewModel ?? sprintf('%sViewModel', $component->getName());
        $serviceClass = ComponentCommandsHelpers::createService($component->getNamespace(), $basePath, $modelClassPath, $service);
        $dtoClass = ComponentCommandsHelpers::createDto($component->getNamespace(), $basePath, $modelClassPath, $dto, $definition instanceof DtoAttributesFactory ? $definition->createDtoAttributes() : []);
        $viewModelClass = ComponentCommandsHelpers::createViewModel($component->getNamespace(), $basePath, $modelClassPath, $viewModel, $definition instanceof ViewModelRulesFactory ? $definition->createRules() : [], $definition instanceof ViewModelRulesFactory ? $definition->createRules(true) : []);
        ComponentsScriptWriter($basePath)->write(
            ComponentBuilderHelpers::buildController(
                $modelClassPath,
                $serviceClass,
                $viewModelClass,
                $dtoClass,
                null,
                sprintf('\\%s\\Http\\Controllers', ComponentCommandsHelpers::getBaseNamespace($component->getNamespace()) ?? 'App'),
                true,
                false,
                $primaryKey
            )
        );
    }
}
