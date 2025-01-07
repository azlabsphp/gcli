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
use Drewlabs\GCli\ScriptWriter as ComponentsScriptWriterClass;
use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Contracts\Pivotable;
use Drewlabs\GCli\Contracts\ProvidesPropertyAccessors;
use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\Contracts\Writable;
use Drewlabs\GCli\DBAL\R\Types;
use Drewlabs\GCli\DBConfig;
use Drewlabs\GCli\HTr\RouteRequestBodyMap;
use Drewlabs\GCli\RouteControllerConfig;
use Drewlabs\GCli\Plugins\G;
use Drewlabs\GCli\Plugins\Laravel\Routes;

use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;
use function Drewlabs\GCli\Proxy\MVCServiceProviderBuilder;

class Task
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
     *
     * @return Closure(string $routesDirectory, string $routesCachePath, Closure $onStartCallback, null|\Closure($policies) $onCompleteCallback = null, null|Closure $onExistsCallback = null, null|Closure $createHTrProjectsCallback = null): void
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

            // #region write tables to cache if caching is not disabled
            if (!$disableCache) {
                Cache::new($cachePath)->dump(new CacheableTables(array_keys($values), $namespace, $subPackage));
            }
            // #endregion write tables to cache if caching is not disabled

            /** @var Progress */
            $indicator = $onStartCallback($values);


            // #region Create components models relations
            $requestBodyMap = new RouteRequestBodyMap();
            // #endregion Create components models relations
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
                    $tableConfig = $component->getTableConfig();
                    $relations = $component->getRelations();

                    if ($tableConfig instanceof Pivotable && \in_array($component->getTableConfig()->getTable(), $pivots, true)) {
                        $tableConfig = $tableConfig->asPivot();
                    }

                    // disable accessor generator case providesModelAccessors is false
                    if ($tableConfig instanceof ProvidesPropertyAccessors && $withoutModelAccessors) {
                        $tableConfig = $tableConfig->withoutAccessors();
                    }

                    static::writeComponentSourceCode($tableConfig->getPath(), self::resolveWritable($tableConfig->getBuilder()), $onExistsCallback);
                    // #endregion Write model source code

                    // Use plugin code generator
                    /** @var \Drewlabs\GCli\Contracts\Type $type */
                    if (null !== ($type = $component->getType())) {
                        G::getInstance()->generate($type);
                    }

                    // #region Write view model source code
                    $tableViewConfig = $component->getTableViewConfig();
                    if ($tableDtoConfig = $component->getTableDtoConfig()) {
                        $tableViewConfig = $tableViewConfig->setDtoClassPath($tableDtoConfig->getClassPath());
                    }

                    $viewmodelSourceCode = self::resolveWritable($tableViewConfig->getBuilder());
                    static::writeComponentSourceCode($tableViewConfig->getPath(), $viewmodelSourceCode, $onExistsCallback);
                    // #endregion Write view model source code

                    // #region Write service source code
                    $tableServiceConfig = $component->getTableServiceConfig();
                    $serviceSourceCode = self::resolveWritable($tableServiceConfig->getBuilder());
                    if ((null !== ($serviceType = $tableServiceConfig->getContract())) && $serviceTypeSourceCode = self::resolveWritable($serviceType->getBuilder())) {
                        static::writeComponentSourceCode($serviceType->getPath(), $serviceTypeSourceCode, $onExistsCallback);
                    }
                    static::writeComponentSourceCode($tableServiceConfig->getPath(), $serviceSourceCode, $onExistsCallback);
                    $bindings[$serviceTypeSourceCode->getClassPath()] = sprintf("\%s", $serviceSourceCode->getClassPath());
                    // #endregion Write service source code

                    // #region Write DTO Component source code
                    $tableDtoConfig = $component->getTableDtoConfig();
                    $currentDtoCasts = [];
                    foreach ($relations as $_current) {
                        $currentDtoCasts[$_current->getName()] = \in_array(
                            $_current->getType(),
                            [Types::ONE_TO_MANY, Types::MANY_TO_MANY, Types::ONE_TO_MANY_THROUGH],
                            true
                        ) ?
                            'collectionOf:\\' . ltrim($_current->getCastClassPath(), '\\') :
                            'value:\\' . ltrim($_current->getCastClassPath(), '\\');
                    }
                    $tableDtoConfig = $tableDtoConfig->camelizeProperties($camelize)->setCasts($currentDtoCasts);
                    $dtoSourceCode = self::resolveWritable($tableDtoConfig->getBuilder());
                    static::writeComponentSourceCode($tableDtoConfig->getPath(), $dtoSourceCode, $onExistsCallback);
                    // #endregion Write DTO Component source code

                    if ($hasHttpHandlers) {
                        $controllerConfig = $component->getTableControllerConfig();
                        // Call the controller factory builder function with the required parameters
                        $controllersource = self::resolveWritable(
                            $controllerConfig->getBuilder(),
                            [
                                $serviceSourceCode->getClassPath(),
                                $serviceTypeSourceCode->getClassPath(),
                            ],
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
                            }, \is_array($relations) ? $relations : [$relations])
                        );
                        yield $name => $routeController;
                    }
                    if ($supportPolicies) {
                        $policyConfig = $component->getTablePolicyConfig();
                        static::writeComponentSourceCode($policyConfig->getPath(), self::resolveWritable($policyConfig->getBuilder()), $onExistsCallback);
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
                    // In case the generator is running for specific tables,
                    // generated routes, consider appending the new table routes
                    // to existing routes
                )($routes, !empty($this->tables));
                // Once the routes are ready, we invoke function to create htr requests
                if ($createHTrProjectsCallback) {
                    $createHTrProjectsCallback($routes, $requestBodyMap, $routePrefix);
                }
                // create htr document for each route
                $indicator->advance();
            }

            // Case policies where generated, we creates a policy service provider class in the project
            if (\is_array($policies) && !empty($policies) || (\is_array($bindings) && !empty($bindings))) {
                $serviceProviderBuilder = MVCServiceProviderBuilder(
                    $policies ?? [],
                    $bindings ?? [],
                    sprintf('%s%s', $namespace, $subPackage ? trim(sprintf('\\%s', "$subPackage")) : ''),
                    implode(\DIRECTORY_SEPARATOR, [$directory, $subPackage ? sprintf('%s', "$subPackage/") : 'Providers']),
                    $subPackage ? 'ServiceProvider' : null,
                );

                // Register domain routes case the route name is not web nor api
                if (!\in_array($routingfilename, ['api', 'web', 'api.php', 'web.php'], true)) {
                    $serviceProviderBuilder = $serviceProviderBuilder->withDomainRouting($routingfilename);
                }
                static::writeComponentSourceCode($directory, self::resolveWritable($serviceProviderBuilder), $onExistsCallback);
                $message = [sprintf("Please add [\%s::class] to the list of application service providers.\n", $serviceProviderBuilder->getClassPath())];
            }
            if ((null !== $indicator) && ($indicator instanceof Progress)) {
                $indicator->finish();
            }

            if (null !== $onCompleteCallback && ($onCompleteCallback instanceof \Closure)) {
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
     * @return Closure(array $routes = [], bool $partial = false): void
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
        return static function (array $routes = [], bool $partial = false) use (
            $disableCache,
            $cachePath,
            $lumen,
            $routesDirectory,
            $routingfilename,
            $prefix,
            $middleware,
            $subPackage
        ) {
            if (!$disableCache && !$partial) {
                // Get route definitions from cache
                $cachedRoutes = $cachePath ? Routes::getCachedRoutes($cachePath) : null;
                $routes = array_merge($routes, $cachedRoutes ? $cachedRoutes->getRoutes() : []);
            }
            $definitions = [];
            foreach ($routes as $key => $value) {
                // Call the route definitions creator function
                $definitions[$key] = Routes::for($key, $value)($lumen);
            }
            Routes::write($routesDirectory, $definitions, $routingfilename, $partial)(
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
        /**
         * @var ComponentsScriptWriterClass
         */
        $instance = ComponentsScriptWriter($path);
        if (!$instance->fileExists($writable)) {
            return $instance->write($writable);
        }
        if (!isset($callback) || (isset($callback) && true === $callback($writable))) {
            return $instance->write($writable);
        }
    }

    /**
     * Resolve writable instance.
     *
     * @param Writable|AbstractBuilder|\Closure(...$args):SourceFileInterface $component
     * @param mixed $args
     *
     * @throws RuntimeExWritableception
     *
     * @return SourceFileInterface
     */
    private static function resolveWritable($component, ...$args)
    {
        if ($component instanceof Writable) {
            return $component;
        }
        if ($component instanceof AbstractBuilder) {
            return $component->build();
        }
        if (\is_callable($component)) {
            return $component(...$args);
        }

        throw new \RuntimeException('Unsupported type ' . (\is_object($component) && null !== $component ? $component::class : \gettype($component)));
    }
}
