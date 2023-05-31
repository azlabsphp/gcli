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

namespace Drewlabs\GCli\Contracts;

interface ORMColumnDefinition
{
    /**
     * Return the table that the column belongs to.
     *
     * @return string
     */
    public function getTable();

    /**
     * Returns the column name from the definitions.
     *
     * @return string
     */
    public function name();

    /**
     * Returns the column type definition.
     *
     * @return string
     */
    public function type();

    /**
     * Returns the unique constraint rules on the column.
     *
     * @return UniqueKeyConstraintDefinition|null
     */
    public function unique();

    /**
     * Returns the foreign constraint rules on the column.
     *
     * @return ForeignKeyConstraintDefinition|null
     */
    public function foreignConstraint();

    /**
     * Indicates whether the column is required or not.
     *
     * @return bool
     */
    public function required();

    /**
     * Indicates whether the column is unsigned or not.
     *
     * @return bool
     */
    public function unsigned();

    /**
     * Checks if the current column has default value.
     *
     * @return bool
     */
    public function hasDefault();
}
