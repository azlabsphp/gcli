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

use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\Factories\RouteName;
use Drewlabs\GCli\Helpers\ComponentBuilder;
use Drewlabs\GCli\Contracts\ControllerBuilder  as Builder;

final class TableControllerConfig
{
    /** @var \Closure(...$args): Builder */
    private $builder;

    /** @var string */
    private $path;

    /**
     * Class constructor
     * 
     * @param string $model 
     * @param string $directory 
     * @param string $primaryKey 
     * @param bool $authenticate 
     * @param bool $authorizable 
     * @param string|null $domain 
     * @param string $namespace 
     * @return void 
     */
    public function __construct(
        string $model,
        string $directory,
        string $domain = null,
        string $namespace = 'App',
        string $primaryKey = 'id',
        bool $authenticate = false,
        bool $authorizable = false,
    ) {
        $this->path = $directory;
        $this->builder = $this->createBuilder($model, $namespace, $domain, $authenticate, $authorizable, $primaryKey ?? 'id');
    }


    /**
     * return the builder instance
     * 
     * @return Builder 
     */
    public function getBuilder(): \Closure
    {
        return $this->builder;
    }


    /**
     * return the path where instance source code must be generated
     * 
     * @return string 
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

    /**
     * Creates a factory method that create the controller script.
     *
     * @return Closure(mixed $service = null, mixed $viewModel = null, mixed $dtoObject = null): SourceFileInterface
     */
    private function createBuilder(
        string $model = null,
        string $namespace = 'App',
        string $domain = null,
        bool $authenticate = false,
        bool $authorizable = false,
        string $key = 'id'
    ) {
        return function ($service = null, $view = null, $dto = null) use ($model, $authenticate, $authorizable, $key, $namespace, $domain) {
            return ComponentBuilder::buildController(
                $model,
                $service ?? null,
                $view ?? null,
                $dto ?? null,
                null,
                sprintf('%s\\%s', $namespace ?? 'App', sprintf('%s%s', 'Http\\Controllers', $domain ? "\\$domain" : '')),
                $authenticate,
                $authorizable,
                $key
            );
        };
    }
}
