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
use Drewlabs\PHPValue\Value;

class ORMColumnForeignKeyConstraintDefinition extends Value implements ForeignKeyConstraintDefinition
{
    protected $__PROPERTIES__ = [
        'local_table',
        'columns',
        'foreign_table',
        'foreign_columns',
        'key',
    ];

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
