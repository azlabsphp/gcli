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


use Drewlabs\GCli\Contracts\ComponentBuilder as Builder;

final class ServiceContractConfig
{

    /** @var Builder */
    private $builder;

    /** @var string */
    private $path;


    /**
     * Class constructor
     * 
     * @param Builder $builder 
     * @param string $directory 
     * @param string|null $domain 
     * @return void 
     */
    public function __construct(
        Builder $builder,
        string $directory,
        ?string $domain = null
    ) {
        $this->builder = $builder;
        $this->path = implode(\DIRECTORY_SEPARATOR, [$directory, sprintf('%s', $domain ? "$domain/" : '')]);
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
}
