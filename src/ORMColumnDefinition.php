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
use Drewlabs\PHPValue\Value;

class ORMColumnDefinition extends Value implements ContractsORMColumnDefinition
{
    protected $__PROPERTIES__ = [
        'table_' => 'table',
        'name_' => 'name',
        'type_' => 'type',
        'default_' => 'default',
        'foreignKeyConstraint_' => 'foreignKeyConstraint',
        'uniqueKeyConstraint_' => 'uniqueKeyConstraint',
        'required_' => 'required',
        'unsigned_' => 'unsigned',
    ];

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
        return $this->uniqueKeyConstraint_;
    }

    public function required()
    {
        return boolval($this->required_) || false;
    }

    public function unsigned()
    {
        return boolval($this->unsigned_) || false;
    }

    /**
     * Returns the foreign constraint rules on the column.
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

    public function hasDefault()
    {
        return null !== $this->default_;
    }

    public function jsonSerialize()
    {
        $list = parent::jsonSerialize();

        return array_filter($list, static function ($value) {
            return null !== $value;
        });
    }
}
