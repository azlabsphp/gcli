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


use Drewlabs\GCli\Builders\DataTransfertClassBuilder as Builder;
use Drewlabs\GCli\Contracts\ORMColumnDefinition as Column;
use Drewlabs\GCli\Helpers\ComponentBuilder;
use InvalidArgumentException;

final class TableDtoConfig
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
        array $columns,
        string $directory,
        string $domain = null,
        string $namespace = 'App',
    ) {
        $builder = ComponentBuilder::createDtoBuilder(
            iterator_to_array((static function () use ($columns) {
                foreach ($columns as $column) {
                    yield $column->name() => $column->type();
                }
            })()),
            [],
            null,
            sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', $domain ? "$domain\\" : '', 'Dto')),
            $model
        );
        $this->path = implode(\DIRECTORY_SEPARATOR, [$directory, sprintf('%s', $domain ? "$domain/" : '')]);
        $this->builder = $builder;
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
     * facade to `builder` setCasts
     * 
     * @param array $casts
     *  
     * @return static
     */
    public function setCasts(array $casts)
    {
        if (method_exists($this->builder, 'setCasts')) {
            $this->builder = $this->builder->setCasts($casts ?? []);
        }

        return $this;
    }

    /**
     * facade to `builder` setCamelizeProperties
     * 
     * @return static 
     */
    public function camelizeProperties(bool $value)
    {
        $this->builder = $this->builder->setCamelizeProperties($value);
        return $this;
    }
}
