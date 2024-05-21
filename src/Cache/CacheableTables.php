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

namespace Drewlabs\GCli\Cache;

use Drewlabs\GCli\Contracts\Cacheable as AbstractCacheable;

class CacheableTables implements AbstractCacheable
{
    private $tables;
    private $namespace;
    private $subNamespace;

    /**
     * Creates class instance.
     *
     * @param iterable $tables
     * @param string   $namespace
     */
    public function __construct(array $tables = [], string $namespace = null, string $subNamespace = null)
    {
        $this->tables = $tables;
        $this->namespace = $namespace;
        $this->subNamespace = $subNamespace;
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
            'subNamespace' => $this->getSubNamespace(),
        ];
    }

    public function unserialize(string $value)
    {
        $result = unserialize($value);
        if (!\is_array($result)) {
            throw new \InvalidArgumentException('Serialized string is malformed');
        }

        return new self($result['tables'] ?? [], $result['namespace'] ?? null, $result['subNamespace']);
    }

    public function serialize()
    {
        return serialize($this->toArray());
    }
}
