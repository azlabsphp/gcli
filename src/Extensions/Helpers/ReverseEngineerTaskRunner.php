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

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\DatabaseSchemaReverseEngineeringRunner;

class ReverseEngineerTaskRunner
{
    public function run(
        array $options,
        string $srcPath,
        string $routingfilename,
        ?string $routePrefix = null,
        ?string $middleware = null,
        ?array $exceptions = [],
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
            $exceptions,
            $forLumen,
            $disableCache,
            $noAuth,
            $namespace,
            $subPackage,
            $schema,
            $hasHttpHandlers
        ) {
            $onCompleteCallback = $onCompleteCallback ?? static function () {
                dump('Task Completed successfully...');
            };
            $connection = DriverManager::getConnection($options);
            $schemaManager = $connection->createSchemaManager();
            // For Mariadb server
            $schemaManager->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            // TODO : Create a table filtering function that removes drewlabs packages tables from
            // the generated tables
            $tablesFilterFunc = static function ($table) {
                return !(drewlabs_core_strings_contains($table->getName(), 'auth_') ||
                    drewlabs_core_strings_starts_with($table->getName(), 'acl_') ||
                    ('accounts_verifications' === $table->getName()) ||
                    drewlabs_core_strings_contains($table->getName(), 'file_authorization') ||
                    drewlabs_core_strings_contains($table->getName(), 'uploaded_file') ||
                    drewlabs_core_strings_contains($table->getName(), 'server_authorized_') ||
                    drewlabs_core_strings_contains($table->getName(), 'shared_files') ||
                    drewlabs_core_strings_contains($table->getName(), 'form_') ||
                    ('forms' === $table->getName()) ||
                    ('migrations' === $table->getName()) ||
                    (drewlabs_core_strings_starts_with($table->getName(), 'log_model_')));
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
                ->except($exceptions)
                ->setSchema($schema)
                ->run(static function ($tables) use ($namespace, $subPackage, $disableCache, $cachePath) {
                    if (!$disableCache) {
                        // TODO : Add definitions to cache
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
            $routes = iterator_to_array((function () use ($values, $subPackage, $indicator) {
                // TODO : IN FUTURE RELEASE BUILD MODEL RELATION METHOD
                foreach ($values as $component) {
                    ComponentsScriptWriter(drewlabs_core_array_get($component, 'model.path'))->write(drewlabs_core_array_get($component, 'model.class'));
                    ComponentsScriptWriter(drewlabs_core_array_get($component, 'viewModel.path'))->write(drewlabs_core_array_get($component, 'viewModel.class'));
                    ComponentsScriptWriter(drewlabs_core_array_get($component, 'service.path'))->write(drewlabs_core_array_get($component, 'service.class'));
                    if (null !== $controller = drewlabs_core_array_get($component, 'controller')) {
                        ComponentsScriptWriter(drewlabs_core_array_get($controller, 'dto.path'))->write(drewlabs_core_array_get($controller, 'dto.class'));
                        ComponentsScriptWriter(drewlabs_core_array_get($controller, 'path'))->write(drewlabs_core_array_get($controller, 'class'));
                        yield drewlabs_core_array_get($controller, 'route.name') => new RouteController(['namespace' => $subPackage, 'name' => drewlabs_core_array_get($controller, 'route.classPath')]);
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
        ?string $middleware,
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

            // TODO : Cache route definitions if cache is not disabled
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
            // TODO : Write the definitions to the route files
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
