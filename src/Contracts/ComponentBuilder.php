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

namespace Drewlabs\GCli\Contracts;

interface ComponentBuilder
{
    /**
     * @return SourceFileInterface
     */
    public function build();

    /**
     * Source code script path getter
     */
    public function getWritePath(): string;

    /**
     * Source code class path getter
     * 
     * @return string 
     */
    public function getClassPath();

    /**
     * Set the path where the component will be written to.
     *
     * @return self
     */
    public function setWritePath(string $path);
}
