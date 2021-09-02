<?php

namespace Drewlabs\ComponentGenerators;

use Drewlabs\ComponentGenerators\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\Support\Immutable\ValueObject;

/** @package Drewlabs\ComponentGenerators */
class ORMColumnForeignKeyConstraintDefinition extends ValueObject implements ForeignKeyConstraintDefinition
{

    protected function getJsonableAttributes()
    {
        return [
            'local_table',
            'columns',
            'foreign_table',
            'foreign_columns',
            'key'
        ];
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getLocalTableName()
    {
        return $this->local_table;
    }

    public function localColumns()
    {
        return $this->columns;
    }

    public function getForeignTableName()
    {
        return $this->foreign_table;
    }

    public function getForeignColumns()
    {
        return $this->foreign_columns ?? [];
    }
}
