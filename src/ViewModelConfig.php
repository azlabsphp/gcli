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

use Drewlabs\GCli\Contracts\ViewModelBuilder as Builder;

final class ViewModelConfig
{

    /** @var Builder */
    private $builder;

    /** @var string */
    private $path;


    /**
     * Class instance initializer
     * 
     * @param Builder $builder 
     * @param string $directory 
     * @param null|string $domain 
     * @return void 
     */
    public function __construct(
        Builder $builder,
        string $directory,
        ?string $domain = null,
    ) {
        $this->builder = $builder;
        $this->path = implode(\DIRECTORY_SEPARATOR, [$directory, sprintf('%s', $domain ? "$domain/" : '')]);
    }

    /**
     * set the class path on the builder instance
     * 
     * @param string $path 
     * 
     * @return ViewModelConfig 
     */
    public function setDtoClassPath(string $path): self
    {
        $this->builder = $this->builder->setDtoClassPath($path);
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
