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
    private $name;

    /**
     * Creates class instance.
     *
     * @param string $namespace
     * @param string $name
     */
    public function __construct(?string $namespace = null, ?string $name = null)
    {
        $this->namespace = $namespace;
        $this->name = $name;
    }

    public function __serialize(): array
    {
        return [
            'name' => $this->getName(),
            'namespace' => $this->getNamespace(),
        ];
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
