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

namespace Drewlabs\ComponentGenerators\Helpers;

use Drewlabs\CodeGenerator\Exceptions\PHPVariableException;
use Drewlabs\ComponentGenerators\Builders\DataTransfertClassBuilder;
use Drewlabs\ComponentGenerators\Builders\ServiceClassBuilder;
use Drewlabs\ComponentGenerators\Builders\ViewModelClassBuilder;
use Drewlabs\ComponentGenerators\Cache\CacheableSerializer;
use Drewlabs\ComponentGenerators\Cache\CacheableTables;
use Drewlabs\ComponentGenerators\Contracts\Cacheable;
use Drewlabs\ComponentGenerators\Contracts\ControllerBuilder;
use Drewlabs\ComponentGenerators\Contracts\ORMModelBuilder;
use Drewlabs\ComponentGenerators\Contracts\SourceFileInterface;
use function Drewlabs\ComponentGenerators\Proxy\DataTransfertClassBuilder;

use function Drewlabs\ComponentGenerators\Proxy\EloquentORMModelBuilder;
use function Drewlabs\ComponentGenerators\Proxy\MVCControllerBuilder;
use function Drewlabs\ComponentGenerators\Proxy\MVCServiceBuilder;

use function Drewlabs\ComponentGenerators\Proxy\ORMColumnDefinition;
use function Drewlabs\ComponentGenerators\Proxy\ORMModelDefinition;
use function Drewlabs\ComponentGenerators\Proxy\ViewModelBuilder;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Filesystem\Exceptions\CreateDirectoryException;
use Drewlabs\Filesystem\Exceptions\FileNotFoundException;
use Drewlabs\Filesystem\Exceptions\ReadFileException;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use Drewlabs\Filesystem\Exceptions\WriteOperationFailedException;
use Drewlabs\Psr7Stream\Exceptions\StreamException;
use Drewlabs\Psr7Stream\Exceptions\IOException;
use InvalidArgumentException;
use RuntimeException;

use function Drewlabs\Filesystem\Proxy\Path;

class ComponentBuilderHelpers
{
    /**
     * Creates a model builder class
     * 
     * @param string $table 
     * @param array $columns 
     * @param string $namespace 
     * @param string $primaryKey 
     * @param bool $increments 
     * @param bool $isViewModel 
     * @param array $hidden 
     * @param array $appends 
     * @param (null|string)|null $comments
     * 
     * @return ORMModelBuilder 
     */
    public static function createModelBuilder(
        string $table,
        array $columns = [],
        string $namespace = 'App\\Models',
        string $primaryKey = 'id',
        bool $increments = true,
        $isViewModel = false,
        array $hidden = [],
        array $appends = [],
        ?string $comments = null
    ) {
        $component = EloquentORMModelBuilder(
            ORMModelDefinition([
                'primaryKey' => $primaryKey,
                'name' => null,
                'table' => $table,
                'columns' => array_map(
                    static function ($definition) {
                        $name = Str::before('|', $definition);
                        $least = explode(',', Str::after('|', $definition) ?? '');
                        $type = Arr::first($least) ?? null;
                        // TODO : Load the remaining parts
                        return ORMColumnDefinition([
                            'name' => $name,
                            'type' => empty($type) ? null : $type,
                        ]);
                    },
                    array_filter(array_map(static function ($column) {
                        if (\is_string($column) && !Str::contains($column, '|')) {
                            $column = sprintf('%s|', $column);
                        }
                        return $column;
                    }, $columns), static function ($definition) {
                        return null !== $definition && Str::contains($definition, '|');
                    })
                ),
                'increments' => $increments,
                'namespace' => $namespace,
                'comment' => $comments,
            ])
        )->setHiddenColumns($hidden ?? [])
            ->setAppends($appends ?? []);
        if ($isViewModel) {
            $component = $component->asViewModel();
        }

        return $component;
    }

    /**
     * Creates a service builder class
     * 
     * @param bool $asCRUD 
     * @param (null|string)|null $name 
     * @param (null|string)|null $namespace 
     * @param (null|string)|null $model 
     * @return ServiceClassBuilder 
     * @throws InvalidArgumentException 
     */
    public static function createServiceBuilder(
        bool $asCRUD = false,
        ?string $name = null,
        ?string $namespace = null,
        ?string $model = null
    ) {
        return ($component = \is_string($model) ? MVCServiceBuilder($name, $namespace)->bindModel($model) : MVCServiceBuilder($name, $namespace)) && $asCRUD ? $component->asCRUDService() : $component;
    }

    /**
     * Create a view model builder class
     * 
     * @param bool $single 
     * @param array $rules 
     * @param array $updateRules 
     * @param (null|string)|null $name 
     * @param (null|string)|null $namespace 
     * @param (null|string)|null $path 
     * @param (null|string)|null $model 
     * @param null|bool $hasHttpHandlers 
     * @return ViewModelClassBuilder 
     * @throws InvalidArgumentException 
     */
    public static function createViewModelBuilder(
        bool $single = false,
        array $rules = [],
        array $updateRules = [],
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null,
        ?string $model = null,
        ?bool $hasHttpHandlers = false
    ) {
        $rulesParserFunc = static function ($definitions) {
            $definitions_ = [];
            foreach ($definitions as $key => $value) {
                if (\is_string($value) && !Str::contains($value, '=')) {
                    continue;
                }
                if (is_numeric($key) && \is_string($value)) {
                    $k = Str::before('=', $value);
                    $v = Str::after('=', $value);
                    $definitions_[$k] = $v;
                    continue;
                }
                $definitions_[$key] = $value;
            }
            foreach ($definitions_ ?? [] as $key => $definition) {
                yield $key => $definition;
            }
        };
        $component = ViewModelBuilder($name, $namespace, $path);
        if (\is_string($model)) {
            $component = $component->bindModel($model);
        }
        if (!$single) {
            $component = $component->setUpdateRules(
                iterator_to_array(
                    $rulesParserFunc($updateRules)
                )
            );
        } else {
            $component = $component->asSingleActionValidator();
        }

        if ($hasHttpHandlers) {
            $component = $component->withHttpHandlers();
        }

        return $component
            ->addInputsTraits()
            ->setRules(iterator_to_array($rulesParserFunc($rules)));
    }

    /**
     * Create a Data Transfer builder class
     * 
     * @param array $attributes 
     * @param array $hidden 
     * @param (null|string)|null $name 
     * @param (null|string)|null $namespace 
     * @param (null|string)|null $model 
     * @return DataTransfertClassBuilder 
     * @throws InvalidArgumentException 
     */
    public static function createDtoBuilder(
        array $attributes = [],
        array $hidden = [],
        ?string $name = null,
        ?string $namespace = null,
        ?string $model = null
    ) {
        $component = (DataTransfertClassBuilder($attributes, $name, $namespace));
        if (\is_string($model)) {
            $component = $component->bindModel($model);
        }
        return $component->setHidden($hidden ?? []);
    }

    /**
     * Creates controller builder
     * 
     * @param mixed $model 
     * @param mixed $service 
     * @param mixed $viewModel 
     * @param mixed $dto 
     * @param null|string $name 
     * @param null|string $namespace 
     * @param bool $auth 
     * @param bool $authorize 
     * @return ControllerBuilder 
     */
    public static function createControllerBuilder(
        $model = null,
        $service = null,
        $viewModel = null,
        $dto = null,
        ?string $name = null,
        ?string $namespace = null,
        bool $auth = true,
        bool $authorize = false
    ) {
        $component = MVCControllerBuilder($name, $namespace);
        if (!$auth) {
            $component = $component->withoutAuthenticatable();
        }
        // Make the component authorizable
        if ($authorize) {
            $component = $component->authorizable();
        }
        // Check null state of the service parameter
        if (null !== $service) {
            $component = $component->bindService(\is_string($service) ? $service : \get_class($service));
        }
        // Check null state of the model parameter
        if (null !== $model) {
            $component = $component->bindModel(\is_string($model) ? $model : \get_class($model));
        }
        // Check null state of the viewModel parameter
        if (null !== $viewModel) {
            $component = $component->bindViewModel(\is_string($viewModel) ? $viewModel : \get_class($viewModel));
        }
        // Check null state of the dtoObject parameter
        if (null !== $dto) {
            $component = $component->bindDTOObject(\is_string($dto) ? $dto : \get_class($dto));
        }
        return $component;
    }

    /**
     * Build a model class script
     * 
     * @param string $table 
     * @param array $columns 
     * @param string $namespace 
     * @param string $primaryKey 
     * @param bool $increments 
     * @param bool $vm 
     * @param array $hidden 
     * @param array $appends 
     * @param (null|string)|null $comments 
     * @return SourceFileInterface 
     * @throws RuntimeException 
     * @throws PHPVariableException 
     * @throws UnableToRetrieveMetadataException 
     */
    public static function buildModelDefinitionSourceFile(
        string $table,
        array $columns = [],
        string $namespace = 'App\\Models',
        string $primaryKey = 'id',
        bool $increments = true,
        $vm = false,
        array $hidden = [],
        array $appends = [],
        ?string $comments = null
    ) {
        return static::createModelBuilder(
            $table,
            $columns,
            $namespace,
            $primaryKey,
            $increments,
            $vm,
            $hidden,
            $appends,
            $comments
        )->build();
    }

    /**
     * Build a service class script
     * 
     * @param bool $asCRUD 
     * @param (null|string)|null $name 
     * @param (null|string)|null $namespace 
     * @param (null|string)|null $model 
     * @return SourceFileInterface 
     * @throws InvalidArgumentException 
     * @throws UnableToRetrieveMetadataException 
     */
    public static function buildServiceDefinition(
        bool $asCRUD = false,
        ?string $name = null,
        ?string $namespace = null,
        ?string $model = null
    ) {
        return self::createServiceBuilder($asCRUD, $name, $namespace, $model)->build();
    }

    /**
     * Build view model class script
     * 
     * @param bool $single 
     * @param array $rules 
     * @param array $updateRules 
     * @param (null|string)|null $name 
     * @param (null|string)|null $namespace 
     * @param (null|string)|null $path 
     * @param (null|string)|null $model 
     * @param null|bool $hasHttpHandlers 
     * @return SourceFileInterface 
     * @throws InvalidArgumentException 
     * @throws PHPVariableException 
     * @throws UnableToRetrieveMetadataException 
     */
    public static function buildViewModelDefinition(
        bool $single = false,
        array $rules = [],
        array $updateRules = [],
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null,
        ?string $model = null,
        ?bool $hasHttpHandlers = false
    ) {
        return self::createViewModelBuilder(
            $single,
            $rules,
            $updateRules,
            $name,
            $namespace,
            $path,
            $model,
            $hasHttpHandlers
        )->build();
    }

    /**
     * Build Data transfer class script
     * 
     * @param array $attributes 
     * @param array $hidden 
     * @param (null|string)|null $name 
     * @param (null|string)|null $namespace 
     * @param (null|string)|null $model 
     * @return SourceFileInterface 
     * @throws InvalidArgumentException 
     * @throws UnableToRetrieveMetadataException 
     */
    public static function buildDtoObjectDefinition(
        array $attributes = [],
        array $hidden = [],
        ?string $name = null,
        ?string $namespace = null,
        ?string $model = null
    ) {
        return self::createDtoBuilder(
            $attributes,
            $hidden,
            $name,
            $namespace,
            $model
        )->build();
    }


    /**
     * Build controller class script
     * 
     * @param mixed $model 
     * @param mixed $service 
     * @param mixed $viewModel 
     * @param mixed $dto 
     * @param null|string $name 
     * @param null|string $namespace 
     * @param bool $auth 
     * @param bool $authorize 
     * 
     * @return SourceFileInterface 
     */
    public static function buildController(
        $model = null,
        $service = null,
        $viewModel = null,
        $dto = null,
        ?string $name = null,
        ?string $namespace = null,
        bool $auth = true,
        bool $authorize = false
    ) {
        return self::createControllerBuilder(
            $model,
            $service,
            $viewModel,
            $dto,
            $name,
            $namespace,
            $auth,
            $authorize
        )->build();
    }

    /**
     * Rewrite a script component path
     * 
     * @param string $namespace 
     * @param string $path 
     * @return string 
     * @throws UnableToRetrieveMetadataException 
     */
    public static function rebuildComponentPath(string $namespace, string $path)
    {
        $namespace = $namespace ?? '';
        $namespace_dir = Str::contains($namespace ?? '', '\\') ? Str::afterLast('\\', $namespace) : $namespace;
        $basename = Path($path)->basename();
        if (Str::lower($namespace_dir) !== Str::lower($basename)) {
            // If the last part of both namespace and path are not the same
            $parts = array_reverse(explode('\\', $namespace));
            foreach ($parts as $value) {
                if (Str::contains($path, $value)) {
                    $path = sprintf('%s%s%s', rtrim(
                        $path,
                        \DIRECTORY_SEPARATOR
                    ), \DIRECTORY_SEPARATOR, ltrim(
                        Str::replace(
                            '\\',
                            \DIRECTORY_SEPARATOR,
                            Str::afterLast($value, $namespace)
                        ),
                        \DIRECTORY_SEPARATOR
                    ));
                    break;
                }
            }
        }

        return $path;
    }

    /**
     * Create route name
     * 
     * @param string $classname 
     * @return string 
     */
    public static function buildRouteName(string $classname)
    {
        if (empty($classname) || (null === $classname)) {
            $classname = 'TestsController';
        }

        return Str::snakeCase(Str::replace('Controller', '', $classname), '-');
    }

    /**
     * Cache component definitions
     * 
     * @param string $path 
     * @param array $tables 
     * @param (null|string)|null $namespace 
     * @param (null|string)|null $subPackage 
     * @return void 
     * @throws UnableToRetrieveMetadataException 
     * @throws CreateDirectoryException 
     * @throws InvalidArgumentException 
     * @throws StreamException 
     * @throws IOException 
     * @throws WriteOperationFailedException 
     */
    public static function cacheComponentDefinitions(string $path, array $tables, ?string $namespace = null, ?string $subPackage = null)
    {
        (new CacheableSerializer($path))->dump(
            new CacheableTables(
                [
                    'tables' => $tables,
                    'namespace' => $namespace,
                    'subNamespace' => $subPackage,
                ]
            )
        );
    }

    /**
     * Get cached component defitions
     * 
     * @param string $path 
     * @return Cacheable 
     * @throws ReadFileException 
     * @throws UnableToRetrieveMetadataException 
     * @throws FileNotFoundException 
     */
    public static function getCachedComponentDefinitions(string $path)
    {
        $value = (new CacheableSerializer($path))->load(CacheableTables::class);
        return $value;
    }
}
