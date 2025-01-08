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

namespace Drewlabs\GCli\Plugins\Laravel;

use Drewlabs\GCli\Contracts\HasModuleMetadata;
use Drewlabs\GCli\Contracts\HasRelations;
use Drewlabs\GCli\Contracts\ORMModelDefinition as Type;
use Drewlabs\GCli\Contracts\Relation;
use Drewlabs\GCli\Contracts\RulesFactory;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\GCli\Contracts\ComponentBuilder;
use Drewlabs\GCli\Contracts\EloquentORMModelBuilder;
use Drewlabs\GCli\ControllerConfig;
use Drewlabs\GCli\DtoConfig;
use Drewlabs\GCli\ModelConfig;
use Drewlabs\GCli\Plugins\Laravel\Facade;
use Drewlabs\GCli\Plugins\Laravel\PolicyClassBuilder;
use Drewlabs\GCli\Plugins\Laravel\ServiceClassBuilder;
use Drewlabs\GCli\Plugins\Laravel\ServiceInterfaceBuilder;
use Drewlabs\GCli\PolicyConfig;
use Drewlabs\GCli\ServiceConfig;
use Drewlabs\GCli\ViewModelConfig;
use InvalidArgumentException;

use function Drewlabs\GCli\Proxy\EloquentORMModelBuilder;

class Config implements HasRelations
{
    /** @var string */
    const DEFAULT_PROJECT_NAMESPACE = 'App';

    /** @var array */
    const DEFAULT_TIMESTAMP_COLUMNS = ['created_at', 'updated_at'];

    // /** @var Type */
    // private $def;

    /** @var ModelConfig */
    private $table;

    /** @var ViewModelConfig */
    private $view;

    /** @var ServiceConfig */
    private $service;

    /** @var ControllerConfig */
    private $controller;

    /** @var DtoConfig */
    private $dto;

    /** @var PolicyConfig */
    private $policy;

    /**
     * Class constructor
     * 
     * @param Type $def 
     * @param string $domain 
     * @param string|null $directory 
     * @param string|null $namespace 
     * @param string|null $schema 
     * @param RulesFactory|null $factory 
     * @param bool $isHTTP 
     * @param bool $authenticate 
     * @param bool $authorize 
     */
    public function __construct(
        Type $def,
        ?string $domain = null,
        ?string $directory = null,
        ?string $namespace = null,
        ?string $schema = null,
        ?RulesFactory $factory = null,
        bool $isHTTP = false,
        bool $authenticate = false,
        bool $authorize = false,
    ) {
        $domain = $domain;
        $directory = $directory ?? 'app';
        $namespace = $namespace ?? self::DEFAULT_PROJECT_NAMESPACE;

        $this->table = new ModelConfig(
            $def,
            $this->getModelBuilder($def, $schema),
            $directory,
            $domain
        );

        $this->view = new ViewModelConfig(
            Facade::createViewModelBuilder(
                false,
                $factory ? $factory->createRules($def) : [],
                $factory ? $factory->createRules($def, true) : [],
                null,
                sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', $domain ? "$domain\\" : '', 'ViewModels')),
                null,
                $this->table->getClassPath(),
                $isHTTP ?: false
            ),
            $directory,
            $domain
        );

        $tableClassPath = $this->table->getClassPath();
        $contractBuilder = $this->getServiceContractBuilder($tableClassPath, $domain, $namespace);
        $this->service = new ServiceConfig(
            $this->getServiceBuilder($contractBuilder, $tableClassPath, $domain, $namespace),
            $contractBuilder,
            $directory,
            $domain
        );

        $this->controller = new ControllerConfig(
            $this->getControllerBuilder(
                $this->table->getClassPath(),
                $namespace ?? 'App',
                $domain,
                $authenticate,
                $authorize,
                $def->primaryKey() ?? 'id'
            ),
            $directory,
        );

        $this->dto = new DtoConfig(
            Facade::createDtoBuilder(
                iterator_to_array((static function () use ($def) {
                    foreach ($def->columns() as $column) {
                        yield $column->name() => $column->type();
                    }
                })()),
                [],
                null,
                sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', $domain ? "$domain\\" : '', 'Dto')),
                $this->table->getClassPath()
            ),
            $directory,
            $domain
        );

        $this->policy = new PolicyConfig(
            (new PolicyClassBuilder(null, sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', $domain ? "$domain\\" : '', 'Policies'))))
                ->withModel($this->table->getClassPath())
                ->withViewModel($this->view->getClassPath()),
            $directory,
            $domain
        );
    }

    /**
     * Class factory constructo
     * 
     * @param Type $def 
     * @param string $domain 
     * @param string|null $directory 
     * @param string|null $namespace 
     * @param string|null $schema 
     * @param RulesFactory $factory 
     * @param bool $isHTTP 
     * @param bool $authenticate 
     * @param bool $authorize 
     * @return static 
     */
    public static function new(
        Type $def,
        string $domain,
        ?string $directory = null,
        ?string $namespace = null,
        ?string $schema = null,
        RulesFactory $factory,
        bool $isHTTP = false,
        bool $authenticate = false,
        bool $authorize = false,
    ) {
        return new static(
            $def,
            $domain,
            $directory,
            $namespace,
            $schema,
            $factory,
            $isHTTP,
            $authenticate,
            $authorize
        );
    }

    /**
     * returns the definition property value
     * 
     * @return Type&HasModuleMetadata
     */
    public function getType(): Type&HasModuleMetadata
    {
        return $this->table->getType();
    }

    /**
     * Add a new relation on the relations stack
     * 
     * @param Relation $value
     * 
     * @return void 
     */
    public function addRelation(Relation $value): void
    {
        $values = $this->table->getRelations();
        $values[] = $value;
        $this->table = $this->table->withRelations($values);
    }

    /**
     * Append a list of relations to the existing config relations
     * 
     * @param array $relations
     * 
     * @return HasRelations|mixed 
     */
    public function withRelations(array $relations)
    {
        $this->table = $this->table->withRelations($relations);
        return $this;
    }

    /**
     * Get the list of configured relations
     * 
     * @return \Drewlabs\GCli\Contracts\Relation[] 
     */
    public function getRelations(): array
    {
        return $this->table->getRelations();
    }

    /**
     * return model class source code configuration
     * 
     * @return ModelConfig 
     */
    public function getModelConfig(): ModelConfig
    {
        return $this->table;
    }

    /**
     * returns view model class source code configuration
     * 
     * @return ViewModelConfig 
     */
    public function getViewModelConfig(): ViewModelConfig
    {
        return $this->view;
    }

    /**
     * returns service class source code configuration
     * 
     * @return ServiceConfig 
     */
    public function getServiceConfig(): ServiceConfig
    {
        return $this->service;
    }

    /**
     * returns controller class source code configuration
     * 
     * @return ControllerConfig 
     */
    public function getControllerConfig(): ControllerConfig
    {
        return $this->controller;
    }

    /**
     * return dto class source code configuration
     * 
     * @return DtoConfig 
     */
    public function getDtoConfig(): DtoConfig
    {
        return $this->dto;
    }

    /**
     * returns the policy class source code configuration
     * 
     * @return PolicyConfig 
     */
    public function getPolicyConfig(): PolicyConfig
    {
        return $this->policy;
    }

    /**
     * Return a model builder instance based on type definition and schema
     * 
     * @param Type $def 
     * @param null|string $schema 
     * @return EloquentORMModelBuilder 
     */
    private function getModelBuilder(Type $def, ?string $schema = null)
    {
        $timestamps = Arr::containsAll(array_map(static function ($column) {
            return $column->name();
        }, $columns ?? []), static::DEFAULT_TIMESTAMP_COLUMNS);
        return EloquentORMModelBuilder($def, $schema)->hasTimestamps($timestamps);
    }



    /**
     * Creates a factory method that create the controller script.
     *
     * @return \Closure(mixed $service = null, mixed $viewModel = null, mixed $dtoObject = null): SourceFileInterface
     */
    private function getControllerBuilder(
        ?string $model = null,
        string $namespace = 'App',
        ?string $domain = null,
        bool $authenticate = false,
        bool $authorizable = false,
        string $key = 'id'
    ) {
        return function ($service = null, $view = null, $dto = null) use ($model, $authenticate, $authorizable, $key, $namespace, $domain) {
            return Facade::buildController(
                $model,
                $service ?? null,
                $view ?? null,
                $dto ?? null,
                null,
                sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', 'Http\\Controllers', $domain ? "\\$domain" : '')),
                $authenticate,
                $authorizable,
                $key
            );
        };
    }


    /**
     * Returns an instance of service contract builder
     * 
     * @param string $model 
     * @param null|string $domain 
     * @param null|string $namespace 
     * @return ServiceInterfaceBuilder 
     */
    private function getServiceContractBuilder(string $model, ?string $domain = null, ?string $namespace = null)
    {
        return new ServiceInterfaceBuilder(
            sprintf('%s%s', array_reverse(explode('\\', $model))[0], 'ServiceInterface'),
            sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', $domain ? "$domain\\" : '', 'Contracts'))
        );
    }

    /**
     * Return service component type builder
     * 
     * @param ComponentBuilder $contractBuilder
     * @param string $model 
     * @param null|string $domain 
     * @param null|string $namespace 
     * @return ServiceClassBuilder 
     * @throws InvalidArgumentException 
     */
    private function getServiceBuilder(ComponentBuilder $contractBuilder, string $model, ?string $domain = null, ?string $namespace = null)
    {
        return Facade::createServiceBuilder(
            true,
            null,
            sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', $domain ? "$domain\\" : '', 'Services')),
            $model
        )->addContracts($contractBuilder->getClassPath());
    }
}
