<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\GCli;

use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\GCli\Contracts\ORMColumnDefinition as ContractsORMColumnDefinition;
use Drewlabs\GCli\Contracts\UniqueKeyConstraintDefinition;

class ORMColumnDefinition implements ContractsORMColumnDefinition
{
    private $table;
    private $name;
    private $type;
    private $default;
    private $foreignKeyConstraint;
    private $uniqueKeyConstraint;
    private $required;
    private $unsigned;

    /**
     * Creates class instance
     * 
     * @param string $name 
     * @param string $type 
     * @param string|null $table 
     * @param bool $required 
     * @param string|int|float $default 
     * @param bool $unsigned 
     * @param ForeignKeyConstraintDefinition $foreignKeyConstraint 
     * @param UniqueKeyConstraintDefinition $uniqueKeyConstraint 
     */
    public function __construct(
        string $name,
        string $type,
        string $table = null,
        $required = false,
        $default = null,
        $unsigned = false,
        $foreignKeyConstraint = null,
        $uniqueKeyConstraint = null
    ) {
        $this->table = $table;
        $this->name = $name;
        $this->type = $type;
        $this->default = $default;
        $this->foreignKeyConstraint = $foreignKeyConstraint;
        $this->uniqueKeyConstraint = $uniqueKeyConstraint;
        $this->required = $required;
        $this->unsigned = $unsigned;
    }

    public function name()
    {
        return $this->name ?? 'column';
    }

    public function type()
    {
        return $this->type ?? 'string';
    }

    public function setForeignKey(ForeignKeyConstraintDefinition $value)
    {
        $self = clone $this;
        $self->foreignKeyConstraint = $value;
        return $self;
    }

    public function setUnique(UniqueKeyConstraintDefinition $value)
    {
        $self = clone $this;
        $self->uniqueKeyConstraint = $value;
        return $self;
    }

    public function unique()
    {
        return $this->uniqueKeyConstraint;
    }

    public function required()
    {
        return boolval($this->required) ?? false;
    }

    public function unsigned()
    {
        return boolval($this->unsigned) ?? false;
    }

    public function foreignConstraint()
    {
        return $this->foreignKeyConstraint;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function hasDefault()
    {
        return null !== $this->default;
    }

    public function jsonSerialize()
    {
        $result  = [
            'table' => $this->table,
            'name' => $this->name,
            'type' => $this->type,
            'default' => $this->default,
            'foreignKeyConstraint' => $this->foreignKeyConstraint,
            'uniqueKeyConstraint' => $this->uniqueKeyConstraint,
            'required' => $this->required,
            'unsigned' => $this->unsigned,
        ];
        return array_filter($result, static function ($value) {
            return null !== $value;
        });
    }
}
