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

namespace Drewlabs\GCli;

class RelationTypes
{
    /**
     * Defines a 1 -> many relation.
     *
     * @var string
     */
    public const ONE_TO_MANY = '1 -> *';

    /**
     * Defines the inverse of a 1 -> many relation.
     *
     * @var string
     */
    public const MANY_TO_ONE = '* -> 1';

    /**
     * Defines a one to one relation type.
     *
     * @var string
     */
    public const ONE_TO_ONE = '1 -> 1';

    /**
     * Defines a many to many relation.
     */
    public const MANY_TO_MANY = '* -> *';

    /**
     * Defines a one to one through relation type.
     */
    public const ONE_TO_ONE_THROUGH = '1 -> t -> 1';

    /**
     * Defines a one to one through relation type.
     */
    public const ONE_TO_MANY_THROUGH = '1 -> t -> *';
}
