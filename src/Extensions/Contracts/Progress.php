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

namespace Drewlabs\GCli\Extensions\Contracts;

interface Progress
{
    /**
     * Initialize the progresss element.
     */
    public function start(): void;

    /**
     * Advance the progress element.
     */
    public function advance(): void;

    /**
     * Stop the progression. Indicates that the progress reach an end.
     */
    public function finish(): void;
}
