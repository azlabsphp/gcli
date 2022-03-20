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

use Drewlabs\ComponentGenerators\Contracts\UniqueKeyConstraintDefinition;
use Drewlabs\PHPValue\Value;

class ORMColumnUniqueKeyDefinition extends Value implements UniqueKeyConstraintDefinition
{
    protected $__PROPERTIES__ = [
        'table_' => 'table',
        'columns_' => 'columns',
    ];

    public function getTable()
    {
        return $this->table_ ?? '';
    }

    public function getColumns()
    {
        return $this->columns_ ?? 'id';
    }
}
