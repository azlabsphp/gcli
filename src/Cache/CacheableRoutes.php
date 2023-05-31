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

namespace Drewlabs\GCli\Cache;

use Drewlabs\GCli\Contracts\Cacheable;

class CacheableRoutes implements Cacheable
{
    /**
     * @var array
     */
    private $routes;

    /**
     * Creates class instance.
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function toArray()
    {
        return ['routes' => $this->getRoutes()];
    }

    public function getRoutes()
    {
        return $this->routes ?? [];
    }

    public function unserialize(string $value)
    {
        $result = unserialize($value);
        if (!\is_array($result)) {
            throw new \InvalidArgumentException('Serialized string is malformed');
        }

        return new self($result['routes']);
    }

    public function serialize()
    {
        return serialize($this->toArray());
    }
}
