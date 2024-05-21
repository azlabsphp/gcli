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

namespace Drewlabs\GCli\Plugins;

use Drewlabs\GCli\Contracts\Type;

interface Plugin
{
    /**
     * compile source code for the provided type and write all components
     * source code to disk.
     *
     * @param string module
     */
    public function generate(Type $type, string $module = null): void;
}
