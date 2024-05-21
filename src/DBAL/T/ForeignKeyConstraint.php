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

class ForeignKeyConstraint
{
    private $foreignKeys = [];

    /**
     * Class constructor.
     */
    public function __construct(array $foreignKeys)
    {
        $this->foreignKeys = $foreignKeys;
    }

    /**
     * Creates new class instance.
     *
     * @return static
     */
    public static function new(array $values)
    {
        return new static($values);
    }

    /**
     * binds foreign key constraint to colum instance.
     *
     * @param array|\Traversable<Column> $columns
     *
     * @return array
     */
    public function bind(string $table, $columns)
    {
        /** @var \Doctrine\DBAL\Schema\ForeignKeyConstraint[] */
        $foreignKeys = empty($this->foreignKeys) ? [] : $this->foreignKeys;
        if (!empty($foreignKeys)) {
            $foreignKeys = iterator_to_array((static function ($keys) use ($table) {
                /** @var \Doctrine\DBAL\Schema\ForeignKeyConstraint[] $keys */
                foreach ($keys as $key) {
                    yield $key->getLocalColumns()[0] => new ForeignKey(
                        $table,
                        $key->getLocalColumns(),
                        $key->getForeignTableName(),
                        $key->getForeignColumns(),
                        $key->getName()
                    );
                }
            })($foreignKeys));
        }
        $columns = \is_array($columns) ? $columns : iterator_to_array($columns);
        foreach ($foreignKeys as $key => $value) {
            /** @var Column $column */
            if (null !== ($column = $columns[$key] ?? null)) {
                $columns = array_merge($columns, [$key => $column->setForeignKey($value)]);
            }
        }

        return $columns;
    }
}
