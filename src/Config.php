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

use Drewlabs\GCli\Contracts\HasModuleMetadata;
use Drewlabs\GCli\Contracts\HasRelations;
use Drewlabs\GCli\Contracts\ORMModelDefinition as Type;
use Drewlabs\GCli\Contracts\Relation;
use Drewlabs\GCli\Validation\RulesFactory;

class Config implements HasRelations
{
    /** @var string */
    const DEFAULT_PROJECT_NAMESPACE = 'App';

    // /** @var Type */
    // private $def;

    /** @var TableConfig */
    private $table;

    /** @var TableViewConfig */
    private $view;

    /** @var TableServiceConfig */
    private $service;

    /** @var TableControllerConfig */
    private $controller;

    /** @var TableDtoConfig */
    private $dto;

    /** @var TablePolicyConfig */
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
        string $domain = null,
        string $directory = null,
        string $namespace = null,
        string $schema = null,
        RulesFactory $factory = null,
        bool $isHTTP = false,
        bool $authenticate = false,
        bool $authorize = false,
    ) {
        $domain = $domain;
        $directory = $directory ?? 'app';
        $namespace = $namespace ?? self::DEFAULT_PROJECT_NAMESPACE;

        $this->table = new TableConfig(
            $def,
            $directory,
            $domain,
            $schema
        );

        $this->view = new TableViewConfig(
            $def,
            $this->table->getClassPath(),
            $directory,
            $domain,
            $namespace,
            $factory,
            $isHTTP
        );
        $this->service = new TableServiceConfig(
            $this->table->getClassPath(),
            $directory,
            $domain,
            $namespace,
        );

        $this->controller = new TableControllerConfig(
            $this->table->getClassPath(),
            $directory,
            $domain,
            $namespace,
            $def->primaryKey() ?? 'id',
            $authenticate,
            $authorize,
        );

        $this->dto = new TableDtoConfig(
            $this->table->getClassPath(),
            $def->columns(),
            $directory,
            $domain,
            $namespace,
        );

        $this->policy = new TablePolicyConfig(
            $this->table->getClassPath(),
            $this->view->getClassPath(),
            $directory,
            $domain,
            $namespace,
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
        string $directory = null,
        string $namespace = null,
        string $schema = null,
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
     * @return void 
     */
    public function addRelation(Relation $value): void
    {
        $values = $this->table->getRelations();
        $values[] = $value;
        $this->table = $this->table->withRelations($values);
    }

    public function withRelations(array $relations)
    {
        $this->table = $this->table->withRelations($relations);
        return $this;
    }

    public function getRelations(): array
    {
        return $this->table->getRelations();
    }

    /**
     * return model class source code configuration
     * 
     * @return TableConfig 
     */
    public function getTableConfig(): TableConfig
    {
        return $this->table;
    }

    /**
     * returns view model class source code configuration
     * 
     * @return TableViewConfig 
     */
    public function getTableViewConfig(): TableViewConfig
    {
        return $this->view;
    }

    /**
     * returns service class source code configuration
     * 
     * @return TableServiceConfig 
     */
    public function getTableServiceConfig(): TableServiceConfig
    {
        return $this->service;
    }

    /**
     * returns controller class source code configuration
     * 
     * @return TableControllerConfig 
     */
    public function getTableControllerConfig(): TableControllerConfig
    {
        return $this->controller;
    }

    /**
     * return dto class source code configuration
     * 
     * @return TableDtoConfig 
     */
    public function getTableDtoConfig(): TableDtoConfig
    {
        return $this->dto;
    }

    /**
     * returns the policy class source code configuration
     * 
     * @return TablePolicyConfig 
     */
    public function getTablePolicyConfig(): TablePolicyConfig
    {
        return $this->policy;
    }
}
