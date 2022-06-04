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

namespace Drewlabs\ComponentGenerators\Extensions\Helpers;

use Doctrine\DBAL\DriverManager;
use Drewlabs\ComponentGenerators\Extensions\Contracts\Progress;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\ComponentGenerators\Helpers\RouteDefinitionsHelper;
use Drewlabs\ComponentGenerators\Models\RouteController;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Str;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\DatabaseSchemaReverseEngineeringRunner;

class ReverseEngineerTaskRunner
{
    /**
     * @var string[]
     */
    private $exceptions = [];

    /**
     * @var string[]
     */
    private $tables = [];

    /**
     * Specifies the tables for which code should be generated.
     *
     * @return self
     */
    public function only(array $tables)
    {
        $this->tables = $tables;

        return $this;
    }

    /**
     * Add exception to some tables during code generation.
     *
     * @return self
     */
    public function except(array $tables)
    {
        $this->exceptions = $tables;

        return $this;
    }

    /**
     * @return \Closure
     */
    public function run(
        array $options,
        string $srcPath,
        string $routingfilename,
        ?string $routePrefix = null,
        ?string $middleware = null,
        ?bool $forLumen = true,
        ?bool $disableCache = false,
        ?bool $noAuth = false,
        ?string $namespace = null,
        ?string $subPackage = null,
        ?string $schema = null,
        ?bool $hasHttpHandlers = false
    ) {
        return function (
            string $routesDirectory,
            string $cachePath,
            string $routesCachePath,
            \Closure $onStartCallback,
            ?\Closure $onCompleteCallback = null
        ) use (
            $options,
            $srcPath,
            $routingfilename,
            $routePrefix,
            $middleware,
            $forLumen,
            $disableCache,
            $noAuth,
            $namespace,
            $subPackage,
            $schema,
            $hasHttpHandlers
        ) {
            $onCompleteCallback = $onCompleteCallback ?? static function () {
                printf("\nTask Completed successfully...\n");
            };
            $connection = DriverManager::getConnection($options);
            $schemaManager = $connection->createSchemaManager();
            // For Mariadb server
            $schemaManager->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            // TODO : Create a table filtering function that removes drewlabs packages tables from
            // the generated tables
            $tablesFilterFunc = static function ($table) {
                return !(Str::contains($table->getName(), 'auth_') ||
                    Str::startsWith($table->getName(), 'acl_') ||
                    ('accounts_verifications' === $table->getName()) ||
                    Str::contains($table->getName(), 'file_authorization') ||
                    Str::contains($table->getName(), 'uploaded_file') ||
                    Str::contains($table->getName(), 'server_authorized_') ||
                    Str::contains($table->getName(), 'shared_files') ||
                    Str::contains($table->getName(), 'form_') ||
                    ('forms' === $table->getName()) ||
                    ('migrations' === $table->getName()) ||
                    (Str::startsWith($table->getName(), 'log_model_')));
            };
            // Execute the runner
            // # Create the migrations runner
            $runner = DatabaseSchemaReverseEngineeringRunner(
                $schemaManager,
                $srcPath,
                $namespace
            );
            if ($hasHttpHandlers) {
                $runner = $runner->withHttpHandlers();
            }

            if ($noAuth) {
                $runner = $runner->withoutAuth();
            }
            // #endregion Create migration runner
            $traversable = $runner->setSubNamespace($subPackage)
                ->bindExceptMethod($tablesFilterFunc)
                ->only($this->tables ?? [])
                ->except($this->exceptions ?? [])
                ->setSchema($schema)
                ->run(static function ($tables) use ($namespace, $subPackage, $disableCache, $cachePath) {
                    if (!$disableCache) {
                        ComponentBuilderHelpers::cacheComponentDefinitions(
                            $cachePath,
                            $tables,
                            $namespace,
                            $subPackage
                        );
                    }
                });
            $values = iterator_to_array($traversable);
            /**
             * @var Progress
             */
            $indicator = $onStartCallback($values);
            $routes = iterator_to_array((static function () use ($values, $subPackage, $indicator) {
                // TODO : IN FUTURE RELEASE BUILD MODEL RELATION METHOD
                foreach ($values as $component) {
                    ComponentsScriptWriter(Arr::get($component, 'model.path'))->write(Arr::get($component, 'model.class'));
                    ComponentsScriptWriter(Arr::get($component, 'viewModel.path'))->write(Arr::get($component, 'viewModel.class'));
                    ComponentsScriptWriter(Arr::get($component, 'service.path'))->write(Arr::get($component, 'service.class'));
                    if (null !== $controller = Arr::get($component, 'controller')) {
                        ComponentsScriptWriter(Arr::get($controller, 'dto.path'))->write(Arr::get($controller, 'dto.class'));
                        ComponentsScriptWriter(Arr::get($controller, 'path'))->write(Arr::get($controller, 'class'));
                        yield Arr::get($controller, 'route.name') => new RouteController(['namespace' => $subPackage, 'name' => Arr::get($controller, 'route.classPath')]);
                    }
                    $indicator->advance();
                }
            })());
            if ($hasHttpHandlers) {
                $this->writeRoutes(
                    $disableCache,
                    $forLumen,
                    $routesDirectory,
                    $routesCachePath,
                    $routingfilename,
                    $routePrefix,
                    $middleware,
                    $subPackage
                )($routes);
                $indicator->advance();
            }
            if ((null !== $indicator) && ($indicator instanceof Progress)) {
                $indicator->complete();
            }
            if (null !== $onCompleteCallback && ($onCompleteCallback instanceof \Closure)) {
                $onCompleteCallback();
            }
        };
    }

    protected function writeRoutes(
        ?bool $disableCache,
        ?bool $forLumen = false,
        ?string $routesDirectory = null,
        ?string $cachePath = null,
        ?string $routingfilename = null,
        ?string $prefix = null,
        ?string $middleware = null,
        ?string $subPackage = null
    ) {
        return static function (array $routes = []) use (
            $disableCache,
            $cachePath,
            $forLumen,
            $routesDirectory,
            $routingfilename,
            $prefix,
            $middleware,
            $subPackage
        ) {
            if (!$disableCache) {
                // Get route definitions from cache
                $value = $cachePath ? RouteDefinitionsHelper::getCachedRouteDefinitions($cachePath) : null;
                $routes = array_merge($routes, $value ? $value->getRoutes() : []);
            }
            $definitions = [];
            foreach ($routes as $key => $value) {
                // Call the route definitions creator function
                $definitions[$key] = RouteDefinitionsHelper::for(
                    $key,
                    $value
                )($forLumen);
            }
            RouteDefinitionsHelper::writeRouteDefinitions(
                $routesDirectory,
                $definitions,
                $routingfilename
            )(
                $forLumen,
                $prefix,
                $middleware,
                static function () use ($routes, $disableCache, $cachePath, $subPackage) {
                    if (!$disableCache) {
                        // Add routes definitions to cache
                        RouteDefinitionsHelper::cacheRouteDefinitions(
                            $cachePath,
                            $routes,
                            $subPackage
                        );
                    }
                }
            );
        };
    }
}
