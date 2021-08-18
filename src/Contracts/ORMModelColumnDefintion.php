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

namespace Drewlabs\ComponentGenerators\Contracts;

interface ORMModelColumnDefintion
{
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
}
