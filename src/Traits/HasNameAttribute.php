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

trait HasNameAttribute
{
    /**
     * @var string
     */
    private $name_;

    public function setName(string $value)
    {
        $this->name_ = $value;
    }

    /**
     * Returns the component name property value.
     *
     * @return string
     */
    public function name(): ?string
    {
        return $this->name_;
    }
}
