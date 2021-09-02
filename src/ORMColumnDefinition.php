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

namespace Drewlabs\ComponentGenerators;

use Drewlabs\ComponentGenerators\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\ComponentGenerators\Contracts\ORMColumnDefinition as ContractsORMColumnDefinition;
use Drewlabs\ComponentGenerators\Contracts\UniqueKeyConstraintDefinition;
use Drewlabs\Support\Immutable\ValueObject;

class ORMColumnDefinition extends ValueObject implements ContractsORMColumnDefinition
{
    public function name()
    {
        return $this->name_ ?? 'column';
    }

    public function type()
    {
        return $this->type_ ?? 'string';
    }

    public function setForeignKey(ForeignKeyConstraintDefinition $value)
    {
        return $this->copyWith(['foreignKeyConstraint' => $value]);
    }

    public function setUnique(UniqueKeyConstraintDefinition $value)
    {
        $self = $this->copyWith(['uniqueKeyConstraint' => $value]);
        return $self;
    }

    public function unique()
    {
        return $this->unique_;
    }

    public function required()
    {
        return $this->required_ || false;
    }

    public function unsigned()
    {
        return $this->uniqueKeyConstraint_ || false;
    }

    /**
     * Returns the foreign constraint rules on the column
     * 
     * @return ForeignKeyConstraintDefinition|null
     */
    public function foreignConstraint()
    {
        return $this->foreignKeyConstraint_;
    }

    public function getTable()
    {
        return $this->table_;
    }

    protected function getJsonableAttributes()
    {
        return [
            'table_' => 'table',
            'name_' => 'name',
            'type_' => 'type',
            'foreignKeyConstraint_' => 'foreignKeyConstraint',
            'uniqueKeyConstraint_' => 'uniqueKeyConstraint',
            'required_' => 'required',
            'unsigned_' => 'unsigned',
        ];
    }

    public function jsonSerialize()
    {
        $list = parent::jsonSerialize();
        return array_filter($list, function ($value) {
            return null !== $value;
        });
    }
}
