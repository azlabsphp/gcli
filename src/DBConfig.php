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

namespace Drewlabs\GCli;

use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition as ForeignKey;
use Drewlabs\GCli\Contracts\UniqueKeyConstraintDefinition as UniqueKey;
use Drewlabs\GCli\Config;

class DBConfig
{

    /** @var Config[]  */
    private $tables;

    /** @var ForeignKey[] */
    private $foreignKeys;

    /** @var UniqueKey[] */
    private $uniqueKeys;

    /** @var string[] */
    private $pivots;

    /**
     * DBConfig class constructor
     * 
     * @param array $tables 
     * @param array $foreignKeys 
     * @param array $uniqueKeys 
     * @param array $pivots 
     * @return void 
     */
    public function __construct(array $tables, array $foreignKeys = [], array $uniqueKeys = [], array $pivots = [])
    {
        $this->tables = $tables;
        $this->foreignKeys = $foreignKeys;
        $this->uniqueKeys = $uniqueKeys;
        $this->pivots = $pivots;
    }


    /**
     * returns an iterator of selected database tables
     * 
     * @return \Traversable<Config> 
     */
    public function getTablesIterator()
    {
        return new \ArrayIterator($this->tables);
    }

    /**
     * returns an array of selected database table configurations
     * 
     * @return Config[] 
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * returns array of selected database foreign keys
     * 
     * @return ForeignKey[] 
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * returns array of selected database unique keys
     * 
     * @return UniqueKey[] 
     */
    public function getUniqueKeys(): array
    {
        return $this->uniqueKeys;
    }

    /**
     * returns an array of tables that are pivotable
     * 
     * @return string[] 
     */
    public function getPivots(): array
    {
        return $this->pivots ?? [];
    }
}
