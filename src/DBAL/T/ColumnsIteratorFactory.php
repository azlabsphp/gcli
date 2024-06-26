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

use Doctrine\DBAL\Schema\Column as DBALColumn;
use Drewlabs\GCli\Contracts\ORMColumnDefinition as AbstractColumn;

class ColumnsIteratorFactory
{
    /**
     * Creates an iterator of columns.
     *
     * @param \Traversable<DBALColumn>|DBALColumn[] $columns
     *
     * @return \Traversable<AbstractColumn>
     */
    public function createIterator(string $table, $columns): \Traversable
    {
        foreach ($columns as $column) {
            $regex = null;
            $type = $column->getType();
            $registry = $type->getTypeRegistry();
            $typeName = $registry->lookupName($type);
            $length = $column->getLength() ?? ('bigint' === $typeName ? \PHP_INT_MAX : null);
            if ('datetime' === $typeName) {
                $regex = 'Y-m-d H:i:s';
            } elseif ($length = $column->getLength()) {
                $regex = (null === $length && 'bigint' === $typeName) ? \PHP_INT_MAX : $length;
            }

            $instance = new Column(
                $column->getName(),
                $regex ? sprintf('%s:%s', $typeName, $regex) : sprintf('%s', $typeName),
                $table,
                $column->getNotnull(),
                $column->getDefault(),
                $column->getUnsigned(),
            );

            // Add the size information to the column instance
            if (null !== $length) {
                $instance = $instance->withSize($length);
            }

            yield $column->getName() => $instance;
        }
    }
}
