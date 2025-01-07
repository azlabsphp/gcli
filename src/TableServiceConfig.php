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

use Drewlabs\GCli\Plugins\Laravel\ServiceInterfaceBuilder;
use Drewlabs\GCli\Plugins\Laravel\ServiceClassBuilder as Builder;
use Drewlabs\GCli\Plugins\Laravel\Facade;
use Drewlabs\GCli\Contracts\ORMModelDefinition as Type;

final class TableServiceConfig
{

    /** @var Builder */
    private $builder;

    /** @var string */
    private $path;

    /**  @var TableServiceContractConfig */
    private $contract;

    /**
     * Class constructor
     * 
     * @param Type $def 
     * @param string $directory 
     * @param string|null $domain 
     * @param string|null $schema 
     */
    public function __construct(
        string $model,
        string $directory,
        ?string $domain = null,
        string $namespace = 'App'
    ) {
        $typeBuilder = new ServiceInterfaceBuilder(
            sprintf('%s%s', array_reverse(explode('\\', $model))[0], 'ServiceInterface'),
            sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', $domain ? "$domain\\" : '', 'Contracts'))
        );

        $this->builder = Facade::createServiceBuilder(
            true,
            null,
            sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', $domain ? "$domain\\" : '', 'Services')),
            $model
        )->addContracts($typeBuilder->getClassPath());
        
        $this->path = implode(\DIRECTORY_SEPARATOR, [$directory, sprintf('%s', $domain ? "$domain/" : '')]);
        $this->contract = new TableServiceContractConfig($typeBuilder, $directory, $domain);
    }

    /**
     * return the class path of the instance
     * 
     * @return string 
     */
    public function getClassPath(): string
    {
        return $this->builder->getClassPath();
    }


    /**
     * return the builder instance
     * 
     * @return Builder 
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }


    /**
     * return the path where instance source code must be generated
     * 
     * @return string 
     */
    public function getPath(): string
    {
        return $this->path;
    }


    public function getContract(): TableServiceContractConfig
    {
        return $this->contract;
    }
}
