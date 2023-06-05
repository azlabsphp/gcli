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

namespace Drewlabs\GCli\Traits;

trait HasPathAttribute
{
    /**
     * @var string
     */
    private $path_;

    public function setWritePath(string $path)
    {
        $this->path_ = $path;

        return $this;
    }

    public function getWritePath(): string
    {
        return $this->path_ ?? '';
    }
}
