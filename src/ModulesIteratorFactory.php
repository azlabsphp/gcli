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
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\GCli\Contracts\ControllerBuilder;
use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\Helpers\ComponentBuilder;
use Generator;
use InvalidArgumentException;
use Drewlabs\GCli\Validation\RulesFactory;

use function Drewlabs\GCli\Proxy\EloquentORMModelBuilder;
use function Drewlabs\GCli\Proxy\MVCPolicyBuilder;
use function Drewlabs\GCli\Proxy\ServiceInterfaceBuilderProxy;

class ModulesIteratorFactory
{
    /**
     * @var array
     */
    const DEFAULT_TIMESTAMP_COLUMNS = ['created_at', 'updated_at'];

    /** @var string */
    const DEFAULT_PROJECT_NAMESPACE = 'App';

    /** @var string */
    private $directory;

    /** @var string */
    private $namespace;

    /** @var mixed */
    private $auth = true;

    /** @var string */
    private $domain;

    /**  @var string */
    private $schema;

    /** @var bool */
    private $http = false;

    /** @var bool */
    private $policies = false;

    /** @var RulesFactory */
    private $rulesFactory;

    /**
     * Creates a database reverse engineering service instance.
     */
    public function __construct(RulesFactory $rulesFactory, string $directory, string $namespace = 'App')
    {
        $this->rulesFactory = $rulesFactory;
        $this->directory = $directory ?? 'app';
        $this->namespace = $namespace ?? self::DEFAULT_PROJECT_NAMESPACE;
    }


    public function withoutAuth()
    {
        $this->auth = false;
        return $this;
    }

    public function setDomain(string $domain = null)
    {
        $this->domain = !empty($domain) ? $domain : $this->domain;
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
     * @param \Traversable<\Drewlabs\GCli\Contracts\ORMModelDefinition> $traversable 
     * @param array $foreignKeys 
     * @param array $tablesindexes 
     * @param array $tableNames 
     * @return Generator<int, array, mixed, void>
     * 
     * @throws InvalidArgumentException 
     */
    public function createModulesIterator($traversable, array &$foreignKeys, array &$tablesindexes, array &$tableNames)
    {
        // We apply filters to only generate code for tables that
        // passes the filters
        $index = 0;
        foreach ($traversable as $value) {
            ++$index;
            $tableName = $value->table();
            $tableNames[] = $tableName;
            $tablesindexes[$tableName] = $index - 1;
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
                'definition' => $value,
                'classPath' => $modelClassPath,
            ];
            $viewmodel = ComponentBuilder::createViewModelBuilder(
                false,
                $this->rulesFactory->createRules($value),
                $this->rulesFactory->createRules($value, true),
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
            $service = ComponentBuilder::createServiceBuilder(
                true,
                null,
                sprintf('%s\\%s', $this->namespace ?? self::DEFAULT_PROJECT_NAMESPACE, sprintf('%s%s', $this->domain ? "$this->domain\\" : '', 'Services')),
                $modelClassPath
            )->addContracts($serviceType->getClassPath()); // Add the service type as a contract to service
            $dto = ComponentBuilder::createDtoBuilder(
                iterator_to_array((static function ($model) {
                    foreach ($model->columns() as $column) {
                        yield $column->name() => $column->type();
                    }
                })($value)),
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
                    'class' => $this->createControllerFactoryMethod($modelClassPath, (bool) $this->policies, $value->primaryKey() ?? 'id'),
                    'route' => [
                        'nameBuilder' => static function ($controller) {
                            return $controller instanceof ControllerBuilder ?
                                $controller->routeName() :
                                ComponentBuilder::buildRouteName($controller->getName());
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
    }

    /**
     * Creates a factory method that create the controller script.
     * 
     * @param string|null $model 
     * @param bool $authorizable 
     * @param string $key 
     * @return Closure(mixed $service = null, mixed $viewModel = null, mixed $dtoObject = null): SourceFileInterface 
     */
    private function createControllerFactoryMethod(string $model = null, bool $authorizable = false, string $key = 'id')
    {
        return function (
            $service = null,
            $viewModel = null,
            $dtoObject = null,
        ) use ($model, $authorizable, $key) {
            return ComponentBuilder::buildController(
                $model,
                $service ?? null,
                $viewModel ?? null,
                $dtoObject ?? null,
                null,
                // TODO: Check in future release if controller should be moved to the Domain space
                sprintf('%s\\%s', $this->namespace ?? self::DEFAULT_PROJECT_NAMESPACE, sprintf('%s%s', 'Http\\Controllers', $this->domain ? "\\$this->domain" : '')),
                $this->auth,
                $authorizable,
                $key
            );
        };
    }
}
