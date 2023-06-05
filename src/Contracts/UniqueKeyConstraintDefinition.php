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

namespace Drewlabs\GCli\Contracts;

interface UniqueKeyConstraintDefinition
{
    /**
     * Returns the column on which the rule must be defined.
     *
     * @return string
     */
    public function getTable();

    /**
     * Returns the column or the list of columns making the constraint.
     *
     * @return string|string[]
     */
    public function getColumns();
}
