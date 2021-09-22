<?php

namespace Drewlabs\ComponentGenerators\Cache;

use Drewlabs\ComponentGenerators\Contracts\Cacheable as ContractsCacheable;
use Drewlabs\Support\Immutable\ValueObject;

/** @package Drewlabs\ComponentGenerators\Extensions */
class CacheableTables extends ValueObject implements ContractsCacheable
{

    protected function getJsonableAttributes()
    {
        return [
            'tables',
            'namespace',
            'subNamespace'
        ];
    }

    public function getTables()
    {
        return $this->tables ?? [];
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getSubNamespace()
    {
        return $this->subNamespace;
    }

    public function toArray()
    {
        return [
            'tables' => $this->getTables(),
            'namespace' => $this->getNamespace(),
            'subNamespace' => $this->getSubNamespace()
        ];
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
