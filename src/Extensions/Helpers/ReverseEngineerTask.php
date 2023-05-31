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

namespace Drewlabs\GCli\Extensions\Helpers;

use Closure;
use Doctrine\DBAL\DriverManager;
use Drewlabs\GCli\ComponentsScriptWriter as ComponentsScriptWriterClass;
use Drewlabs\GCli\Contracts\ComponentBuilder;
use Drewlabs\GCli\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\GCli\Contracts\ProvidesRelations;
use Drewlabs\GCli\Contracts\Writable;
use Drewlabs\GCli\Extensions\Contracts\Progress;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;
use Drewlabs\GCli\Helpers\RouteDefinitionsHelper;
use Drewlabs\GCli\Models\RouteController;
use Drewlabs\GCli\Builders\DataTransfertClassBuilder;
use Drewlabs\GCli\Builders\ViewModelClassBuilder;
use Drewlabs\GCli\Extensions\Traits\ReverseEngineerRelations;
use Drewlabs\GCli\RelationTypes;

use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;
use function Drewlabs\GCli\Proxy\DatabaseSchemaReverseEngineeringRunner;
use function Drewlabs\GCli\Proxy\MVCPolicyServiceProviderBuilder;

use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Str;
use RuntimeException;

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
     * 
     * @var string[]
     */
    private $oneThroughs = [];

    /**
     * 
     * @var string[]
     */
    private $manyThroughs = [];

    /**
     * 
     * @var string[]
     */
    private $oneToOnes = [];

    /**
     * 
     * @var string[]
     */
    private $manyToMany = [];

    /**
     * 
     * @var bool
     */
    private $provideRelations = false;

    /**
     * Defines if policy classes must be generated
     * 
     * @var bool
     */
    private $policies = false;

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
     * By default from version 2.7.x model attribute attibutes are no more
     * converted to camel case representation. To make sure json representation
     * of attributes are in camelcase, this method must be invoked
     * 
     * @param bool $value 
     * @return self 
     */
    public function setCamelize(bool $value = false)
    {
        $this->camelize = $value;

        return $this;
    }

    /**
     * Set the one through relations used when generating lazy load model
     * for model class definitions
     * 
     * @param array $values 
     * @return self 
     */
    public function setOnThroughRelations(array $values = [])
    {
        $this->oneThroughs = $values;

        return $this;
    }

    /**
     * Set the many through relations used when generating lazy load model
     * for model class definitions
     * 
     * @param array $values 
     * @return self 
     */
    public function setManyThroughRelations(array $values = [])
    {
        $this->manyThroughs = $values;

        return $this;
    }

    /**
     * Set the one to one relations used when generating lazy load model
     * for model class definitions
     * 
     * @param array $values 
     * @return self 
     */
    public function setToOnesRelations(array $values = [])
    {
        $this->oneToOnes = $values;

        return $this;
    }

    /**
     * Set the many to many relations used when generating lazy load model
     * for model class definitions
     * 
     * @param array $values 
     * @return self 
     */
    public function setManyToManyRelations(array $values = [])
    {
        $this->manyToMany = $values;

        return $this;
    }

    /**
     * Set a property that insure model relations are generated
     * 
     * @return self 
     */
    public function withRelations()
    {
        $this->provideRelations = true;

        return $this;
    }

    /**
     * Makes the task runner generate policy classes
     * 
     * @return self
     */
    public function withPolicies()
    {
        $this->policies = true;
        return $this;
    }

    /**
     * Creates a code generator factory function based on provided options
     * 
     * @param array $options 
     * @param string $src 
     * @param string $routingfilename 
     * @param string|null $routePrefix 
     * @param string|null $middleware 
     * @param bool $forLumen 
     * @param bool $disableCache 
     * @param bool $noAuth 
     * @param string|null $namespace 
     * @param string|null $subPackage 
     * @param string|null $schema 
     * @param bool $hasHttpHandlers 
     * @return Closure(string $routesDirectory, string $cachePath, string $routesCachePath, Closure $onStartCallback, null|\Closure($policies) $onCompleteCallback = null, null|Closure $onExistsCallback = null): void 
     */
    public function run(
        array $options,
        string $src,
        string $routingfilename,
        string $routePrefix = null,
        string $middleware = null,
        bool $forLumen = true,
        bool $disableCache = false,
        bool $noAuth = false,
        string $namespace = null,
        string $subPackage = null,
        string $schema = null,
        bool $hasHttpHandlers = false
    ) {
        return function (
            string $routesDirectory,
            string $cachePath,
            string $routesCachePath,
            \Closure $onStartCallback,
            ?\Closure $onCompleteCallback = null,
            ?\Closure $onExistsCallback = null,
        ) use (
            $options,
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
            $hasHttpHandlers
        ) {
            $providesRelations = $this->provideRelations;
            $toones = $this->oneToOnes ?? [];
            $manytomany = $this->manyToMany ?? [];
            $onethroughs = $this->oneThroughs ?? [];
            $manythroughs = $this->manyThroughs ?? [];
            $camelize = $this->camelize;
            $policies = [];
            $message = [];

            $onCompleteCallback = $onCompleteCallback ?? static function () {
                printf("\nTask Completed successfully...\n");
            };
            $connection = DriverManager::getConnection($options);
            $schemaManager = $connection->createSchemaManager();
            $connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
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
                $src,
                $namespace ?? 'App'
            );
            if ($hasHttpHandlers) {
                $runner = $runner->withHttpHandlers();
            }

            if ($this->policies) {
                $runner = $runner->withPolicies();
            }

            if ($noAuth) {
                $runner = $runner->withoutAuth();
            }
            /**
             * @var ForeignKeyConstraintDefinition[]
             */
            $foreignKeys = [];
            /**
             * @var array<string,int>
             */
            $tablesindexes = [];
            // #endregion Create migration runner
            $traversable = $runner->setSubNamespace($subPackage)
                ->bindExceptMethod($tablesFilterFunc)
                ->only($this->tables ?? [])
                ->except($this->exceptions ?? [])
                ->setSchema($schema)
                ->handle(
                    $foreignKeys,
                    $tablesindexes,
                    static function ($tables) use ($namespace, $subPackage, $disableCache, $cachePath) {
                        if (!$disableCache) {
                            ComponentBuilderHelpers::cacheComponentDefinitions(
                                $cachePath,
                                $tables,
                                $namespace,
                                $subPackage
                            );
                        }
                    }
                );
            $values = iterator_to_array($traversable);
            /**
             * @var Progress
             */
            $indicator = $onStartCallback($values);
            // #region Create components models relations
            list($relations, $pivots) = $providesRelations ? self::resolveRelations(
                $values,
                $tablesindexes,
                $foreignKeys,
                $manytomany,
                $toones,
                $manythroughs,
                $onethroughs,
                $schema
            ) : [[], []];
            // #endregion Create components models relations
            $routes = iterator_to_array((static function () use (
                $camelize,
                $values,
                $subPackage,
                $indicator,
                $relations,
                $pivots,
                &$onExistsCallback,
                &$policies
            ) {
                foreach ($values as $component) {
                    $modelbuilder = Arr::get($component, 'model.class');
                    if (($modelbuilder instanceof ProvidesRelations) && is_array($componentrelations = $relations[Arr::get($component, 'model.classPath')] ?? [])) {
                        $modelbuilder = $modelbuilder->provideRelations($componentrelations);
                        if (in_array(Arr::get($component, 'table'), $pivots)) {
                            $modelbuilder = $modelbuilder->asPivot();
                        }
                    }
                    /**
                     * @var ViewModelClassBuilder
                     */
                    $viewmodelbuilder = Arr::get($component, 'viewModel.class');
                    /**
                     * @var DataTransfertClassBuilder
                     */
                    if ($dtobuiler = Arr::get($component, 'controller.dto.class')) {
                        $viewmodelbuilder = $viewmodelbuilder->setDTOClassPath($dtobuiler->getClassPath());
                    }
                    $viewmodelsourcecode = self::resolveWritable($viewmodelbuilder);
                    $servicesourcecode = self::resolveWritable(Arr::get($component, 'service.class'));
                    static::writeComponentSourceCode(Arr::get($component, 'model.path'), self::resolveWritable($modelbuilder), $onExistsCallback);
                    static::writeComponentSourceCode(Arr::get($component, 'viewModel.path'), $viewmodelsourcecode, $onExistsCallback);
                    static::writeComponentSourceCode(Arr::get($component, 'service.path'), $servicesourcecode, $onExistsCallback);
                    if (null !== $controller = Arr::get($component, 'controller')) {
                        if (is_array($componentrelations) && method_exists($dtobuiler, 'setCasts')) {
                            $currentDtoCasts = [];
                            foreach ($componentrelations as $_current) {
                                $currentDtoCasts[$_current->getName()] = in_array(
                                    $_current->getType(),
                                    [
                                        RelationTypes::ONE_TO_MANY,
                                        RelationTypes::MANY_TO_MANY,
                                        RelationTypes::ONE_TO_MANY_THROUGH
                                    ]
                                ) ?
                                    'collectionOf:\\' . ltrim($_current->getCastClassPath(), '\\') :
                                    'value:\\' . ltrim($_current->getCastClassPath(), '\\');
                            }
                            $dtobuiler->setCamelizeProperties($camelize)->setCasts($currentDtoCasts);
                        }
                        $dtosourcecode = self::resolveWritable($dtobuiler);
                        static::writeComponentSourceCode(Arr::get($controller, 'dto.path'), $dtosourcecode,  $onExistsCallback);
                        // Call the controller factory builder function with the required parameters
                        $controllersource = self::resolveWritable(Arr::get($controller, 'class'), $servicesourcecode, $viewmodelsourcecode, $dtosourcecode);
                        $name =  is_callable($nameBuilder = Arr::get($controller, 'route.nameBuilder')) ? $nameBuilder($controllersource) : Arr::get($controller, 'route.name'); //
                        $classPath = is_callable($classPathBuilder = Arr::get($controller, 'route.classPathBuilder')) ? $classPathBuilder($controllersource) : Arr::get($controller, 'route.classPath');
                        static::writeComponentSourceCode(Arr::get($controller, 'path'), $controllersource, $onExistsCallback);
                        $routeController = new RouteController($subPackage, $classPath);
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
                $indicator->advance();
            }

            // Case policies where generated, we creates a policy service provider class in the project
            if (is_array($policies) && !empty($policies)) {
                $policiesServiceProviderBuilder = MVCPolicyServiceProviderBuilder($policies);
                static::writeComponentSourceCode($src, self::resolveWritable($policiesServiceProviderBuilder),  $onExistsCallback);
                $message = [sprintf("Please add [\%s::class] to the list of application service providers to apply policies.\n", $policiesServiceProviderBuilder->getClassPath())];
            }
            if ((null !== $indicator) && ($indicator instanceof Progress)) {
                $indicator->complete();
            }
            if (null !== $onCompleteCallback && ($onCompleteCallback instanceof \Closure)) {
                $onCompleteCallback(implode(PHP_EOL, $message));
            }
        };
    }

    /**
     * Write app routes to disk
     * 
     * @param null|bool $disableCache 
     * @param null|bool $lumen 
     * @param (null|string)|null $routesDirectory 
     * @param (null|string)|null $cachePath 
     * @param (null|string)|null $routingfilename 
     * @param (null|string)|null $prefix 
     * @param (null|string)|null $middleware 
     * @param (null|string)|null $subPackage 
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
                $value = $cachePath ? RouteDefinitionsHelper::getCachedRouteDefinitions($cachePath) : null;
                $routes = array_merge($routes, $value ? $value->getRoutes() : []);
            }
            $definitions = [];
            foreach ($routes as $key => $value) {
                // Call the route definitions creator function
                $definitions[$key] = RouteDefinitionsHelper::for(
                    $key,
                    $value
                )($lumen);
            }
            RouteDefinitionsHelper::writeRouteDefinitions(
                $routesDirectory,
                $definitions,
                $routingfilename,
                $partial
            )(
                $lumen,
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

    /**
     * Write the source code for a given OOP component to disk
     * 
     * @param mixed $path 
     * @param Writable $writable 
     * @param null|callable $callback 
     * @return void 
     * 
     * @throws \Exception 
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
     * Resolve writable instance
     * 
     * @param Writable|ComponentBuilder|\Closure(...$args):Writable $component 
     * @param mixed $args 
     * @return Writable 
     * @throws RuntimeExWritableception 
     */
    private static function resolveWritable($component, ...$args)
    {
        if ($component instanceof Writable) {
            return $component;
        }
        if ($component instanceof ComponentBuilder) {
            return $component->build();
        }
        if (is_callable($component)) {
            return $component(...$args);
        }

        throw new RuntimeException('Unsupported type ' . (is_object($component)  && !is_null($component) ? get_class($component) : gettype($component)));
    }
}
