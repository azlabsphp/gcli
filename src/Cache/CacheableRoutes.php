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

namespace Drewlabs\ComponentGenerators\Cache;

use Drewlabs\ComponentGenerators\Contracts\Cacheable;
use Drewlabs\PHPValue\Value;

class CacheableRoutes extends Value implements Cacheable
{
    protected $__PROPERTIES__ = [
        'routes',
    ];

    public function toArray()
    {
        return [
            'routes' => $this->getRoutes(),
        ];
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes ?? [];
    }

    public function unserialize(string $value)
    {
        return new self(unserialize($value));
    }

    public function serialize()
    {
        return serialize($this->toArray());
    }
}
