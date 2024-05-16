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

namespace Drewlabs\GCli\DBAL\T;

use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition;

/** @internal */
class ForeignKey implements ForeignKeyConstraintDefinition
{
    
    /**  @var string */
    private $local_table;
    
    /**  @var string[] */
    private $columns;
    
    /**  @var string */
    private $foreign_table;
    
    /**  @var string[] */
    private $foreign_columns;
    
    /**  @var string */
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
