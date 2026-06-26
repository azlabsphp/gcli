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

namespace Drewlabs\GCli;

use Drewlabs\GCli\Contracts\ControllerBuilder as Builder;
use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\Factories\RouteName;

final class ControllerConfig
{
    /** @var (\Closure():SourceFileInterface)|SourceFileInterface */
    private $builder;

    /** @var string */
    private $path;

    /**
     * Class constructor.
     *
     * @param (\Closure():SourceFileInterface)|SourceFileInterface $builder
     */
    public function __construct(\Closure|SourceFileInterface $builder, string $directory)
    {
        $this->path = $directory;
        $this->builder = $builder;
    }

    /**
     * return the builder instance.
     *
     * @return (\Closure(): SourceFileInterface)|SourceFileInterface
     */
    public function getBuilder(): \Closure|SourceFileInterface
    {
        return $this->builder;
    }

    /**
     * return the path where instance source code must be generated.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getRouteNameBuilder()
    {
        return static function ($controller) {
            return $controller instanceof Builder ? $controller->routeName() : RouteName::new()->createRouteName($controller->getName());
        };
    }

    public function getClassPathBuilder()
    {
        return static function (SourceFileInterface $controller) {
            return $controller->getClassPath();
        };
    }
}
