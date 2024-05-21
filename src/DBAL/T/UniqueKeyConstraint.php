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

use Drewlabs\Core\Helpers\Iter;

class UniqueKeyConstraint
{
    /** @var array */
    private $indexes = [];

    /**
     * Class constructor.
     */
    public function __construct(array $indexes)
    {
        $this->indexes = $indexes;
    }

    /**
     * Creates new class instance.
     *
     * @return static
     */
    public static function new(array $indexes)
    {
        return new static($indexes);
    }

    /**
     * Bind unique key indexes to the column instance.
     *
     * @param array|\Traversable<Column> $columns
     *
     * @return Column[]
     */
    public function bind($columns)
    {
        $indexes = empty($this->indexes) ? [] : $this->indexes;
        $list = [];
        if (!empty($indexes)) {
            $list = iterator_to_array(Iter::filter((static function ($keys) {
                foreach ($keys as $key) {
                    yield $key->getColumns()[0] => $key->isUnique();
                }
            })($indexes), static function ($item) {
                return true === $item;
            }));
        }

        $columns = \is_array($columns) ? $columns : iterator_to_array($columns);
        // Set the unique constraint on the definition
        foreach ($list as $key => $value) {
            /** @var Column $column */
            if (null !== ($column = $columns[$key] ?? null) && (true === $value)) {
                $column = $column->setUnique(new UniqueKey($column->getTable(), [$column->name()]));
                $columns = array_merge($columns, [$key => $column]);
            }
        }

        return $columns;
    }
}
