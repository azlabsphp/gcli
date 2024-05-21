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

use Drewlabs\CodeGenerator\Helpers\Str;
use Drewlabs\Core\Helpers\Functional;
use Drewlabs\GCli\Traits\ProvidesTrimTableSchema;
use Illuminate\Support\Pluralizer;

final class Iterator implements \IteratorAggregate
{
    use ProvidesTrimTableSchema;

    /** @var \Doctrine\DBAL\Schema\Table[] */
    private $tables;

    /** @var string */
    private $namespace;

    /** @var string|null */
    private $schema;

    /**
     * Creates Iterator instance.
     *
     * @param \Doctrine\DBAL\Schema\Table[] $tables
     */
    public function __construct(array $tables, string $namespace, ?string $schema)
    {
        $this->tables = $tables;
        $this->namespace = $namespace;
        $this->schema = $schema;
    }

    /**
     * @return \Traversable<Table>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->tables as $table) {
            $tableName = $table->getName();
            // # region get table primary key columns
            $tablePrimaryKey = $table->getPrimaryKey();
            $primaryKeyColumns = $tablePrimaryKey ? $tablePrimaryKey->getColumns() : [];
            $primaryKey = ($columnCount = \count($primaryKeyColumns)) <= 1 ? (1 === $columnCount ? $primaryKeyColumns[0] : null) : $primaryKeyColumns;
            // # end region get table primary key columns
            // #region column definition
            $columns = Functional::compose(
                static function ($table_name) use ($table) {
                    $factory = new ColumnsIteratorFactory();

                    return $factory->createIterator($table_name, new \ArrayIterator($table->getColumns()));
                },
                static function ($columns) use ($table) {
                    return ForeignKeyConstraint::new($table->getForeignKeys())->bind($table->getName(), $columns);
                },
                static function ($columns) use ($table) {
                    return UniqueKeyConstraint::new($table->getIndexes())->bind($columns);
                },
                static function ($columns) {
                    return array_values($columns);
                }
            )($tableName);
            // #endregion colum definition
            // # Get table comment
            $comment = $table->getComment();
            // # Get unique primary key value - Cause Eloquent does not support composite keys
            $key = \is_array($primaryKey) ? ($primaryKey[0] ?? null) : $primaryKey;
            // # Get unique primary key value
            $module = static::trimschema($tableName, $this->schema);
            yield new Table(
                $key ?? null,
                Str::camelize(Pluralizer::singular($module)),
                $tableName,
                $columns,
                !empty($key) ? $table->getColumn($key)->getAutoincrement() : false,
                $this->namespace,
                $module,
                $comment,
            );
        }
    }
}
