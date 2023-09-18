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

use Drewlabs\Core\Helpers\Functional;
use Drewlabs\GCli\Helpers\ColumnsDefinitionHelpers;

final class ModelDefinitionIterator implements \IteratorAggregate
{
    /**
     * @var array
     */
    private $tables;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $domain;

    /**
     * Creates Iterator instance.
     *
     * @param string $domain
     */
    public function __construct(array $tables, string $namespace, string $domain = null)
    {
        $this->tables = $tables;
        $this->namespace = $namespace;
        $this->domain = $domain;
    }

    /**
     * @return \Traversable<ORMModelDefinition>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->tables as $table) {
            $name_ = $table->getName();
            // # region get table primary key columns
            $tPrimaryKey = $table->getPrimaryKey();
            $primaryKeyColumns = $tPrimaryKey ? $tPrimaryKey->getColumns() : [];
            $primaryKey = ($columnCount = \count($primaryKeyColumns)) <= 1 ? (1 === $columnCount ? $primaryKeyColumns[0] : null) : $primaryKeyColumns;
            // # end region get table primary key columns
            // #region column definition
            $columns = Functional::compose(
                static function ($table_name) use ($table) {
                    return ColumnsDefinitionHelpers::createColumnDefinitionsGenerator($table_name, new \ArrayIterator($table->getColumns()));
                },
                static function ($columns) use ($table) {
                    return ColumnsDefinitionHelpers::bindForeignConstTraintsToColumns($table->getForeignKeys())($columns);
                },
                static function ($columns) use ($table) {
                    return ColumnsDefinitionHelpers::bindUniqueConstraintsToColumns($table->getIndexes())($columns);
                },
                static function ($columns) {
                    return array_values($columns);
                }
            )($name_);
            // #endregion colum definition
            // # Get table comment
            $comment = $table->getComment();
            // # Get unique primary key value - Cause Eloquent does not support composite keys
            $key = \is_array($primaryKey) ? ($primaryKey[0] ?? null) : $primaryKey;
            // # Get unique primary key value
            yield new ORMModelDefinition(
                $key ?? null,
                null,
                $name_,
                $columns,
                !empty($key) ? $table->getColumn($key)->getAutoincrement() : false,
                sprintf('%s\\%s', $this->namespace, ltrim(sprintf('%s%s', $this->domain ? "$this->domain\\" : '', 'Models'))),
                $comment,
            );
        }
    }
}
