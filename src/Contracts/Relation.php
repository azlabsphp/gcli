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

interface Relation extends HasModuleMetadata
{
    /**
     * returns the type of the relation.
     */
    public function getType(): string;

    /**
     * Get relation name.
     */
    public function getName(): string;

    /**
     * Returns the instance to which points the relation.
     */
    public function to(): string;

    /**
     * Checks if the instance provides a -> * link.
     */
    public function multi(): bool;
}
