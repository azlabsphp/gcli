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

namespace Drewlabs\GCli\HTr;

class RouteRequestBodyMap
{
    /**
     * @var RouteRequestBody[]
     */
    private $values;

    /**
     * Creates new class instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Put new route request body into cache.
     *
     * @return $this
     */
    public function put(string $name, array $post, array $put)
    {
        $this->values[] = new RouteRequestBody($name, $post, $put);

        return $this;
    }

    /**
     * Search for a given route request body in the cache.
     *
     * @return RouteRequestBody|null
     */
    public function get(string $name)
    {
        foreach ($this->values as $value) {
            if ($value->getName() === $name) {
                return $value;
            }
        }

        return null;
    }
}
