<?php

namespace Drewlabs\ComponentGenerators\Cache;

use Drewlabs\ComponentGenerators\Contracts\Cacheable;
use Drewlabs\Support\Immutable\ValueObject;

/** @package Drewlabs\ComponentGenerators\Extensions */
class CacheableRoutes extends ValueObject implements Cacheable
{

    protected function getJsonableAttributes()
    {
        return [
            'routes',
        ];
    }

    public function toArray()
    {
        return [
            'routes' => $this->getRoutes(),
        ];
    }

    /**
     * 
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
