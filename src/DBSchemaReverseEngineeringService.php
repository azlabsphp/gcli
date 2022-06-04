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

namespace Drewlabs\ComponentGenerators;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Drewlabs\ComponentGenerators\Contracts\ControllerBuilder;
use Drewlabs\ComponentGenerators\Contracts\SourceFileInterface;
use Drewlabs\ComponentGenerators\Helpers\ColumnsDefinitionHelpers;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Functional;
use Drewlabs\Core\Helpers\Str;

use function Drewlabs\ComponentGenerators\Proxy\EloquentORMModelBuilder;
use function Drewlabs\ComponentGenerators\Proxy\ORMModelDefinition;

class DBSchemaReverseEngineeringService
{
    /**
     * @var array
     */
    private const DEFAULT_TIMESTAMP_COLUMNS = ['created_at', 'updated_at'];

    private const DEFAULT_BLOC_COMPONENT_NAMESPACE = 'App';

    /**
     * @var AbstractSchemaManager
     */
    private $manager;

    /**
     * @var string
     */
    private $blocComponentPath_;

    /**
     * @var string
     */
    private $blocComponentNamespace_;

    /**
     * List of table that must be ignore.
     *
     * @var string[]
     */
    private $excepts_ = [];

    /**
     * @var \Closure
     */
    private $tablesFilterFunc_;

    /**
     * @var mixed
     */
    private $auth_ = true;

    /**
     * Components namespace.
     *
     * @var string
     */
    private $subNamespace_;

    /**
     * @var string
     */
    private $schema_;

    /**
     * @var bool
     */
    private $generateHttpHandlers_ = false;

    /**
     * @var string[]
     */
    private $tables_ = [];

    public function __construct(
        AbstractSchemaManager $manager,
        string $blocComponentPath,
        string $blocComponentNamespace = 'App'
    ) {
        $this->manager = $manager;
        $this->blocComponentPath_ = $blocComponentPath ?? 'app';
        $this->blocComponentNamespace_ = $blocComponentNamespace ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE;
    }

    /**
     * Specifies the tables for which code should be generated.
     *
     * @return self
     */
    public function only(array $tables)
    {
        $this->tables_ = $tables;

        return $this;
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

    public function setSchema(?string $value = null)
    {
        $this->schema_ = $value;

        return $this;
    }

    /**
     * @return self
     */
    public function withHttpHandlers()
    {
        $this->generateHttpHandlers_ = true;

        return $this;
    }

    public function run(?\Closure $callback = null)
    {
        // We apply filters to only generate code for tables that
        // passes the filters
        $tables = $this->applyFilters($this->manager->listTables());
        $models = $this->tablesToORMModelDefinitionGenerator($tables);
        foreach ($models as $value) {
            $components = [];
            $hasTimeStamps = Arr::containsAll(array_map(static function ($column) {
                return $column->name();
            }, $value->columns() ?? []), self::DEFAULT_TIMESTAMP_COLUMNS);
            $modelClass = EloquentORMModelBuilder(
                $value,
                $this->schema_
            )->hasTimestamps($hasTimeStamps)->build();
            $components['model'] = [
                'path' => $this->blocComponentPath_,
                'class' => $modelClass,
                'definitions' => $value,
            ];
            $modelClassPath = sprintf('%s\\%s', $modelClass->getNamespace(), Str::camelize($modelClass->getName()));
            // TODO: Generate view model file for the model
            // TODO: Build rules from model defintions
            $viewModel = ComponentBuilderHelpers::buildViewModelDefinition(
                false,
                // Rules must be provided
                $value->createRules(),
                $value->createRules(true),
                null,
                sprintf('%s\\%s', $this->blocComponentNamespace_ ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf('%s%s', $this->generateHttpHandlers_ ? 'Http\\ViewModels' : 'ViewModels', $this->subNamespace_ ? "\\$this->subNamespace_" : '')),
                sprintf('%s%s', $this->generateHttpHandlers_ ? 'Http/ViewModels' : 'ViewModels', $this->subNamespace_ ? "/$this->subNamespace_" : ''),
                // TODO Add namespace method to component items
                $modelClassPath,
                $this->generateHttpHandlers_ ?: false
            );
            $components['viewModel'] = [
                'path' => $this->blocComponentPath_,
                'class' => $viewModel,
            ];
            $service = ComponentBuilderHelpers::buildServiceDefinition(
                true,
                null,
                sprintf('%s\\%s', $this->blocComponentNamespace_ ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf('%s%s', 'Services', $this->subNamespace_ ? "\\$this->subNamespace_" : '')),
                $modelClassPath
            );
            $components['service'] = [
                'path' => $this->blocComponentPath_,
                'class' => $service,
            ];
            if ($this->generateHttpHandlers_) {
                $dtoObject = ComponentBuilderHelpers::buildDtoObjectDefinition(
                    $value->createDtoAttributes(),
                    [],
                    null,
                    sprintf('%s\\%s', $this->blocComponentNamespace_ ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf('%s%s', 'Dto', $this->subNamespace_ ? "\\$this->subNamespace_" : '')),
                    $modelClassPath
                );
                $controller = $this->generateController($modelClassPath, $service, $viewModel, $dtoObject);
                $content = $controller->getContent();
                $routeName = $content instanceof ControllerBuilder ? $content->routeName() : ComponentBuilderHelpers::buildRouteName($controller->getName());
                $controllerClassPath = sprintf('%s\\%s', $content->getNamespace(), Str::camelize($controller->getName()));
                $components['controller'] = [
                    'path' => $this->blocComponentPath_,
                    'class' => $controller,
                    'route' => [
                        'name' => $routeName,
                        'classPath' => $controllerClassPath,
                    ],
                    'dto' => [
                        'path' => $this->blocComponentPath_,
                        'class' => $dtoObject,
                    ],
                ];
            }
            yield $components;
        }

        if ($callback) {
            $callback(array_map(static function ($table) {
                return $table->getName();
            }, $tables));
        }
    }

    private function generateController(?string $model = null, ?SourceFileInterface $service = null, ?SourceFileInterface $viewModel = null, ?SourceFileInterface $dtoObject = null)
    {
        $controller = ComponentBuilderHelpers::buildController(
            $model,
            $service ? sprintf(
                '%s\\%s',
                $service->getNamespace(),
                Str::camelize($service->getName())
            ) : null,
            $viewModel ? sprintf(
                '%s\\%s',
                $viewModel->getNamespace(),
                Str::camelize($viewModel->getName())
            ) : null,
            $dtoObject ? sprintf(
                '%s\\%s',
                $dtoObject->getNamespace(),
                Str::camelize($dtoObject->getName())
            ) : null,
            null,
            sprintf('%s\\%s', $this->blocComponentNamespace_ ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf('%s%s', 'Http\\Controllers', $this->subNamespace_ ? "\\$this->subNamespace_" : '')),
            $this->auth_
        );

        return $controller;
    }

    private function applyFilters(array $tables)
    {
        if (!empty($this->excepts_)) {
            $tables = array_filter($tables, function ($table) {
                return !\in_array($table->getName(), $this->excepts_, true);
            });
        }
        // We apply a filter that returns only tables having the name
        // matching the names specified in the {@see $this->tables_} properties
        if (!empty($this->tables_)) {
            $tables = array_filter($tables, function ($table) {
                return \in_array($table->getName(), $this->tables_, true);
            });
        }
        if (null !== $this->tablesFilterFunc_) {
            $tables = array_filter($tables, $this->tablesFilterFunc_);
        }

        return $tables;
    }

    /**
     * @param Table[] $tables
     *
     * @return \Generator<int, ORMModelDefinition, mixed, void>
     */
    private function tablesToORMModelDefinitionGenerator(array $tables)
    {
        foreach ($tables as $table) {
            $name_ = $table->getName();
            // # region get table primary key columns
            $tPrimaryKey = $table->getPrimaryKey();
            $primaryKeyColumns = $tPrimaryKey ? $tPrimaryKey->getColumns() : [];
            $primaryKey = ($columnCount = \count($primaryKeyColumns)) <= 1 ? (1 === $columnCount ? $primaryKeyColumns[0] : null) : $primaryKeyColumns;
            // # end region get table primary key columns
            // #region column definition
            $columns = Functional::compose(
                static function ($table_name) use ($table) {
                    return ColumnsDefinitionHelpers::createColumnDefinitionsGenerator($table_name, new \ArrayIterator($table->getColumns()));
                },
                static function ($columns) use ($table) {
                    return ColumnsDefinitionHelpers::bindForeignConstTraintsToColumns($table->getForeignKeys())($columns);
                },
                static function ($columns) use ($table) {
                    return ColumnsDefinitionHelpers::bindUniqueConstraintsToColumns($table->getIndexes())($columns);
                },
                static function ($columns) {
                    return array_values($columns);
                }
            )($name_);
            // #endregion colum definition
            // # Get table comment
            $comment = $table->getComment();
            // # Get unique primary key value - Cause Eloquent does not support composite keys
            $key = \is_array($primaryKey) ? ($primaryKey[0] ?? null) : $primaryKey;
            // # Get unique primary key value
            yield ORMModelDefinition([
                'primaryKey' => $key ?? null,
                'name' => null,
                'table' => $name_,
                'columns' => $columns,
                'increments' => !empty($key) ? $table->getColumn($key)->getAutoincrement() : false,
                'namespace' => sprintf('%s\\%s', $this->blocComponentNamespace_ ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf('%s%s', 'Models', $this->subNamespace_ ? "\\$this->subNamespace_" : '')),
                'comment' => $comment,
            ]);
        }
    }
}
