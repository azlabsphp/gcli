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

namespace Drewlabs\GCli\Plugins\Laravel\Console;

use Closure;
use Drewlabs\GCli\Cache\Cache;
use Drewlabs\GCli\Cache\CacheableTables;
use Drewlabs\GCli\Console\Contracts\Progress;
use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\Contracts\Writable;
use Drewlabs\GCli\DBAL\R\Types;
use Drewlabs\GCli\HTr\RouteRequestBodyMap;
use Drewlabs\GCli\Plugins\G;
use Drewlabs\GCli\Plugins\Laravel\DBConfig;
use Drewlabs\GCli\Plugins\Laravel\Observers\Observers;
use Drewlabs\GCli\Plugins\Laravel\Routes;

use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;
use function Drewlabs\GCli\Proxy\MVCServiceProviderBuilder;

use Drewlabs\GCli\RouteControllerConfig;

final class Task
{
    /** @var bool */
    private $camelize = false;

    /** @var bool */
    private $policies = false;

    /**
     * Class constructor.
     */
    public function __construct() {}

    /**
     * By default from version 2.7.x model attribute attibutes are no more
     * converted to camel case representation. To make sure json representation
     * of attributes are in camelcase, this method must be invoked.
     *
     * @return self
     */
    public function setCamelize(bool $value = false)
    {
        $this->camelize = $value;

        return $this;
    }

    /**
     * Makes the task runner generate policy classes.
     *
     * @return self
     */
    public function withPolicies()
    {
        $this->policies = true;

        return $this;
    }

    /**
     * Creates a code generator factory function based on provided options.
     */
    public function run(
        DBConfig $dbConfig,
        string $directory,
        string $routingfilename,
        ?string $routePrefix = null,
        ?string $middleware = null,
        bool $forLumen = true,
        bool $disableCache = false,
        string $namespace = 'App',
        ?string $subPackage = null,
        bool $hasHttpHandlers = false,
        bool $withoutModelAccessors = true
    ) {
        return function (
            string $routesDirectory,
            string $cachePath,
            string $routesCachePath,
            callable $onStartCallback,
            ?callable $onCompleteCallback = null,
            ?callable $onExistsCallback = null,
            ?callable $createHTrProjectsCallback = null
        ) use (
            $dbConfig,
            $directory,
            $routingfilename,
            $routePrefix,
            $middleware,
            $forLumen,
            $disableCache,
            $namespace,
            $subPackage,
            $hasHttpHandlers,
            $withoutModelAccessors
        ) {
            $supportPolicies = $this->policies;
            $camelize = $this->camelize;
            $policies = [];
            $message = [];
            $bindings = [];

            $onCompleteCallback = $onCompleteCallback ?? static function () {
                printf("\nTask Completed successfully...\n");
            };

            // Execute the runner
            // # Create the migrations runner
            $values = $dbConfig->getTables();
            $pivots = $dbConfig->getPivots();

            if (!$disableCache) {
                Cache::new($cachePath)->dump(new CacheableTables(array_keys($values), $namespace, $subPackage));
            }

            /** @var Progress */
            $indicator = $onStartCallback($values);
            $requestBodyMap = new RouteRequestBodyMap();

            $routes = iterator_to_array((static function () use (
                $camelize,
                $values,
                $subPackage,
                $indicator,
                $pivots,
                $withoutModelAccessors,
                &$onExistsCallback,
                &$policies,
                &$bindings,
                &$requestBodyMap,
                $hasHttpHandlers,
                $supportPolicies
            ) {
                foreach ($values as $component) {
                    // #region Write model source code
                    $tableConfig = $component->getModelConfig();
                    $relations = $component->getRelations();

                    if (\in_array($component->getModelConfig()->getTable(), $pivots, true)) {
                        $tableConfig = $tableConfig->asPivot();
                    }

                    // disable accessor generator case providesModelAccessors is false
                    if ($withoutModelAccessors) {
                        $tableConfig = $tableConfig->withoutAccessors();
                    }

                    static::writeComponentSourceCode($tableConfig->getPath(), static::resolveWritable($tableConfig->getBuilder()), $onExistsCallback);

                    G::getInstance()->generate($component->getType());

                    // #region Write view model source code
                    $tableViewConfig = $component->getViewModelConfig();
                    $tableViewConfig = $tableViewConfig->setDtoClassPath($component->getDtoConfig()->getClassPath());

                    $viewmodelSourceCode = static::resolveWritable($tableViewConfig->getBuilder());
                    static::writeComponentSourceCode($tableViewConfig->getPath(), $viewmodelSourceCode, $onExistsCallback);
                    // #endregion Write view model source code

                    // #region Write service source code
                    $tableServiceConfig = $component->getServiceConfig();
                    $serviceSourceCode = static::resolveWritable($tableServiceConfig->getBuilder());

                    /** @var  (\Drewlabs\GCli\Contracts\SourceFileInterface&AbstractBuilder)|null */
                    $serviceTypeSourceCode = null;
                    $serviceType = $tableServiceConfig->getContract();
                    if ($serviceTypeSourceCode = static::resolveWritable($serviceType->getBuilder())) {
                        static::writeComponentSourceCode($serviceType->getPath(), $serviceTypeSourceCode, $onExistsCallback);
                    }
                    static::writeComponentSourceCode($tableServiceConfig->getPath(), $serviceSourceCode, $onExistsCallback);
                    if ($serviceTypeSourceCode !== null) {
                        $bindings[$serviceTypeSourceCode->getClassPath()] = sprintf("\%s", $serviceSourceCode->getClassPath());
                    }
                    // #endregion Write service source code

                    // #region Write DTO Component source code
                    $tableDtoConfig = $component->getDtoConfig();
                    $currentDtoCasts = [];
                    
                    /** @var \Drewlabs\GCli\DBAL\R\Through|\Drewlabs\GCli\DBAL\R\Basic $_current */
                    foreach ($relations as $_current) {
                        $currentDtoCasts[$_current->getName()] = \in_array(
                            $_current->getType(),
                            [Types::ONE_TO_MANY, Types::MANY_TO_MANY, Types::ONE_TO_MANY_THROUGH],
                            true
                        ) ? 'collectionOf:\\' . ltrim($_current->getCastClassPath(), '\\') : 'value:\\' . ltrim($_current->getCastClassPath(), '\\');
                    }
                    $tableDtoConfig = $tableDtoConfig->camelizeProperties($camelize)->setCasts($currentDtoCasts);
                    $dtoSourceCode = static::resolveWritable($tableDtoConfig->getBuilder());
                    static::writeComponentSourceCode($tableDtoConfig->getPath(), $dtoSourceCode, $onExistsCallback);
                    // #endregion Write DTO Component source code

                    if ($hasHttpHandlers) {
                        $controllerConfig = $component->getControllerConfig();
                        // Call the controller factory builder function with the required parameters
                        $controllersource = static::resolveWritable(
                            $controllerConfig->getBuilder(),
                            $serviceTypeSourceCode ? [$serviceSourceCode->getClassPath(), $serviceTypeSourceCode->getClassPath() ] : [$serviceSourceCode->getClassPath()],
                            $viewmodelSourceCode->getClassPath(),
                            $dtoSourceCode->getClassPath()
                        );
                        $name = $controllerConfig->getRouteNameBuilder()($controllersource);
                        $classPath = $controllerConfig->getClassPathBuilder()($controllersource);
                        static::writeComponentSourceCode($controllerConfig->getPath(), $controllersource, $onExistsCallback);
                        $routeController = new RouteControllerConfig($name, $subPackage, $classPath);
                        $requestBodyMap->put(
                            $name,
                            $tableViewConfig->getRules(),
                            $tableViewConfig->getUpdateRules(),
                            array_map(static function ($current) {
                                return sprintf('%s (%s)', $current->getName(), (string) $current);
                            }, $relations)
                        );
                        yield $name => $routeController;
                    }
                    if ($supportPolicies) {
                        $policyConfig = $component->getPolicyConfig();
                        static::writeComponentSourceCode($policyConfig->getPath(), static::resolveWritable($policyConfig->getBuilder()), $onExistsCallback);
                        $policies[sprintf("\%s", $tableConfig->getClassPath())] = sprintf("\%s", $policyConfig->getClassPath());
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
                    $subPackage,
                )($routes);
                // Once the routes are ready, we invoke function to create htr requests
                if ($createHTrProjectsCallback) {
                    $createHTrProjectsCallback($routes, $requestBodyMap, $routePrefix);
                }
                // create htr document for each route
                $indicator->advance();
            }

            // Case policies where generated, we creates a policy service provider class in the project
            if (!empty($policies) || !empty($bindings)) {
                $serviceProviderBuilder = MVCServiceProviderBuilder(
                    $policies,
                    $bindings,
                    sprintf('%s%s', $namespace, $subPackage ? trim(sprintf('\\%s', "$subPackage")) : '\\Providers'),
                    implode(\DIRECTORY_SEPARATOR, [$directory, $subPackage ? sprintf('%s', "$subPackage/") : 'Providers']),
                    $subPackage ? 'ServiceProvider' : null,
                );

                // Register domain routes case the route name is not web nor api
                if (!\in_array($routingfilename, ['api', 'web', 'api.php', 'web.php'], true)) {
                    $serviceProviderBuilder = $serviceProviderBuilder->withDomainRouting($routingfilename);
                }

                // Add observers events
                $serviceProviderBuilder = $serviceProviderBuilder->withEvents(Observers::getInstance()->getEvents());

                static::writeComponentSourceCode($directory, static::resolveWritable($serviceProviderBuilder), $onExistsCallback);
                $message = [sprintf("Please add [\%s::class] to the list of application service providers.\n", $serviceProviderBuilder->getClassPath())];
            }

            if (!empty($events = Observers::getInstance()->getEvents())) {
                foreach ($events as $e) {
                    if ($e->doesExists()) {
                        continue;
                    }
                    $eventBuilder = $e->getBuilder(implode(\DIRECTORY_SEPARATOR, [$directory, $subPackage ? sprintf('%s', "$subPackage/Events") : 'Events']));
                    $listenerBuilder = $e->getListener()->getBuilder(implode(\DIRECTORY_SEPARATOR, [$directory, $subPackage ? sprintf('%s', "$subPackage/Listeners") : 'Listeners']));
                    static::writeComponentSourceCode($directory, static::resolveWritable($eventBuilder), $onExistsCallback);
                    static::writeComponentSourceCode($directory, static::resolveWritable($listenerBuilder), $onExistsCallback);
                }
            }

            $indicator->finish();

            if ($onCompleteCallback instanceof \Closure) {
                $onCompleteCallback(implode(\PHP_EOL, $message));
            }
        };
    }

    /**
     * Write app routes to disk.
     *
     * @param (string|null)|null $routesDirectory
     * @param (string|null)|null $cachePath
     * @param (string|null)|null $routingfilename
     * @param (string|null)|null $prefix
     * @param (string|null)|null $middleware
     * @param (string|null)|null $subPackage
     *
     * @return \Closure(array): void
     */
    protected function writeRoutes(
        ?bool $disableCache,
        ?bool $lumen = false,
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
            $lumen,
            $routesDirectory,
            $routingfilename,
            $prefix,
            $middleware,
            $subPackage
        ) {
            if (!$disableCache) {
                // Get route definitions from cache
                $cachedRoutes = $cachePath ? Routes::getCachedRoutes($cachePath) : null;
                $routes = array_merge($routes, $cachedRoutes ? $cachedRoutes->getRoutes() : []);
            }
            $definitions = [];
            foreach ($routes as $key => $value) {
                // Call the route definitions creator function
                $definitions[$key] = Routes::for($key, $value)($lumen);
            }
            Routes::write($routesDirectory, $definitions, $routingfilename)(
                $lumen,
                $prefix,
                $middleware,
                static function () use ($routes, $disableCache, $cachePath, $subPackage) {
                    if (!$disableCache) {
                        // Add routes definitions to cache
                        Routes::cacheRoutes(
                            $cachePath,
                            $routes,
                            $subPackage
                        );
                    }
                }
            );
        };
    }

    /**
     * Write the source code for a given OOP component to disk.
     *
     * @param mixed $path
     *
     * @throws \Exception
     *
     * @return void
     */
    private static function writeComponentSourceCode($path, Writable $writable, ?callable $callback = null)
    {
        /** @var \Drewlabs\GCli\IO\ScriptWriter */
        $instance = ComponentsScriptWriter($path);
        if (!$instance->fileExists($writable)) {
            $instance->write($writable);
            return;
        }

        if ($callback && true === $callback($writable)) {
            $instance->write($writable);
        }
    }

    /**
     * Resolve writable instance.
     *
     * @param mixed $component
     * @param mixed $args
     *
     * @throws \RuntimeException
     *
     * @return (SourceFileInterface&Writable)|null
     */
    private static function resolveWritable($component, ...$args)
    {
        if ($component instanceof SourceFileInterface) {
            return $component;
        }
    
        if ($component instanceof AbstractBuilder) {
            return $component->build();
        }
    
        if (\is_callable($component)) {
            return $component(...$args);
        }

        throw new \RuntimeException('Unsupported type ' . ($component && \is_object($component) ? $component::class : \gettype($component)));
    }
}
