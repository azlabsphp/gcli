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

use Drewlabs\CodeGenerator\Contracts\Stringable;

interface Writable extends Stringable
{
    /**
     * Returns the path the component should be written to.
     */
    public function getPath(): string;
}
