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

use Drewlabs\GCli\Contracts\ORMModelDefinition as Type;
use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition as AbstractForeignConstraint;
use Drewlabs\GCli\Contracts\EloquentORMModelBuilder as Builder;
use Drewlabs\GCli\Contracts\HasModuleMetadata;
use Drewlabs\GCli\Contracts\HasRelations;
use Drewlabs\GCli\Contracts\Pivotable;
use Drewlabs\GCli\Contracts\ProvidesPropertyAccessors;
use Drewlabs\GCli\Contracts\Relation;


final class ModelConfig implements
    HasRelations,
    Pivotable,
    ProvidesPropertyAccessors
{

    /** @var array */
    const DEFAULT_TIMESTAMP_COLUMNS = ['created_at', 'updated_at'];

    /** @var Builder */
    private $builder;

    /** @var string */
    private $path;

    /** @var AbstractForeignConstraint[] */
    private $foreignKeys = [];

    /** @var Type */
    private $def;

    /** @var Relation[] */
    private $relations;

    /**
     * Class constructor
     * 
     * @param Type $def
     * @param Builder $builder
     * @param string $directory 
     * @param string|null $domain 
     * @return void 
     */
    public function __construct(Type $def, Builder $builder , string $directory, ?string $domain = null)
    {
        $this->def = $def;
        $this->builder = $builder;
        $this->path = implode(\DIRECTORY_SEPARATOR, [$directory, sprintf('%s', $domain ? "$domain/" : '')]);
        foreach ($this->def->columns() as $column) {
            if ($constraint = $column->foreignConstraint()) {
                $this->foreignKeys[] = $constraint;
            }
        }
    }

    public function withoutAccessors()
    {
        if ($this->builder instanceof ProvidesPropertyAccessors) {
            $this->builder = $this->builder->withoutAccessors();
        }
        return $this;
    }

    public function asPivot()
    {
        if ($this->builder instanceof Pivotable) {
            $this->builder = $this->builder->asPivot();
        }
        return $this;
    }

    public function withRelations(array $values): static
    {
        $self = clone $this;

        if ($self->def instanceof HasRelations) {
            $self->def = $self->def->withRelations($values);
        }

        if ($self->builder instanceof HasRelations) {
            $self->builder = $self->builder->withRelations($values);
        }

        $self->relations = $values;

        return $self;
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

    public function getRelations(): array
    {
        return $this->relations ?? [];
    }

    public function getTable()
    {
        return $this->def->table();
    }

    /**
     * returns the list of table foreign key constraints
     * 
     * @return array 
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys ?? [];
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
