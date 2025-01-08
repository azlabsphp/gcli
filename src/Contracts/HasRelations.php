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

interface HasRelations
{
    /**
     * Set the list realtions to be provideded.
     *
     * @return self|mixed
     */
    public function withRelations(array $relations);

    /**
     * Returns the list of relations of the current instance
     * 
     * @return Relation[] 
     */
    public function getRelations(): array;
}
