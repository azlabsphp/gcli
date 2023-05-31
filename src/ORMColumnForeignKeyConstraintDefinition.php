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

class ORMColumnForeignKeyConstraintDefinition implements ForeignKeyConstraintDefinition
{
    private $local_table;
    private $columns;
    private $foreign_table;
    private $foreign_columns;
    private $key;

    /**
     * Creates class instance.
     *
     * @param string   $local_table
     * @param string[] $columns
     * @param string   $foreign_table
     * @param string[] $foreign_columns
     * @param string   $key
     */
    public function __construct($local_table, $columns, $foreign_table, $foreign_columns, $key)
    {
        $this->local_table = $local_table;
        $this->columns = $columns;
        $this->foreign_table = $foreign_table;
        $this->foreign_columns = $foreign_columns;
        $this->key = $key;
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
