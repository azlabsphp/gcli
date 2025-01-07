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

use Drewlabs\GCli\Plugins\Laravel\Facade;
use Drewlabs\GCli\Contracts\ORMModelDefinition as Type;
use Drewlabs\GCli\Contracts\RulesFactory;
use Drewlabs\GCli\Plugins\Laravel\ViewModelClassBuilder as Builder;
use InvalidArgumentException;

final class TableViewConfig
{

    /** @var Builder */
    private $builder;

    /** @var string */
    private $path;

    /**
     * Class constructor
     * 
     * @param Type $def 
     * @param string $directory 
     * @param string|null $domain 
     * @param string $namespace 
     * @param string $model 
     * @param RulesFactory|null $factory 
     * @param bool $isHTTP 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public function __construct(
        Type $def,
        string $model,
        string $directory,
        ?string $domain = null,
        string $namespace = 'App',
        ?RulesFactory $factory = null,
        bool $isHTTP = false
    ) {
        $this->builder = Facade::createViewModelBuilder(
            false,
            $factory ? $factory->createRules($def) : [],
            $factory ? $factory->createRules($def, true) : [],
            null,
            sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', $domain ? "$domain\\" : '', 'ViewModels')),
            null,
            $model,
            $isHTTP ?: false
        );
        $this->path = implode(\DIRECTORY_SEPARATOR, [$directory, sprintf('%s', $domain ? "$domain/" : '')]);
    }

    /**
     * set the class path on the builder instance
     * 
     * @param string $path 
     * 
     * @return TableViewConfig 
     */
    public function setDtoClassPath(string $path): self
    {
        $this->builder = $this->builder->setDTOClassPath($path);
        return $this;
    }

    /**
     * return the class path of the model
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


    /**
     * facade to builder `getRules` method
     * 
     * @return array 
     */
    public function getRules(): array
    {
        return $this->builder->getRules();
    }

    /**
     * facade to builder `getUpdateRules` method
     * 
     * @return array 
     */
    public function getUpdateRules(): array
    {
        return $this->builder->getUpdateRules() ?? [];
    }
}
