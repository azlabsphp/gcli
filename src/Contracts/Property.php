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

interface Property
{
    /**
     * returns property `name` value.
     */
    public function name(): string;

    /**
     * returns the raw type declaration of the property.
     */
    public function getRawType(): string;

    /**
     * boolean flag for required state of the property.
     */
    public function required(): bool;
}
