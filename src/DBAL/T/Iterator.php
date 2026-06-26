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
use Drewlabs\GCli\DBAL\ProvidesTrimTableSchema;
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
            $tableName = $table->getObjectName()->toString();

            $tablePrimaryKey = $table->getPrimaryKeyConstraint();
            $primaryKeyColumns = $tablePrimaryKey?->getColumnNames();
            $primaryKey = ($columnCount = \count($primaryKeyColumns ?? [])) <= 1 ? (1 === $columnCount ? $primaryKeyColumns[0] : null) : $primaryKeyColumns;
            
            $columns = Functional::compose(
                static function ($table_name) use ($table) {
                    $factory = new ColumnsIteratorFactory();

                    return $factory->createIterator($table_name, new \ArrayIterator($table->getColumns()));
                },
                static function ($columns) use ($table, $tableName) {
                    return ForeignKeyConstraint::new($table->getForeignKeys())->bind($tableName, $columns);
                },
                static function ($columns) use ($table) {
                    return UniqueKeyConstraint::new($table->getIndexes())->bind($columns);
                },
                static function ($columns) {
                    return array_values($columns);
                }
            )($tableName);
            
            $comment = $table->getComment();

            $key = \is_array($primaryKey) ? $primaryKey[0] : $primaryKey;
            // # Get unique primary key value
            $module = static::trimschema($tableName, $this->schema);
            yield new Table(
                $key ? $key->toString() : null,
                Str::camelize(Pluralizer::singular($module)),
                $tableName,
                $columns,
                !empty($key) ? $table->getColumn($key->toString())->getAutoincrement() : false,
                $this->namespace,
                $module,
                $comment,
            );
        }
    }
}
