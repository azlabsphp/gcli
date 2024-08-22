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
use Drewlabs\GCli\Validation\RulesFactory;

class Config implements HasRelations
{
    /** @var string */
    const DEFAULT_PROJECT_NAMESPACE = 'App';

    /** @var Type */
    private $def;

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
     * @param RulesFactory $factory 
     * @param string $domain 
     * @param string|null $directory 
     * @param string|null $namespace 
     * @param string|null $schema 
     */
    public function __construct(
        Type $def,
        RulesFactory $factory,
        string $domain,
        string $directory = null,
        string $namespace = null,
        string $schema = null,
        bool $isHTTP = false,
        bool $authenticate = false,
        bool $authorize = false,
    ) {
        $domain = $domain;
        $directory = $directory ?? 'app';
        $namespace = $namespace ?? self::DEFAULT_PROJECT_NAMESPACE;
        $this->def = $def;

        $this->table = new TableConfig(
            $this->def,
            $directory,
            $domain,
            $schema
        );

        $this->view = new TableViewConfig(
            $factory,
            $this->def,
            $this->table->getClassPath(),
            $directory,
            $domain,
            $namespace,
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
            $this->def->primaryKey() ?? 'id',
            $authenticate,
            $authorize,
        );

        $this->dto = new TableDtoConfig(
            $this->table->getClassPath(),
            $this->def->columns(),
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
     * @param RulesFactory $factory 
     * @param string $domain 
     * @param string|null $directory 
     * @param string|null $namespace 
     * @param string|null $schema 
     * @param bool $isHTTP 
     * @param bool $authenticate 
     * @param bool $authorize 
     * @return static 
     */
    public static function new(
        Type $def,
        RulesFactory $factory,
        string $domain,
        string $directory = null,
        string $namespace = null,
        string $schema = null,
        bool $isHTTP = false,
        bool $authenticate = false,
        bool $authorize = false,
    ) {
        return new static(
            $def,
            $factory,
            $domain,
            $directory,
            $namespace,
            $schema,
            $isHTTP,
            $authenticate,
            $authorize
        );
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
     * returns the definition property value
     * 
     * @return Type&HasModuleMetadata
     */
    public function getType(): Type&HasModuleMetadata
    {
        return $this->def;
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
