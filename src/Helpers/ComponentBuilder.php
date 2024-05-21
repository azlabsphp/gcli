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

namespace Drewlabs\GCli\Helpers;

use Drewlabs\CodeGenerator\Exceptions\PHPVariableException;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Builders\DataTransfertClassBuilder;
use Drewlabs\GCli\Builders\ServiceClassBuilder;
use Drewlabs\GCli\Builders\ViewModelClassBuilder;
use Drewlabs\GCli\Cache\Cache;
use Drewlabs\GCli\Cache\CacheableTables;
use Drewlabs\GCli\Contracts\Cacheable;
use Drewlabs\GCli\Contracts\ControllerBuilder;
use Drewlabs\GCli\Contracts\ORMModelBuilder;

use Drewlabs\GCli\Contracts\SourceFileInterface;
use Drewlabs\GCli\DBAL\T\Column;
use Drewlabs\GCli\DBAL\T\Table;
use Drewlabs\GCli\Exceptions\IOException;
use Drewlabs\GCli\IO\Path;

use Drewlabs\GCli\Traits\ProvidesTrimTableSchema;
use Illuminate\Support\Pluralizer;

use function Drewlabs\GCli\Proxy\DataTransfertClassBuilder;
use function Drewlabs\GCli\Proxy\EloquentORMModelBuilder;
use function Drewlabs\GCli\Proxy\MVCControllerBuilder;
use function Drewlabs\GCli\Proxy\MVCServiceBuilder;
use function Drewlabs\GCli\Proxy\ViewModelBuilder;

class ComponentBuilder
{
    use ProvidesTrimTableSchema;

    /**
     * Creates a model builder class.
     *
     * @param bool               $isViewModel
     * @param (string|null)|null $comments
     *
     * @return ORMModelBuilder
     */
    public static function createModelBuilder(
        string $table,
        ?string $schema = null,
        array $columns = [],
        string $namespace = 'App\\Models',
        string $primaryKey = 'id',
        bool $increments = true,
        $isViewModel = false,
        array $hidden = [],
        array $appends = [],
        string $comments = null
    ) {
        $module = static::trimschema($table, $schema);
        $component = EloquentORMModelBuilder(
            new Table(
                $primaryKey,
                Str::camelize(Pluralizer::singular($module)),
                $table,
                array_map(
                    static function ($definition) {
                        $name = Str::before('|', $definition);
                        $least = explode(',', Str::after('|', $definition) ?? '');
                        $type = Arr::first($least) ?? null;

                        return new Column($name, empty($type) ? null : $type);
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
                $increments,
                $namespace,
                $module,
                $comments,
            )
        )->setHiddenColumns($hidden ?? [])
            ->setAppends($appends ?? []);
        if ($isViewModel) {
            $component = $component->asViewModel();
        }

        return $component;
    }

    /**
     * Creates a service builder class.
     *
     * @param (string|null)|null $name
     * @param (string|null)|null $namespace
     * @param (string|null)|null $model
     *
     * @throws \InvalidArgumentException
     *
     * @return ServiceClassBuilder
     */
    public static function createServiceBuilder(
        bool $asCRUD = false,
        string $name = null,
        string $namespace = null,
        string $model = null
    ) {
        return ($component = \is_string($model) ? MVCServiceBuilder($name, $namespace)->bindModel($model) : MVCServiceBuilder($name, $namespace)) && $asCRUD ? $component->asCRUDService() : $component;
    }

    /**
     * Create a view model builder class.
     *
     * @param (string|null)|null $name
     * @param (string|null)|null $namespace
     * @param (string|null)|null $path
     * @param (string|null)|null $model
     *
     * @throws \InvalidArgumentException
     *
     * @return ViewModelClassBuilder
     */
    public static function createViewModelBuilder(
        bool $single = false,
        array $rules = [],
        array $updateRules = [],
        string $name = null,
        string $namespace = null,
        string $path = null,
        string $model = null,
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
     * Create a Data Transfer builder class.
     *
     * @param (string|null)|null $name
     * @param (string|null)|null $namespace
     * @param (string|null)|null $model
     *
     * @throws \InvalidArgumentException
     *
     * @return DataTransfertClassBuilder
     */
    public static function createDtoBuilder(
        array $attributes = [],
        array $hidden = [],
        string $name = null,
        string $namespace = null,
        string $model = null
    ) {
        $component = DataTransfertClassBuilder($attributes, $name, $namespace);
        if (\is_string($model)) {
            $component = $component->bindModel($model);
        }

        return $component->setHidden($hidden ?? []);
    }

    /**
     * Creates controller builder.
     *
     * @param mixed $model
     * @param mixed $service
     * @param mixed $viewModel
     * @param mixed $dto
     *
     * @return ControllerBuilder
     */
    public static function createControllerBuilder(
        $model = null,
        $service = null,
        $viewModel = null,
        $dto = null,
        string $name = null,
        string $namespace = null,
        bool $auth = true,
        bool $authorize = false,
        string $key = 'id'
    ) {
        $component = MVCControllerBuilder($name, $namespace);
        // Add binding for primary key
        if (!is_null($key)) {
            $component = $component->withPrimaryKey($key);
        }
        if (!$auth) {
            $component = $component->withoutAuthenticatable();
        }
        // Make the component authorizable
        if ($authorize) {
            $component = $component->authorizable();
        }
        // Check null state of the service parameter
        if (null !== $service) {
            $arguments = \is_array($service) ? $service : [\is_string($service) ? $service : $service::class];
            $component = $component->bindService(...$arguments);
        }
        // Check null state of the model parameter
        if (null !== $model) {
            $component = $component->bindModel(\is_string($model) ? $model : $model::class);
        }
        // Check null state of the viewModel parameter
        if (null !== $viewModel) {
            $component = $component->bindViewModel(\is_string($viewModel) ? $viewModel : $viewModel::class);
        }

        // Check null state of the dtoObject parameter
        if (null !== $dto) {
            $component = $component->bindDTOObject(\is_string($dto) ? $dto : $dto::class);
        }

        return $component;
    }

    /**
     * Build a service class script.
     *
     * @param (string|null)|null $name
     * @param (string|null)|null $namespace
     * @param (string|null)|null $model
     *
     * @throws \InvalidArgumentException
     * @throws IOException
     *
     * @return SourceFileInterface
     */
    public static function buildServiceDefinition(
        bool $asCRUD = false,
        string $name = null,
        string $namespace = null,
        string $model = null
    ) {
        return self::createServiceBuilder($asCRUD, $name, $namespace, $model)->build();
    }

    /**
     * Build view model class script.
     *
     * @param (string|null)|null $name
     * @param (string|null)|null $namespace
     * @param (string|null)|null $path
     * @param (string|null)|null $model
     *
     * @throws \InvalidArgumentException
     * @throws PHPVariableException
     * @throws IOException
     *
     * @return SourceFileInterface
     */
    public static function buildViewModelDefinition(
        bool $single = false,
        array $rules = [],
        array $updateRules = [],
        string $name = null,
        string $namespace = null,
        string $path = null,
        string $model = null,
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
     * Build Data transfer class script.
     *
     * @param (string|null)|null $name
     * @param (string|null)|null $namespace
     * @param (string|null)|null $model
     *
     * @throws \InvalidArgumentException
     * @throws IOException
     *
     * @return SourceFileInterface
     */
    public static function buildDtoObjectDefinition(
        array $attributes = [],
        array $hidden = [],
        string $name = null,
        string $namespace = null,
        string $model = null
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
     * Build controller class script.
     *
     * @param mixed $model
     * @param mixed $service
     * @param mixed $viewModel
     * @param mixed $dto
     *
     * @return SourceFileInterface
     */
    public static function buildController(
        $model = null,
        $service = null,
        $viewModel = null,
        $dto = null,
        string $name = null,
        string $namespace = null,
        bool $auth = true,
        bool $authorize = false,
        string $key = 'id'
    ) {
        return self::createControllerBuilder(
            $model,
            $service,
            $viewModel,
            $dto,
            $name,
            $namespace,
            $auth,
            $authorize,
            $key
        )->build();
    }
}
