<?php

namespace Drewlabs\ComponentGenerators\Helpers;

use Doctrine\DBAL\Schema\Column;
use Drewlabs\ComponentGenerators\ORMColumnDefinition;
use Drewlabs\ComponentGenerators\ORMColumnForeignKeyConstraintDefinition;
use Drewlabs\ComponentGenerators\ORMColumnUniqueKeyDefinition;
use Generator;
use Traversable;

class ColumnsDefinitionHelpers
{

    /**
     * Create an iterator or a generator of {@link ORMColumnDefinition} from a list of doctrine dbal column instance
     * 
     * @param string $table
     * @param Traversable<Column> $columns 
     * @return Generator<mixed, ORMColumnDefinition, mixed, void> 
     */
    public static function createColumnDefinitionsGenerator(string $table, Traversable $columns)
    {
        foreach ($columns as $column) {
            $regex = null;
            if ($column->getType()->getName() === 'datetime') {
                $regex = "Y-m-d H:i:s";
            } else if ($length = $column->getLength()) {
                $regex = (null === $length && $column->getType()->getName() === 'bigint') ? PHP_INT_MAX : $length;
            }
            // Evaluate other types in the future
            yield $column->getName() => new ORMColumnDefinition([
                'name' => $column->getName(),
                'table' => $table,
                'required' => $column->getNotnull(),
                'unsigned' => $column->getUnsigned(),
                'type' => $regex ? sprintf("%s:%s", $column->getType()->getName(), $regex) : sprintf("%s", $column->getType()->getName()),
            ]);
        }
    }

    public static function bindForeignConstTraintsToColumns($foreignKeys = [])
    {
        $foreignKeys = empty($foreignKeys) ? [] : $foreignKeys;
        if (!empty($foreignKeys)) {
            $foreignKeys = (iterator_to_array((function ($keys) {
                foreach ($keys as $key) {
                    yield $key->getLocalColumns()[0] => new ORMColumnForeignKeyConstraintDefinition([
                        'local_table' => $key->getLocalTableName(),
                        'columns' => $key->getLocalColumns(),
                        'foreign_table' => $key->getForeignTableName(),
                        'foreign_columns' => $key->getForeignColumns(),
                        'key' => $key->getName()
                    ]);
                }
            })($foreignKeys)));
        }
        return function ($definitions) use ($foreignKeys) {
            /**
             * @var ORMColumnDefinition[]
             */
            $definitions = is_array($definitions) ? $definitions : iterator_to_array($definitions);
            foreach ($foreignKeys as $key => $value) {
                if (($definition = $definitions[$key] ?? null)) {
                    $definitions = array_merge($definitions, [$key => $definition->setForeignKey($value)]);
                }
            }
            return $definitions;
        };
    }

    public static function bindUniqueConstraintsToColumns($indexes = [])
    {
        $indexes = empty($indexes) ? [] : $indexes;
        $uniqueIndexes = [];
        if (!empty($indexes)) {
            $uniqueIndexes = (iterator_to_array(drewlabs_core_iter_filter((function ($keys) {
                foreach ($keys as $key) {
                    /**
                     * @var Index
                     */
                    $key = $key;
                    yield $key->getColumns()[0] => $key->isUnique();
                }
            })($indexes), function ($item) {
                return $item === true;
            })));
        }
        return function ($definitions) use ($uniqueIndexes) {
            /**
             * @var ORMColumnDefinition[]
             */
            $definitions = is_array($definitions) ? $definitions : iterator_to_array($definitions);
            // Set the unique constraint on the definition
            foreach ($uniqueIndexes as $key => $value) {
                if (($definition = $definitions[$key] ?? null)) {
                    $definition = $definition->setUnique($value === true ? new ORMColumnUniqueKeyDefinition([
                        'table' => $definition->getTable(),
                        'columns' => [$definition->name()]
                    ]) : null);
                    $definitions = array_merge(
                        $definitions,
                        [
                            $key => $definition
                        ]
                    );
                }
            }
            return $definitions;
        };
    }
}
