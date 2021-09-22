<?php

namespace Drewlabs\ComponentGenerators\Extensions\Helpers;

use Doctrine\DBAL\DriverManager;
use Drewlabs\ComponentGenerators\Extensions\Contracts\Progress;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\ComponentGenerators\Helpers\RouteDefinitionsHelper;
use Drewlabs\ComponentGenerators\Models\RouteController;

use function Drewlabs\ComponentGenerators\Proxy\DatabaseSchemaReverseEngineeringRunner;

class ReverseEngineerTaskRunner
{
    public function run(
        array $options,
        string $srcPath,
        string $routingfilename,
        ?string $routePrefix = null,
        ?string $middleware = null,
        array $exceptions = [],
        bool $forLumen = true,
        bool $disableCache = false,
        bool $noAuth = false,
        ?string $namespace = null,
        ?string $subPackage = null
    ) {

        return function (
            string $routesDirectory,
            string $cachePath,
            string $routesCachePath,
            \Closure $onStartCallback,
            \Closure $onCompleteCallback = null
        )  use (
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
            $subPackage
        ) {
            $onCompleteCallback = $onCompleteCallback ?? function () {
                dump('Task Completed successfully...');
            };
            $connection = DriverManager::getConnection($options);
            $schemaManager =  $connection->createSchemaManager();
            // For Mariadb server
            $schemaManager->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            // TODO : Create a table filtering function that removes drewlabs packages tables from
            // the generated tables
            $tablesFilterFunc = function ($table) {
                return !(drewlabs_core_strings_contains($table->getName(), 'auth_') ||
                    drewlabs_core_strings_starts_with($table->getName(), 'acl_') ||
                    ($table->getName() === 'accounts_verifications') ||
                    drewlabs_core_strings_contains($table->getName(), 'file_authorization') ||
                    drewlabs_core_strings_contains($table->getName(), 'uploaded_file') ||
                    drewlabs_core_strings_contains($table->getName(), 'server_authorized_') ||
                    drewlabs_core_strings_contains($table->getName(), 'shared_files') ||
                    drewlabs_core_strings_contains($table->getName(), 'form_') ||
                    ($table->getName() === 'forms') ||
                    ($table->getName() === 'migrations') ||
                    (drewlabs_core_strings_starts_with($table->getName(), 'log_model_')));
            };
            // Execute the runner
            // # Create the migrations runner
            $runner = DatabaseSchemaReverseEngineeringRunner(
                $schemaManager,
                $srcPath,
                $namespace
            );
            // #endregion Create migration runner
            $traversable = ($noAuth ? $runner : $runner->withoutAuth())
                ->setSubNamespace($subPackage)
                ->bindExceptMethod($tablesFilterFunc)
                ->except($exceptions)
                ->run(function ($tables) use ($namespace, $subPackage, $disableCache, $cachePath) {
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

            $routes = iterator_to_array(
                (function () use ($traversable, $subPackage) {
                    foreach ($traversable as $key => $value) {
                        yield $key => new RouteController(['namespace' => $subPackage, 'name' => $value]);
                    }
                })()
            );
            // TODO : Cache route definitions if cache is not disabled
            if (!$disableCache) {
                // Get route definitions from cache
                $value = RouteDefinitionsHelper::getCachedRouteDefinitions($routesCachePath);
                if (null !== $value) {
                    $routes = array_merge($routes, $value->getRoutes());
                }
            }
            /**
             * @var Progress
             */
            $indicator = $onStartCallback($routes);
            $definitions = [];
            foreach ($routes as $key => $value) {
                // Call the route definitions creator function
                $definitions[$key] = RouteDefinitionsHelper::for(
                    $key,
                    $value
                )($forLumen);
                // TODO : Add the definitions to the route definitions array
                if ((null !== $indicator) && ($indicator instanceof Progress)) {
                    $indicator->advance();
                }
            }
            // dd($definitions);
            // TODO : Write the definitions to the route files
            RouteDefinitionsHelper::writeRouteDefinitions(
                $routesDirectory,
                $definitions,
                $routingfilename
            )(
                true,
                $routePrefix,
                $middleware,
                function () use ($routes, $disableCache, $routesCachePath, $subPackage) {
                    if (!$disableCache) {
                        // Add routes definitions to cache
                        RouteDefinitionsHelper::cacheRouteDefinitions(
                            $routesCachePath,
                            $routes,
                            $subPackage
                        );
                    }
                }
            );
            if ((null !== $indicator) && ($indicator instanceof Progress)) {
                $indicator->complete();
            }
            if (null !== $onCompleteCallback && ($onCompleteCallback instanceof \Closure)) {
                $onCompleteCallback();
            }
        };
    }
}
