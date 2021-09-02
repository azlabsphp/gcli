<?php

namespace Drewlabs\ComponentGenerators;

use Drewlabs\ComponentGenerators\Contracts\UniqueKeyConstraintDefinition;
use Drewlabs\Support\Immutable\ValueObject;

/** @package Drewlabs\ComponentGenerators */
class ORMColumnUniqueKeyDefinition extends ValueObject implements UniqueKeyConstraintDefinition
{

    protected function getJsonableAttributes()
    {
        return [
            'table_' => 'table',
            'columns_' => 'columns'
        ];
    }

    public function getTable()
    {
        return $this->table_ ?? '';
    }

    public function getColumns()
    {
        return $this->columns_ ?? 'id';
    }
}
