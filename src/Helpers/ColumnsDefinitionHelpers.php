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

namespace Drewlabs\ComponentGenerators\Helpers;

use Closure;
use Doctrine\DBAL\Schema\Column;
use Drewlabs\ComponentGenerators\ORMColumnDefinition;
use Drewlabs\ComponentGenerators\ORMColumnForeignKeyConstraintDefinition;
use Drewlabs\ComponentGenerators\ORMColumnUniqueKeyDefinition;
use Drewlabs\Core\Helpers\Iter;
use Generator;

class ColumnsDefinitionHelpers
{
    /**
     * Create an iterator or a generator of {@link ORMColumnDefinition} from a list of doctrine dbal column instance.
     *
     * @param \Traversable<Column> $columns
     *
     * @return \Generator<mixed, ORMColumnDefinition, mixed, void>
     */
    public static function createColumnDefinitionsGenerator(string $table, \Traversable $columns)
    {
        foreach ($columns as $column) {
            $regex = null;
            if ('datetime' === $column->getType()->getName()) {
                $regex = 'Y-m-d H:i:s';
            } elseif ($length = $column->getLength()) {
                $regex = (null === $length && 'bigint' === $column->getType()->getName()) ? \PHP_INT_MAX : $length;
            }
            // Evaluate other types in the future
            yield $column->getName() => new ORMColumnDefinition([
                'name' => $column->getName(),
                'table' => $table,
                'required' => $column->getNotnull(),
                'unsigned' => $column->getUnsigned(),
                'type' => $regex ? sprintf('%s:%s', $column->getType()->getName(), $regex) : sprintf('%s', $column->getType()->getName()),
            ]);
        }
    }

    public static function bindForeignConstTraintsToColumns($foreignKeys = [])
    {
        $foreignKeys = empty($foreignKeys) ? [] : $foreignKeys;
        if (!empty($foreignKeys)) {
            $foreignKeys = (iterator_to_array((static function ($keys) {
                foreach ($keys as $key) {
                    yield $key->getLocalColumns()[0] => new ORMColumnForeignKeyConstraintDefinition([
                        'local_table' => $key->getLocalTableName(),
                        'columns' => $key->getLocalColumns(),
                        'foreign_table' => $key->getForeignTableName(),
                        'foreign_columns' => $key->getForeignColumns(),
                        'key' => $key->getName(),
                    ]);
                }
            })($foreignKeys)));
        }

        return static function ($definitions) use ($foreignKeys) {
            /**
             * @var ORMColumnDefinition[]
             */
            $definitions = \is_array($definitions) ? $definitions : iterator_to_array($definitions);
            foreach ($foreignKeys as $key => $value) {
                if (($definition = $definitions[$key] ?? null)) {
                    $definitions = array_merge($definitions, [$key => $definition->setForeignKey($value)]);
                }
            }

            return $definitions;
        };
    }

    /**
     * 
     * @param array $indexes 
     * @return Closure 
     */
    public static function bindUniqueConstraintsToColumns($indexes = [])
    {
        $indexes = empty($indexes) ? [] : $indexes;
        $uniqueIndexes = [];
        if (!empty($indexes)) {
            $uniqueIndexes = (iterator_to_array(Iter::filter((static function ($keys) {
                foreach ($keys as $key) {
                    /**
                     * @var Index
                     */
                    $key = $key;
                    yield $key->getColumns()[0] => $key->isUnique();
                }
            })($indexes), static function ($item) {
                return true === $item;
            })));
        }

        return static function ($definitions) use ($uniqueIndexes) {
            /**
             * @var ORMColumnDefinition[]
             */
            $definitions = \is_array($definitions) ? $definitions : iterator_to_array($definitions);
            // Set the unique constraint on the definition
            foreach ($uniqueIndexes as $key => $value) {
                if (($definition = $definitions[$key] ?? null)) {
                    $definition = $definition->setUnique(true === $value ? new ORMColumnUniqueKeyDefinition([
                        'table' => $definition->getTable(),
                        'columns' => [$definition->name()],
                    ]) : null);
                    $definitions = array_merge(
                        $definitions,
                        [
                            $key => $definition,
                        ]
                    );
                }
            }

            return $definitions;
        };
    }
}
