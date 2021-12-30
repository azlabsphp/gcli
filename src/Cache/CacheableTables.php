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

use Drewlabs\ComponentGenerators\Contracts\Cacheable as ContractsCacheable;
use Drewlabs\Support\Immutable\ValueObject;

class CacheableTables extends ValueObject implements ContractsCacheable
{
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
            'subNamespace' => $this->getSubNamespace(),
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

    protected function getJsonableAttributes()
    {
        return [
            'tables',
            'namespace',
            'subNamespace',
        ];
    }
}
