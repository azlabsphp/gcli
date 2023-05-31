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

namespace Drewlabs\GCli;

use Closure;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Drewlabs\CodeGenerator\Exceptions\PHPVariableException;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Functional;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ControllerBuilder;
use Drewlabs\GCli\Contracts\ORMColumnDefinition;
use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\Helpers\ColumnsDefinitionHelpers;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;

use function Drewlabs\GCli\Proxy\EloquentORMModelBuilder;
use function Drewlabs\GCli\Proxy\MVCPolicyBuilder;

use Exception as GlobalException;

class ReverseEngineeringService
{
    /**
     * @var array
     */
    private const DEFAULT_TIMESTAMP_COLUMNS = ['created_at', 'updated_at'];

    /**
     * @var string
     */
    private const DEFAULT_BLOC_COMPONENT_NAMESPACE = 'App';

    /**
     * @var AbstractSchemaManager
     */
    private $manager;

    /**
     * @var string
     */
    private $blocComponentPath;

    /**
     * @var string
     */
    private $blocComponentNamespace;

    /**
     * List of table that must be ignore.
     *
     * @var string[]
     */
    private $excepts = [];

    /**
     * @var \Closure
     */
    private $tablesFilterFunc;

    /**
     * @var mixed
     */
    private $auth = true;

    /**
     * Components namespace.
     *
     * @var string
     */
    private $subNamespace;

    /**
     * @var string
     */
    private $schema;

    /**
     * @var bool
     */
    private $http = false;

    /**
     * @var bool
     */
    private $policies = false;

    /**
     * @var string[]
     */
    private $tables = [];

    /**
     * Creates a database reverse engineering service instance.
     */
    public function __construct(
        AbstractSchemaManager $manager,
        string $blocComponentPath,
        string $blocComponentNamespace = 'App'
    ) {
        $this->manager = $manager;
        $this->blocComponentPath = $blocComponentPath ?? 'app';
        $this->blocComponentNamespace = $blocComponentNamespace ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE;
    }

    /**
     * Specifies the tables for which code should be generated.
     *
     * @return self
     */
    public function only(array $tables)
    {
        $this->tables = $tables;

        return $this;
    }

    public function except(array $tables)
    {
        $this->excepts = $tables ?? [];

        return $this;
    }

    public function bindExceptMethod(\Closure $filterFn)
    {
        $this->tablesFilterFunc = $filterFn;

        return $this;
    }

    public function withoutAuth()
    {
        $this->auth = false;

        return $this;
    }

    public function setSubNamespace(?string $namespace = null)
    {
        $this->subNamespace = !empty($namespace) ? $namespace : $this->subNamespace;

        return $this;
    }

    public function setSchema(?string $value = null)
    {
        $this->schema = $value;

        return $this;
    }

    /**
     * @return self
     */
    public function withHttpHandlers()
    {
        $this->http = true;

        return $this;
    }

    public function withPolicies()
    {
        $this->policies = true;

        return $this;
    }

    /**
     * Execute a reverse engeneering flow on database tables.
     *
     * @param (\Closure|null)|null $callback
     *
     * @throws Exception
     * @throws SchemaException
     * @throws GlobalException
     * @throws \InvalidArgumentException
     * @throws PHPVariableException
     *
     * @return \Generator<int, array, mixed, void>
     */
    public function handle(array &$foreignKeys, array &$tablesindexes, ?\Closure $callback = null)
    {
        // We apply filters to only generate code for tables that
        // passes the filters
        $tables = $this->applyFilters($this->manager->listTables());
        $models = $this->tablesToORMModelDefinitionGenerator($tables);
        $index = 0;
        foreach ($models as $value) {
            ++$index;
            $tablesindexes[$value->table()] = $index - 1;
            // for column foreign constraint push the constraint to the foreign key array
            /**
             * @var ORMColumnDefinition $column
             */
            foreach ($value->columns() as $column) {
                if ($constraint = $column->foreignConstraint()) {
                    $foreignKeys[] = $constraint;
                }
            }
            $components = ['table' => $value->table()];
            $hasTimeStamps = Arr::containsAll(array_map(static function ($column) {
                return $column->name();
            }, $value->columns() ?? []), self::DEFAULT_TIMESTAMP_COLUMNS);
            $builder = EloquentORMModelBuilder($value, $this->schema)->hasTimestamps($hasTimeStamps);
            $modelclasspath = $builder->getClassPath();
            $components['model'] = [
                'path' => $this->blocComponentPath,
                'class' => $builder,
                'definitions' => $value,
                'classPath' => $modelclasspath,
            ];
            $viewmodel = ComponentBuilderHelpers::createViewModelBuilder(
                false,
                $value->createRules(),
                $value->createRules(true),
                null,
                sprintf(
                    '%s\\%s',
                    $this->blocComponentNamespace ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE,
                    sprintf('%s%s', $this->http ? 'Http\\ViewModels' : 'ViewModels', $this->subNamespace ? "\\$this->subNamespace" : '')
                ),
                sprintf(
                    '%s%s',
                    $this->http ? 'Http/ViewModels' : 'ViewModels',
                    $this->subNamespace ? "/$this->subNamespace" : ''
                ),
                $modelclasspath,
                $this->http ?: false
            );
            $service = ComponentBuilderHelpers::createServiceBuilder(
                true,
                null,
                sprintf(
                    '%s\\%s',
                    $this->blocComponentNamespace ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE,
                    sprintf('%s%s', 'Services', $this->subNamespace ? "\\$this->subNamespace" : '')
                ),
                $modelclasspath
            );
            if ($this->http) {
                $dto = ComponentBuilderHelpers::createDtoBuilder(
                    $value->createDtoAttributes(),
                    [],
                    null,
                    sprintf(
                        '%s\\%s',
                        $this->blocComponentNamespace ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE,
                        sprintf(
                            '%s%s',
                            'Dto',
                            $this->subNamespace ? "\\$this->subNamespace" : ''
                        )
                    ),
                    $modelclasspath
                );
                $components['controller'] = [
                    'path' => $this->blocComponentPath,
                    'class' => $this->createControllerFactoryMethod($modelclasspath, (bool) $this->policies),
                    'route' => [
                        'nameBuilder' => static function ($controller) {
                            return $controller instanceof ControllerBuilder ?
                                $controller->routeName() :
                                ComponentBuilderHelpers::buildRouteName($controller->getName());
                        },
                        'classPathBuilder' => static function (SourceFileInterface $controller) {
                            return sprintf('%s\\%s', $controller->getNamespace(), Str::camelize($controller->getName()));
                        },
                    ],
                    'dto' => ['path' => $this->blocComponentPath, 'class' => $dto, 'classPath' => $dto->getClassPath()],
                ];
            }

            // Add the policy component
            if ($this->policies) {
                $policyBuilder = MVCPolicyBuilder()->withModel($modelclasspath)->withViewModel($viewmodel->getClassPath());
                $components['policy'] = ['path' => $this->blocComponentPath, 'class' => $policyBuilder, 'classPath' => $policyBuilder->getClassPath()];
            }
            // Add the view model component
            $components['viewModel'] = ['path' => $this->blocComponentPath, 'class' => $viewmodel, 'classPath' => $viewmodel->getClassPath()];

            // Add the service component
            $components['service'] = ['path' => $this->blocComponentPath, 'class' => $service, 'classPath' => $service->getClassPath()];

            // Yield the components container
            yield $components;
        }

        if ($callback) {
            $callback(array_map(static function ($table) {
                return $table->getName();
            }, $tables));
        }
    }

    /**
     * Creates a factory method that create the controller script.
     *
     * @param bool|null $authorizable
     *
     * @return Closure((null|SourceFileInterface)|null $service = null, (null|SourceFileInterface)|null $viewModel = null, (null|SourceFileInterface)|null $dtoObject = null): SourceFileInterface
     */
    private function createControllerFactoryMethod(?string $model = null, bool $authorizable = false)
    {
        return function (
            ?SourceFileInterface $service = null,
            ?SourceFileInterface $viewModel = null,
            ?SourceFileInterface $dtoObject = null
        ) use ($model, $authorizable) {
            return ComponentBuilderHelpers::buildController(
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
                sprintf('%s\\%s', $this->blocComponentNamespace ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf('%s%s', 'Http\\Controllers', $this->subNamespace ? "\\$this->subNamespace" : '')),
                $this->auth,
                $authorizable
            );
        };
    }

    /**
     * Filter tables and ignore not required tables.
     *
     * @return array
     */
    private function applyFilters(array $tables)
    {
        if (!empty($this->excepts)) {
            $tables = array_filter($tables, function ($table) {
                return !\in_array($table->getName(), $this->excepts, true);
            });
        }
        // We apply a filter that returns only tables having the name
        // matching the names specified in the {@see $this->tables_} properties
        if (!empty($this->tables)) {
            $tables = array_filter($tables, function ($table) {
                return \in_array($table->getName(), $this->tables, true);
            });
        }
        if (null !== $this->tablesFilterFunc) {
            $tables = array_filter($tables, $this->tablesFilterFunc);
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
            yield new ORMModelDefinition(
                $key ?? null,
                null,
                $name_,
                $columns,
                !empty($key) ? $table->getColumn($key)->getAutoincrement() : false,
                sprintf('%s\\%s', $this->blocComponentNamespace ?? self::DEFAULT_BLOC_COMPONENT_NAMESPACE, sprintf('%s%s', 'Models', $this->subNamespace ? "\\$this->subNamespace" : '')),
                $comment,
            );
        }
    }
}
