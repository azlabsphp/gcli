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

use Drewlabs\GCli\Builders\PolicyClassBuilder as Builder;

final class TablePolicyConfig
{
    /** @var Builder */
    private $builder;

    /** @var string */
    private $path;

    /**
     * Class constructor
     * 
     * @param Column[] $columns 
     * @param string $model 
     * @param string $directory 
     * @param string|null $domain 
     * @param string $namespace 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public function __construct(
        string $model,
        string $tableView,
        string $directory,
        string $domain = null,
        string $namespace = 'App',
    ) {
        $this->builder = (new Builder(null, sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', $domain ? "$domain\\" : '', 'Policies'))))->withModel($model)->withViewModel($tableView);
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
