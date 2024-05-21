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

interface Type
{
    /**
     * returns the type name.
     */
    public function name(): string;

    // /**
    //  * returns the actual name from the name property of the current type
    //  *
    //  * @return string
    //  */
    // public function getActualName(): string;

    /**
     * returns the list of type properties.
     *
     * @return Property[]
     */
    public function getProperties(): array;
}
