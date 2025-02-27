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

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

final class PropertyName
{
    /** @var string */
    private $name;

    /**
     * L-value expression class constructor.
     *
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        if (((false !== ($offset_1 = strpos($this->name, '['))) && (false !== ($offset_2 = strpos($this->name, ']')))) || ((false !== ($offset_1 = strpos($this->name, '{'))) && (false !== ($offset_2 = strpos($this->name, '}'))))) {
            return trim(substr($this->name, $offset_1 + 1, $offset_2 - \strlen(substr($this->name, 0, $offset_1 + 1))));
        }

        return $this->name;
    }
}
