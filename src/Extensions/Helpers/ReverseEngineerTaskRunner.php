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

use Closure;
use Doctrine\DBAL\DriverManager;
use Drewlabs\ComponentGenerators\ComponentsScriptWriter as ComponentsScriptWriterClass;
use Drewlabs\ComponentGenerators\Contracts\ComponentBuilder;
use Drewlabs\ComponentGenerators\Contracts\ForeignKeyConstraintDefinition;
use Drewlabs\ComponentGenerators\Contracts\ProvidesRelations;
use Drewlabs\ComponentGenerators\Contracts\Writable;
use Drewlabs\ComponentGenerators\Extensions\Contracts\Progress;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\ComponentGenerators\Helpers\RouteDefinitionsHelper;
use Drewlabs\ComponentGenerators\Models\RouteController;
use Drewlabs\ComponentGenerators\Builders\DataTransfertClassBuilder;
use Drewlabs\ComponentGenerators\Builders\ViewModelClassBuilder;
use Drewlabs\ComponentGenerators\Extensions\Traits\ReverseEngineerRelations;
use Drewlabs\ComponentGenerators\RelationTypes;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\DatabaseSchemaReverseEngineeringRunner;

use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Str;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToWriteFile;
use RuntimeException;

class ReverseEngineerTaskRunner
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
        string $routePrefix = null,
        string $middleware = null,
        bool $forLumen = true,
        bool $disableCache = false,
        bool $noAuth = false,
        string $namespace = null,
        string $subPackage = null,
        string $schema = null,
        bool $hasHttpHandlers = false,
        bool $providesRelations = false,
        array $toones = [],
        array $manytomany = [],
        array $onethroughs = [],
        array $manythroughs = []
    ) {
        return function (
            string $routesDirectory,
            string $cachePath,
            string $routesCachePath,
            \Closure $onStartCallback,
            ?\Closure $onCompleteCallback = null,
            ?\Closure $onExistsCallback = null
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
            $hasHttpHandlers,
            $providesRelations,
            $toones,
            $manytomany,
            $onethroughs,
            $manythroughs,
        ) {
            $onCompleteCallback = $onCompleteCallback ?? static function () {
                printf("\nTask Completed successfully...\n");
            };
            $connection = DriverManager::getConnection($options);
            $schemaManager = $connection->createSchemaManager();
            // For Mariadb server
            $schemaManager->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
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
                $namespace ?? 'App'
            );
            if ($hasHttpHandlers) {
                $runner = $runner->withHttpHandlers();
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
                ->run(
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
            $routes = iterator_to_array((static function () use ($values, $subPackage, $indicator, $relations, $pivots, &$onExistsCallback) {
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
                            $dtobuiler->setCasts($currentDtoCasts);
                        }
                        $dtosourcecode = self::resolveWritable($dtobuiler);
                        static::writeComponentSourceCode(Arr::get($controller, 'dto.path'), $dtosourcecode,  $onExistsCallback);
                        // Call the controller factory builder function with the required parameters
                        $controllersource = self::resolveWritable(Arr::get($controller, 'class'), $servicesourcecode, $viewmodelsourcecode, $dtosourcecode);
                        $name =  is_callable($nameBuilder = Arr::get($controller, 'route.nameBuilder')) ? $nameBuilder($controllersource) : Arr::get($controller, 'route.name'); //
                        $classPath = is_callable($classPathBuilder = Arr::get($controller, 'route.classPathBuilder')) ? $classPathBuilder($controllersource) : Arr::get($controller, 'route.classPath');
                        static::writeComponentSourceCode(Arr::get($controller, 'path'), $controllersource, $onExistsCallback);
                        $routeController = new RouteController(['namespace' => $subPackage, 'name' => $classPath]);
                        yield $name => $routeController;
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
            if ((null !== $indicator) && ($indicator instanceof Progress)) {
                $indicator->complete();
            }
            if (null !== $onCompleteCallback && ($onCompleteCallback instanceof \Closure)) {
                $onCompleteCallback();
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
     * @throws FilesystemException 
     * @throws UnableToCheckExistence 
     * @throws UnableToWriteFile 
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
     * @param mixed $component 
     * @param mixed $args 
     * @return mixed 
     * @throws RuntimeException 
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
