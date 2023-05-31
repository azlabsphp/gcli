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

use Drewlabs\GCli\Contracts\UniqueKeyConstraintDefinition;

class ORMColumnUniqueKeyDefinition implements UniqueKeyConstraintDefinition
{
    private $table;

    private $columns;

    /**
     * Creates class instance.
     *
     * @param string|string[] $columns
     */
    public function __construct(string $table, $columns)
    {
        $this->table = $table;
        $this->columns = $columns;
    }

    public function getTable()
    {
        return $this->table ?? '';
    }

    public function getColumns()
    {
        return $this->columns ?? 'id';
    }
}
