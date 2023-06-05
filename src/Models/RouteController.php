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

namespace Drewlabs\GCli\Models;

class RouteController
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $classPath;

    /**
     * @var string
     */
    private $name;

    /**
     * Creates class instance.
     *
     * @param string $namespace
     * @param string $classPath
     */
    public function __construct(string $name, ?string $namespace = null, ?string $classPath = null)
    {
        $this->name = $name;
        $this->namespace = $namespace;
        $this->classPath = $classPath;
    }

    public function __serialize(): array
    {
        return ['classpath' => $this->getClassPath(), 'namespace' => $this->getNamespace()];
    }

    public function getClassPath()
    {
        return $this->classPath;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }
}
