<?php


namespace Drewlabs\ComponentGenerators;

use ArrayIterator;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Drewlabs\ComponentGenerators\Builders\DataTransfertClassBuilder;
use Drewlabs\ComponentGenerators\Contracts\ControllerBuilder;
use Drewlabs\ComponentGenerators\Contracts\ORMColumnDefinition;
use Drewlabs\ComponentGenerators\Contracts\ORMModelDefinition as ContractsORMModelDefinition;
use Drewlabs\ComponentGenerators\Helpers\ColumnsDefinitionHelpers;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\ComponentGenerators\Helpers\DataTypeToFluentValidationRulesHelper;
use Generator;
use Doctrine\DBAL\Schema\Table;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\EloquentORMModelBuilder;
use function Drewlabs\ComponentGenerators\Proxy\ORMModelDefinition;

class DatabaseSchemaReverseEngineeringRunner
{
    /**
     *
     * @var AbstractSchemaManager
     */
    private $manager;

    /**
     *
     * @var string
     */
    private $blocComponentPath_;

    /**
     *
     * @var string
     */
    private $blocComponentNamespace_;

    private const DEFAULT_BLOC_COMPONENT_NAMESPACE = "App";

    /**
     * List of table that must be ignore
     *
     * @var string[]
     */
    private $excepts_;

    /**
     *
     * @var \Closure
     */
    private $tablesFilterFunc_;

    /**
     * 
     * @var mixed
     */
    private $auth_ = true;

    /**
     * Components namespace
     * 
     * @var string
     */
    private $subNamespace_;

    public function __construct(
        AbstractSchemaManager $manager,
        string $blocComponentPath,
        string $blocComponentNamespace = 'App'
    ) {
        $this->manager = $manager;
        $this->blocComponentPath_ = $blocComponentPath ?? 'app';
        $this->blocComponentNamespace_ = $blocComponentNamespace ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE;
    }

    public function except(array $tables)
    {
        $this->excepts_ = $tables ?? [];
        return $this;
    }

    public function bindExceptMethod(\Closure $filterFn)
    {
        $this->tablesFilterFunc_ = $filterFn;
        return $this;
    }

    public function withoutAuth()
    {
        $this->auth_ = false;
        return $this;
    }

    public function setSubNamespace(?string $namespace = null)
    {
        $this->subNamespace_ = !empty($namespace) ? $namespace : $this->subNamespace_;
        return $this;
    }

    public function run(\Closure $callback = null)
    {
        // TODO: Read the database table using doctrine Database access layer
        // Filter tables during testing
        $tables = $this->manager->listTables();
        if (!empty($this->excepts_)) {
            $tables = array_filter($tables, function ($table) {
                return !in_array($table->getName(), $this->excepts_);
            });
        }
        if (null !== $this->tablesFilterFunc_) {
            $tables = array_filter($tables, $this->tablesFilterFunc_);
        }
        // TODO : For each table create model components
        $models = $this->tablesToORMModelDefinitionGenerator($tables);

        foreach ($models as $value) {
            // TODO: Generate model file in the model namespace
            $modelClass = EloquentORMModelBuilder($value)->build();
            ComponentsScriptWriter($this->blocComponentPath_)->write($modelClass);
            $modelClassPath = sprintf("%s\\%s", $modelClass->getNamespace(), drewlabs_core_strings_as_camel_case($modelClass->getName()));
            // TODO: Generate view model file for the model
            // TODO: Build rules from model defintions
            $viewModel = ComponentBuilderHelpers::buildViewModelDefinition(
                false,
                // Rules must be provided
                iterator_to_array((function (ContractsORMModelDefinition $model) {
                    foreach ($model->columns() as $value) {
                        yield $value->name() => $this->getColumRules($value, $model->primaryKey());
                    }
                })($value)),
                iterator_to_array((function (ContractsORMModelDefinition $model) {
                    foreach ($model->columns() as $value) {
                        yield $value->name() => $this->getColumRules($value, $model->primaryKey(), true);
                    }
                })($value)),
                null,
                sprintf("%s\\%s", $this->blocComponentNamespace_ ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf("%s%s", 'Http\\ViewModels', $this->subNamespace_ ? "\\$this->subNamespace_" : '')),
                // TODO Add namespace method to component items
                $modelClassPath
            );
            ComponentsScriptWriter($this->blocComponentPath_)->write($viewModel);
            // TODO : Generate Data Transfert model file
            $dtoObject = ComponentBuilderHelpers::buildDtoObjectDefinition(
                DataTransfertClassBuilder::buildAsAssociativeArray(
                    array_map(function (ORMColumnDefinition $colum) {
                        return $colum->name();
                    }, $value->columns())
                ),
                [],
                [],
                null,
                sprintf("%s\\%s", $this->blocComponentNamespace_ ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf("%s%s", 'DataTransfertObject', $this->subNamespace_ ? "\\$this->subNamespace_" : '')),
                $modelClassPath
            );
            ComponentsScriptWriter($this->blocComponentPath_)->write($dtoObject);
            // TODO : Generate Service file
            $service = ComponentBuilderHelpers::buildServiceDefinition(
                true,
                null,
                sprintf("%s\\%s", $this->blocComponentNamespace_ ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf("%s%s", 'Services', $this->subNamespace_ ? "\\$this->subNamespace_" : '')),
                $modelClassPath
            );
            ComponentsScriptWriter($this->blocComponentPath_)->write($service);
            // TODO : Generate controller file from the model
            $controller = ComponentBuilderHelpers::buildController(
                $modelClassPath,
                sprintf("%s\\%s", $service->getNamespace(), drewlabs_core_strings_as_camel_case($service->getName())),
                sprintf("%s\\%s", $viewModel->getNamespace(), drewlabs_core_strings_as_camel_case($viewModel->getName())),
                sprintf("%s\\%s", $dtoObject->getNamespace(), drewlabs_core_strings_as_camel_case($dtoObject->getName())),
                null,
                sprintf("%s\\%s", $this->blocComponentNamespace_ ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf("%s%s", 'Http\\Controllers', $this->subNamespace_ ? "\\$this->subNamespace_" : '')),
                $this->auth_
            );
            ComponentsScriptWriter($this->blocComponentPath_)->write($controller);
            $content = $controller->getContent();
            $routeName = $content instanceof ControllerBuilder ? $content->routeName() : ComponentBuilderHelpers::buildRouteName($controller->getName());
            $controllerClassPath = sprintf("%s\\%s", $content->getNamespace(), drewlabs_core_strings_as_camel_case($controller->getName()));
            // Yield the created mvc component route name;
            yield $routeName => $controllerClassPath;
        }

        if ($callback) {
            $callback(array_map(function($table) {
                return $table->getName();
            }, $tables));
        }
    }

    public function getColumRules(ORMColumnDefinition $column, string $primaryKey = null, $useUpdateRules = false)
    {
        $evaluateIfPrimaryKeyFunc = function ($key) use ($column) {
            if ($column->name() === $key) {
                return DataTypeToFluentValidationRulesHelper::SOMETIMES;
            }
            return null !== $key ?
                sprintf(
                    "%s:%s",
                    DataTypeToFluentValidationRulesHelper::REQUIRED_WITHOUT,
                    $key
                ) : DataTypeToFluentValidationRulesHelper::REQUIRED;
        };
        $rules[] = $column->required() ?
            ($useUpdateRules ? DataTypeToFluentValidationRulesHelper::SOMETIMES :
                $evaluateIfPrimaryKeyFunc($primaryKey)) :
            DataTypeToFluentValidationRulesHelper::NULLABLE;
        $rules = [...$rules, ...(DataTypeToFluentValidationRulesHelper::getRule($column->type()))];
        if ($constraints = $column->foreignConstraint()) {
            $rules = [...$rules, ...(DataTypeToFluentValidationRulesHelper::getRule($constraints))];
        }
        if (($constraints = $column->unique()) && !($useUpdateRules)) {
            $rules = [...$rules, ...(DataTypeToFluentValidationRulesHelper::getRule($constraints))];
        }
        return array_merge($rules);
    }

    /**
     * 
     * @param Table[] $tables 
     * @return Generator<int, ContractsORMModelDefinition, mixed, void> 
     */
    private function tablesToORMModelDefinitionGenerator(array $tables)
    {
        foreach ($tables as $table) {
            $table_name = $table->getName();
            //# region get table primary key columns
            $tPrimaryKey = $table->getPrimaryKey();
            $primaryKeyColumns = $tPrimaryKey ? $tPrimaryKey->getColumns() : [];
            $primaryKey = ($columnCount = count($primaryKeyColumns)) <= 1 ? ($columnCount === 1 ? $primaryKeyColumns[0] : null) : $primaryKeyColumns;
            //# end region get table primary key columns
            // #region column definition
            $columns = drewlabs_core_fn_compose(
                function ($table_name) use ($table) {
                    return ColumnsDefinitionHelpers::createColumnDefinitionsGenerator($table_name, new ArrayIterator($table->getColumns()));
                },
                function ($columns)  use ($table) {
                    return ColumnsDefinitionHelpers::bindForeignConstTraintsToColumns($table->getForeignKeys())($columns);
                },
                function ($columns)  use ($table) {
                    return ColumnsDefinitionHelpers::bindUniqueConstraintsToColumns($table->getIndexes())($columns);
                },
                function ($columns) {
                    return array_values($columns);
                }
            )($table_name);
            // #endregion colum definition
            // # Get table comment
            $comment = $table->getComment();
            // # Get unique primary key value - Cause Eloquent does not support composite keys
            $key = is_array($primaryKey) ? ($primaryKey[0] ?? null) : $primaryKey;
            // # Get unique primary key value
            yield ORMModelDefinition([
                'primaryKey' => $key ?? null,
                'name' => null,
                'table' => $table_name,
                'columns' => $columns,
                'increments' => !empty($key) ? $table->getColumn($key)->getAutoincrement() : false,
                'namespace' => sprintf("%s\\%s", $this->blocComponentNamespace_ ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf("%s%s", 'Models', $this->subNamespace_ ? "\\$this->subNamespace_" : '')),
                'comment' => $comment
            ]);
        }
    }
}
