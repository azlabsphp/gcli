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

namespace Drewlabs\GCli\Extensions\Console;

use Drewlabs\CodeGenerator\Exceptions\PHPVariableException;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Builders\ORMModelBuilder;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;

use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;

class ComponentCommandsHelpers
{
    /**
     * @throws UnableToRetrieveMetadataException
     *
     * @return void
     */
    public static function createService(string $namespace, string $basePath, string $model = null, string $class = null)
    {
        if (null !== $class && !class_exists($class) && !class_exists(ORMModelBuilder::defaultClassPath($class))) {
            $name = Str::contains($class, '\\') ? Str::afterLast('\\', $class) : $class;
            $class_name_space = sprintf('\\%s\\Services', static::getBaseNamespace($namespace) ?? 'App');
            ComponentsScriptWriter($basePath)->write(
                ComponentBuilderHelpers::buildServiceDefinition(
                    true,
                    $name,
                    $class_name_space,
                    $model
                )
            );

            return sprintf('%s\\%s', $class_name_space, $name);
        }
    }

    /**
     * @throws PHPVariableException
     *
     * @return string|void
     */
    public static function createDto(string $namespace, string $basePath, string $model = null, string $class = null, array $attributes = [])
    {
        if (null !== $class && !class_exists($class) && !class_exists(ORMModelBuilder::defaultClassPath($class))) {
            $name = Str::contains($class, '\\') ? Str::afterLast('\\', $class) : $class;
            $class_name_space = sprintf('\\%s\\Dto', static::getBaseNamespace($namespace) ?? 'App');
            ComponentsScriptWriter($basePath)->write(
                ComponentBuilderHelpers::buildDtoObjectDefinition(
                    $attributes ?? [],
                    [],
                    $name,
                    $class_name_space,
                    $model
                )
            );

            return sprintf('%s\\%s', $class_name_space, $name);
        }
    }

    /**
     * @throws PHPVariableException
     *
     * @return string|void
     */
    public static function createViewModel(string $namespace, string $basePath, string $model = null, string $class = null, array $rules = [], array $updateRules = [])
    {
        if (null !== $class && !class_exists($class) && !class_exists(ORMModelBuilder::defaultClassPath($class))) {
            $name = Str::contains($class, '\\') ? Str::afterLast('\\', $class) : $class;
            $class_namespace = sprintf('\\%s\\Http\\ViewModels', static::getBaseNamespace($namespace) ?? 'App');
            ComponentsScriptWriter($basePath)->write(
                ComponentBuilderHelpers::buildViewModelDefinition(
                    false,
                    $rules ?? [],
                    $updateRules ?? [],
                    $name,
                    $class_namespace,
                    null,
                    $model,
                    true
                )
            );

            return sprintf('%s\\%s', $class_namespace, $name);
        }
    }

    /**
     * @param mixed $namespace
     *
     * @return string
     */
    public static function getBaseNamespace($namespace)
    {
        if (Str::startsWith($namespace, '\\')) {
            return Str::before('\\', Str::ltrim($namespace, '\\'));
        }

        return Str::before('\\', $namespace);
    }
}
