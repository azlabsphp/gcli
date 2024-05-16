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

namespace Drewlabs\GCli\Extensions\Helpers;

use Closure;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\GCli\ComponentsScriptWriter as ComponentsScriptWriterClass;
use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\GCli\Contracts\ProvidesPropertyAccessors;
use Drewlabs\GCli\Contracts\ProvidesRelations;
use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\Contracts\Writable;
use Drewlabs\GCli\Extensions\Contracts\Progress;
use Drewlabs\GCli\Extensions\Traits\ReverseEngineerRelations;
use Drewlabs\GCli\Helpers\ComponentBuilder;
use Drewlabs\GCli\Helpers\RouteDefinitions;
use Drewlabs\GCli\HTr\RouteRequestBodyMap;
use Drewlabs\GCli\Models\RouteController;
use Drewlabs\GCli\ModulesIteratorFactory;
use Drewlabs\GCli\Plugins\G;

use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;

use function Drewlabs\GCli\Proxy\MVCServiceProviderBuilder;

use Drewlabs\GCli\RelationTypes;
use Drewlabs\GCli\Validation\RulesFactory;

class ReverseEngineerTask
{
    use ReverseEngineerRelations;

    /**
     * @var string[]
     */
    private $exceptions = [];

    /**
     * @var string[]
     */
    private $tables = [];

    /**
     * @var bool
     */
    private $camelize = false;

    /**
     * @var string[]
     */
    private $oneThroughs = [];

    /**
     * @var string[]
     */
    private $manyThroughs = [];

    /**
     * @var string[]
     */
    private $oneToOnes = [];

    /**
     * @var string[]
     */
    private $manyToMany = [];

    /**
     * @var bool
     */
    private $provideRelations = false;

    /**
     * Defines if policy classes must be generated.
     *
     * @var bool
     */
    private $policies = false;

    /** @var RulesFactory */
    private $rulesFactory;


    /**
     * Class constructor
     * 
     * @param RulesFactory $rulesFactory 
     */
    public function __construct(RulesFactory $rulesFactory)
    {
        $this->rulesFactory = $rulesFactory;
    }

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
     * Set the one through relations used when generating lazy load model
     * for model class definitions.
     *
     * @return self
     */
    public function setOnThroughRelations(array $values = [])
    {
        $this->oneThroughs = $values;

        return $this;
    }

    /**
     * Set the many through relations used when generating lazy load model
     * for model class definitions.
     *
     * @return self
     */
    public function setManyThroughRelations(array $values = [])
    {
        $this->manyThroughs = $values;

        return $this;
    }

    /**
     * Set the one to one relations used when generating lazy load model
     * for model class definitions.
     *
     * @return self
     */
    public function setToOnesRelations(array $values = [])
    {
        $this->oneToOnes = $values;

        return $this;
    }

    /**
     * Set the many to many relations used when generating lazy load model
     * for model class definitions.
     *
     * @return self
     */
    public function setManyToManyRelations(array $values = [])
    {
        $this->manyToMany = $values;

        return $this;
    }

    /**
     * Set a property that insure model relations are generated.
     *
     * @return self
     */
    public function withRelations()
    {
        $this->provideRelations = true;

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
        \Traversable $traversable,
        string $src,
        string $routingfilename,
        string $routePrefix = null,
        string $middleware = null,
        bool $forLumen = true,
        bool $disableCache = false,
        bool $noAuth = false,
        string $namespace = 'App',
        string $subPackage = null,
        string $schema = null,
        bool $hasHttpHandlers = false,
        bool $disableModelAccessors = true
    ) {
        return function (
            string $routesDirectory,
            string $cachePath,
            string $routesCachePath,
            callable $onStartCallback,
            callable $onCompleteCallback = null,
            callable $onExistsCallback = null,
            callable $createHTrProjectsCallback = null,
            callable $pluginsCallback = null
        ) use (
            $traversable,
            $src,
            $routingfilename,
            $routePrefix,
            $middleware,
            $forLumen,
            $disableCache,
            $noAuth,
            $namespace,
            $subPackage,
            $schema,
            $hasHttpHandlers,
            $disableModelAccessors
        ) {
            $providesRelations = $this->provideRelations;
            $toones = $this->oneToOnes ?? [];
            $manytomany = $this->manyToMany ?? [];
            $onethroughs = $this->oneThroughs ?? [];
            $manythroughs = $this->manyThroughs ?? [];
            $camelize = $this->camelize;
            $policies = [];
            $message = [];
            $bindings = [];

            $onCompleteCallback = $onCompleteCallback ?? static function () {
                printf("\nTask Completed successfully...\n");
            };

            // Execute the runner
            // # Create the migrations runner
            $modulesFactory = new ModulesIteratorFactory($this->rulesFactory, $src, $namespace);
            $modulesFactory = $modulesFactory->setDomain($subPackage)->setSchema($schema);
            if ($hasHttpHandlers) {
                $modulesFactory = $modulesFactory->withHttpHandlers();
            }

            if ($this->policies) {
                $modulesFactory = $modulesFactory->withPolicies();
            }

            if ($noAuth) {
                $modulesFactory = $modulesFactory->withoutAuth();
            }

            /** @var string[] */
            $tableNames = [];
            /** @var ForeignKeyConstraintDefinition[] */
            $foreignKeys = [];
            /** @var array<string,int> */
            $tablesindexes = [];

            // #endregion Create migration runner
            $iterator = $modulesFactory->createModulesIterator($traversable, $foreignKeys, $tablesindexes, $tableNames);
            $values = iterator_to_array($iterator);
            //#region write tables to cache if caching is not disabled
            if (!$disableCache) {
                ComponentBuilder::cacheComponentDefinitions($cachePath, $tableNames, $namespace, $subPackage);
            }
            //#endregion write tables to cache if caching is not disabled
            /**
             * @var Progress
             */
            $indicator = $onStartCallback($values);
            // #region Create components models relations
            [$relations, $pivots] = $providesRelations ? self::resolveRelations(
                $values,
                $tablesindexes,
                $foreignKeys,
                $manytomany,
                $toones,
                $manythroughs,
                $onethroughs,
                $schema
            ) : [[], []];
            $requestBodyMap = new RouteRequestBodyMap();
            // #endregion Create components models relations
            $routes = iterator_to_array((static function () use (
                $camelize,
                $values,
                $subPackage,
                $indicator,
                $relations,
                $pivots,
                $disableModelAccessors,
                &$onExistsCallback,
                &$policies,
                &$bindings,
                &$requestBodyMap
            ) {
                foreach ($values as $component) {
                    // #region Write model source code
                    $modelbuilder = Arr::get($component, 'model.class');
                    if (($modelbuilder instanceof ProvidesRelations) && \is_array($componentrelations = $relations[Arr::get($component, 'model.classPath')] ?? [])) {
                        $modelbuilder = $modelbuilder->provideRelations($componentrelations);
                        if (\in_array(Arr::get($component, 'table'), $pivots, true)) {
                            $modelbuilder = $modelbuilder->asPivot();
                        }
                    }

                    // disable accessor generator case providesModelAccessors is false
                    if ($modelbuilder instanceof ProvidesPropertyAccessors && $disableModelAccessors) {
                        $modelbuilder = $modelbuilder->withoutAccessors();
                    }

                    static::writeComponentSourceCode(Arr::get($component, 'model.path'), self::resolveWritable($modelbuilder), $onExistsCallback);
                    // #endregion Write model source code

                    // Use plugin code generator
                    /** @var \Drewlabs\GCli\Contracts\Type $type */
                    if (!is_null($type = Arr::get($component, 'model.definition'))) {
                        G::getInstance()->generate($type);
                    }

                    // #region Write view model source code
                    $viewmodelbuilder = Arr::get($component, 'viewModel.class');
                    if ($dtoBuilder = Arr::get($component, 'dto.class')) {
                        $viewmodelbuilder = $viewmodelbuilder->setDTOClassPath($dtoBuilder->getClassPath());
                    }
                    $viewmodelSourceCode = self::resolveWritable($viewmodelbuilder);
                    static::writeComponentSourceCode(Arr::get($component, 'viewModel.path'), $viewmodelSourceCode, $onExistsCallback);
                    // #endregion Write view model source code

                    // #region Write service source code
                    $serviceSourceCode = self::resolveWritable(Arr::get($component, 'service.class'));
                    if ((null !== ($serviceType = Arr::get($component, 'service.type.class'))) && $serviceTypeSourceCode = self::resolveWritable($serviceType)) {
                        static::writeComponentSourceCode(Arr::get($component, 'service.type.path'), $serviceTypeSourceCode, $onExistsCallback);
                    }
                    static::writeComponentSourceCode(Arr::get($component, 'service.path'), $serviceSourceCode, $onExistsCallback);
                    $bindings[$serviceTypeSourceCode->getClassPath()] = sprintf("\%s", $serviceSourceCode->getClassPath());
                    // #endregion Write service source code

                    // #region Write DTO Component source code
                    if (\is_array($componentrelations) && method_exists($dtoBuilder, 'setCasts')) {
                        $currentDtoCasts = [];
                        foreach ($componentrelations as $_current) {
                            $currentDtoCasts[$_current->getName()] = \in_array(
                                $_current->getType(),
                                [
                                    RelationTypes::ONE_TO_MANY,
                                    RelationTypes::MANY_TO_MANY,
                                    RelationTypes::ONE_TO_MANY_THROUGH,
                                ],
                                true
                            ) ?
                                'collectionOf:\\' . ltrim($_current->getCastClassPath(), '\\') :
                                'value:\\' . ltrim($_current->getCastClassPath(), '\\');
                        }
                        $dtoBuilder->setCamelizeProperties($camelize)->setCasts($currentDtoCasts);
                    }
                    $dtoSourceCode = self::resolveWritable($dtoBuilder);
                    static::writeComponentSourceCode(Arr::get($component, 'dto.path'), $dtoSourceCode, $onExistsCallback);
                    // #endregion Write DTO Component source code

                    if (null !== $controller = Arr::get($component, 'controller')) {
                        // Call the controller factory builder function with the required parameters
                        $controllersource = self::resolveWritable(
                            Arr::get($controller, 'class'),
                            [
                                $serviceSourceCode->getClassPath(),
                                $serviceTypeSourceCode->getClassPath(),
                            ],
                            $viewmodelSourceCode->getClassPath(),
                            $dtoSourceCode->getClassPath()
                        );
                        $name = \is_callable($nameBuilder = Arr::get($controller, 'route.nameBuilder')) ? $nameBuilder($controllersource) : Arr::get($controller, 'route.name');
                        $classPath = \is_callable($classPathBuilder = Arr::get($controller, 'route.classPathBuilder')) ? $classPathBuilder($controllersource) : Arr::get($controller, 'route.classPath');
                        static::writeComponentSourceCode(Arr::get($controller, 'path'), $controllersource, $onExistsCallback);
                        $routeController = new RouteController($name, $subPackage, $classPath);
                        $requestBodyMap->put(
                            $name,
                            $viewmodelbuilder->getRules(),
                            $viewmodelbuilder->getUpdateRules(),
                            array_map(static function ($current) {
                                return sprintf('%s (%s)', $current->getName(), (string) $current);
                            }, \is_array($componentrelations) ? $componentrelations : [$componentrelations])
                        );
                        yield $name => $routeController;
                    }
                    if (null !== ($policy = Arr::get($component, 'policy'))) {
                        $policyBuilder = Arr::get($policy, 'class');
                        if ($policyBuilder) {
                            static::writeComponentSourceCode(Arr::get($policy, 'path'), self::resolveWritable($policyBuilder), $onExistsCallback);
                            $policies[sprintf("\%s", Arr::get($component, 'model.classPath'))] = sprintf("\%s", Arr::get($component, 'policy.classPath'));
                        }
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
                    implode(\DIRECTORY_SEPARATOR, [$src, $subPackage ? sprintf('%s', "$subPackage/") : 'Providers']),
                    $subPackage ? 'ServiceProvider' : null,
                );

                // Register domain routes case the route name is not web nor api
                if (!in_array($routingfilename, ['api', 'web', 'api.php', 'web.php'])) {
                    $serviceProviderBuilder = $serviceProviderBuilder->withDomainRouting($routingfilename);
                }
                static::writeComponentSourceCode($src, self::resolveWritable($serviceProviderBuilder), $onExistsCallback);
                $message = [sprintf("Please add [\%s::class] to the list of application service providers.\n", $serviceProviderBuilder->getClassPath())];
            }
            if ((null !== $indicator) && ($indicator instanceof Progress)) {
                $indicator->complete();
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
        string $routesDirectory = null,
        string $cachePath = null,
        string $routingfilename = null,
        string $prefix = null,
        string $middleware = null,
        string $subPackage = null
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
                $cachedRoutes = $cachePath ? RouteDefinitions::getCachedRoutes($cachePath) : null;
                $routes = array_merge($routes, $cachedRoutes ? $cachedRoutes->getRoutes() : []);
            }
            $definitions = [];
            foreach ($routes as $key => $value) {
                // Call the route definitions creator function
                $definitions[$key] = RouteDefinitions::for($key, $value)($lumen);
            }
            RouteDefinitions::writeRoutes($routesDirectory, $definitions, $routingfilename, $partial)(
                $lumen,
                $prefix,
                $middleware,
                static function () use ($routes, $disableCache, $cachePath, $subPackage) {
                    if (!$disableCache) {
                        // Add routes definitions to cache
                        RouteDefinitions::cacheRoutes(
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
    private static function writeComponentSourceCode($path, Writable $writable, callable $callback = null)
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
