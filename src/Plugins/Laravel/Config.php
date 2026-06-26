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

use Drewlabs\Core\Helpers\Arr;
use Drewlabs\GCli\Contracts\ComponentBuilder;
use Drewlabs\GCli\Contracts\EloquentORMModelBuilder;
use Drewlabs\GCli\Contracts\HasModuleMetadata;
use Drewlabs\GCli\Contracts\HasRelations;
use Drewlabs\GCli\Contracts\ORMModelDefinition as Type;
use Drewlabs\GCli\Contracts\Relation;
use Drewlabs\GCli\Contracts\RulesFactory;
use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\ControllerConfig;
use Drewlabs\GCli\DtoConfig;
use Drewlabs\GCli\ModelConfig;
use Drewlabs\GCli\PolicyConfig;

use function Drewlabs\GCli\Proxy\EloquentORMModelBuilder;

use Drewlabs\GCli\ServiceConfig;

use Drewlabs\GCli\ViewModelConfig;

final class Config implements HasRelations
{
    /** @var string */
    const DEFAULT_PROJECT_NAMESPACE = 'App';

    /** @var array */
    const DEFAULT_TIMESTAMP_COLUMNS = ['created_at', 'updated_at'];

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
     * Class constructor.
     */
    final public function __construct(
        Type&HasModuleMetadata $def,
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
        $directory = $directory;
        $namespace = $namespace ?? static::DEFAULT_PROJECT_NAMESPACE;

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
                sprintf('%s\\%s', $namespace, sprintf('%s%s', $domain ? "$domain\\" : '', 'ViewModels')),
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
                $namespace,
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
                sprintf('%s\\%s', $namespace, sprintf('%s%s', $domain ? "$domain\\" : '', 'Dto')),
                $this->table->getClassPath()
            ),
            $directory,
            $domain
        );

        $this->policy = new PolicyConfig(
            (new PolicyClassBuilder(null, sprintf('%s\\%s', $namespace, sprintf('%s%s', $domain ? "$domain\\" : '', 'Policies'))))
                ->withModel($this->table->getClassPath())
                ->withViewModel($this->view->getClassPath()),
            $directory,
            $domain
        );
    }

    /**
     * Class factory constructo.
     *
     * @return static
     */
    public static function new(
        Type&HasModuleMetadata $def,
        string $domain,
        ?string $directory,
        ?string $namespace,
        ?string $schema,
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
     * returns the definition property value.
     */
    public function getType(): Type&HasModuleMetadata
    {
        return $this->table->getType();
    }

    /**
     * Add a new relation on the relations stack.
     */
    public function addRelation(Relation $value): void
    {
        $values = $this->table->getRelations();
        $values[] = $value;
        $this->table = $this->table->withRelations($values);
    }

    /**
     * Append a list of relations to the existing config relations.
     *
     * @return HasRelations|mixed
     */
    public function withRelations(array $relations)
    {
        $this->table = $this->table->withRelations($relations);

        return $this;
    }

    /**
     * Get the list of configured relations.
     *
     * @return \Drewlabs\GCli\Contracts\Relation[]
     */
    public function getRelations(): array
    {
        return $this->table->getRelations();
    }

    /**
     * return model class source code configuration.
     */
    public function getModelConfig(): ModelConfig
    {
        return $this->table;
    }

    /**
     * returns view model class source code configuration.
     */
    public function getViewModelConfig(): ViewModelConfig
    {
        return $this->view;
    }

    /**
     * returns service class source code configuration.
     */
    public function getServiceConfig(): ServiceConfig
    {
        return $this->service;
    }

    /**
     * returns controller class source code configuration.
     */
    public function getControllerConfig(): ControllerConfig
    {
        return $this->controller;
    }

    /**
     * return dto class source code configuration.
     */
    public function getDtoConfig(): DtoConfig
    {
        return $this->dto;
    }

    /**
     * returns the policy class source code configuration.
     */
    public function getPolicyConfig(): PolicyConfig
    {
        return $this->policy;
    }

    /**
     * Return a model builder instance based on type definition and schema.
     *
     * @return EloquentORMModelBuilder
     */
    private function getModelBuilder(Type $def, ?string $schema = null)
    {
        $names = array_map(static function ($column) {
            return $column->name();
        }, $def->columns());
        $timestamps = Arr::containsAll($names, static::DEFAULT_TIMESTAMP_COLUMNS);

        return EloquentORMModelBuilder($def, $schema)->hasTimestamps($timestamps);
    }

    /**
     * Creates a factory method that create the controller script.
     *
     * @return \Closure(): SourceFileInterface
     */
    private function getControllerBuilder(
        ?string $model = null,
        string $namespace = 'App',
        ?string $domain = null,
        bool $authenticate = false,
        bool $authorizable = false,
        string $key = 'id'
    ) {
        return static function ($service = null, $view = null, $dto = null) use ($model, $authenticate, $authorizable, $key, $namespace, $domain) {
            return Facade::buildController(
                $model,
                $service ?? null,
                $view ?? null,
                $dto ?? null,
                null,
                sprintf('%s\\%s', $namespace, sprintf('%s%s', 'Http\\Controllers', $domain ? "\\$domain" : '')),
                $authenticate,
                $authorizable,
                $key
            );
        };
    }

    /**
     * Returns an instance of service contract builder.
     *
     * @return ServiceInterfaceBuilder
     */
    private function getServiceContractBuilder(string $model, ?string $domain = null, ?string $namespace = null)
    {
        return new ServiceInterfaceBuilder(
            sprintf('%s%s', array_reverse(explode('\\', $model))[0], 'ServiceInterface'),
            sprintf('%s\\%s', $namespace, sprintf('%s%s', $domain ? "$domain\\" : '', 'Contracts'))
        );
    }

    /**
     * Return service component type builder.
     *
     * @throws \InvalidArgumentException
     *
     * @return ServiceClassBuilder
     */
    private function getServiceBuilder(ComponentBuilder $contractBuilder, string $model, ?string $domain = null, ?string $namespace = null)
    {
        return Facade::createServiceBuilder(
            true,
            null,
            sprintf('%s\\%s', $namespace, sprintf('%s%s', $domain ? "$domain\\" : '', 'Services')),
            $model
        )->addContracts($contractBuilder->getClassPath());
    }
}
