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

namespace Drewlabs\GCli;

use Closure;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaException;
use Drewlabs\CodeGenerator\Exceptions\PHPVariableException;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\GCli\Contracts\ControllerBuilder;
use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;

use function Drewlabs\GCli\Proxy\EloquentORMModelBuilder;
use function Drewlabs\GCli\Proxy\MVCPolicyBuilder;
use function Drewlabs\GCli\Proxy\ServiceInterfaceBuilderProxy;

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
    private const DEFAULT_PROJECT_NAMESPACE = 'App';

    /**
     * @var AbstractSchemaManager
     */
    private $manager;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $namespace;

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
    private $domain;

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
        string $directory,
        string $namespace = 'App'
    ) {
        $this->manager = $manager;
        $this->directory = $directory ?? 'app';
        $this->namespace = $namespace ?? self::DEFAULT_PROJECT_NAMESPACE;
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

    public function setDomain(string $namespace = null)
    {
        $this->domain = !empty($namespace) ? $namespace : $this->domain;

        return $this;
    }

    public function setSchema(string $value = null)
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
    public function handle(array &$foreignKeys, array &$tablesindexes, \Closure $callback = null)
    {
        // We apply filters to only generate code for tables that
        // passes the filters
        $index = 0;
        $tables = $this->applyFilters($this->manager->listTables());
        $traversable = new ModelDefinitionIterator($tables, $this->namespace ?? self::DEFAULT_PROJECT_NAMESPACE, $this->domain);
        foreach ($traversable as $value) {
            ++$index;
            $tablesindexes[$value->table()] = $index - 1;
            // for column foreign constraint push the constraint to the foreign key array
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
            $modelClassPath = $builder->getClassPath();
            $components['model'] = [
                'path' => implode(\DIRECTORY_SEPARATOR, [$this->directory, sprintf('%s', $this->domain ? "$this->domain/" : '')]),
                'class' => $builder,
                'definitions' => $value,
                'classPath' => $modelClassPath,
            ];
            $viewmodel = ComponentBuilderHelpers::createViewModelBuilder(
                false,
                $value->createRules(),
                $value->createRules(true),
                null,
                sprintf('%s\\%s', $this->namespace ?? self::DEFAULT_PROJECT_NAMESPACE, sprintf('%s%s', $this->domain ? "$this->domain\\" : '', 'ViewModels')),
                null,
                $modelClassPath,
                $this->http ?: false
            );
            $serviceType = ServiceInterfaceBuilderProxy(
                sprintf('%s%s', array_reverse(explode('\\', $modelClassPath))[0], 'ServiceInterface'),
                sprintf('%s\\%s', $this->namespace ?? self::DEFAULT_PROJECT_NAMESPACE, sprintf('%s%s', $this->domain ? "$this->domain\\" : '', 'Contracts'))
            );
            $service = ComponentBuilderHelpers::createServiceBuilder(
                true,
                null,
                sprintf('%s\\%s', $this->namespace ?? self::DEFAULT_PROJECT_NAMESPACE, sprintf('%s%s', $this->domain ? "$this->domain\\" : '', 'Services')),
                $modelClassPath
            )->addContracts($serviceType->getClassPath()); // Add the service type as a contract to service
            $dto = ComponentBuilderHelpers::createDtoBuilder(
                $value->createDtoAttributes(),
                [],
                null,
                sprintf('%s\\%s', $this->namespace ?? self::DEFAULT_PROJECT_NAMESPACE, sprintf('%s%s', $this->domain ? "$this->domain\\" : '', 'Dto')),
                $modelClassPath
            );

            $components['dto'] = [
                'path' => implode(\DIRECTORY_SEPARATOR, [$this->directory, sprintf('%s', $this->domain ? "$this->domain/" : '')]),
                'class' => $dto,
                'classPath' => $dto->getClassPath(),
            ];
            // Add the view model component
            $components['viewModel'] = [
                'path' => implode(\DIRECTORY_SEPARATOR, [$this->directory, sprintf('%s', $this->domain ? "$this->domain/" : '')]),
                'class' => $viewmodel,
                'classPath' => $viewmodel->getClassPath(),
            ];

            // Add the service component
            $components['service'] = [
                'path' => implode(\DIRECTORY_SEPARATOR, [$this->directory, sprintf('%s', $this->domain ? "$this->domain/" : '')]),
                'class' => $service,
                'classPath' => $service->getClassPath(),
                'type' => [
                    'path' => implode(\DIRECTORY_SEPARATOR, [$this->directory, sprintf('%s', $this->domain ? "$this->domain/" : '')]),
                    'class' => $serviceType,
                    'classPath' => $serviceType->getClassPath(),
                ],
            ];

            // Add Http Controllers
            if ($this->http) {
                $components['controller'] = [
                    'path' => $this->directory,
                    'class' => $this->createControllerFactoryMethod($modelClassPath, (bool) $this->policies),
                    'route' => [
                        'nameBuilder' => static function ($controller) {
                            return $controller instanceof ControllerBuilder ?
                                $controller->routeName() :
                                ComponentBuilderHelpers::buildRouteName($controller->getName());
                        },
                        'classPathBuilder' => static function (SourceFileInterface $controller) {
                            return $controller->getClassPath();
                        },
                    ],
                ];
            }
            // Add the policy component
            if ($this->policies) {
                $policyBuilder = MVCPolicyBuilder(
                    null,
                    sprintf('%s\\%s', $this->namespace ?? self::DEFAULT_PROJECT_NAMESPACE, sprintf('%s%s', $this->domain ? "$this->domain\\" : '', 'Policies'))
                )->withModel($modelClassPath)
                    ->withViewModel($viewmodel->getClassPath());
                $components['policy'] = [
                    'path' => implode(\DIRECTORY_SEPARATOR, [$this->directory, sprintf('%s', $this->domain ? "$this->domain/" : '')]),
                    'class' => $policyBuilder,
                    'classPath' => $policyBuilder->getClassPath(),
                ];
            }

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
     * @return Closure((null|string|string[])|null $service = null, (null|string)|null $viewModel = null, (null|string)|null $dtoObject = null): SourceFileInterface
     */
    private function createControllerFactoryMethod(string $model = null, bool $authorizable = false)
    {
        return function (
            $service = null,
            $viewModel = null,
            $dtoObject = null,
        ) use ($model, $authorizable) {
            return ComponentBuilderHelpers::buildController(
                $model,
                $service ?? null,
                $viewModel ?? null,
                $dtoObject ?? null,
                null,
                // TODO: Check in future release if controller should be moved to the Domain space
                sprintf('%s\\%s', $this->namespace ?? self::DEFAULT_PROJECT_NAMESPACE, sprintf('%s%s', 'Http\\Controllers', $this->domain ? "\\$this->domain" : '')),
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
}
