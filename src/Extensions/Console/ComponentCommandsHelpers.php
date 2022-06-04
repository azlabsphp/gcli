<?php

namespace Drewlabs\ComponentGenerators\Extensions\Console;

use Drewlabs\CodeGenerator\Exceptions\PHPVariableException;
use Drewlabs\ComponentGenerators\Builders\EloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Drewlabs\Core\Helpers\Str;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;

class ComponentCommandsHelpers
{
    /**
     * 
     * @param string $namespace 
     * @param string $basePath 
     * @param string|null $model 
     * @param string|null $class 
     * @return void 
     * @throws UnableToRetrieveMetadataException 
     */
    public static function createService(string $namespace, string $basePath, ?string  $model = null, ?string $class = null)
    {
        if (null !== $class && !class_exists($class) && !class_exists(EloquentORMModelBuilder::defaultClassPath($class))) {
            $name = Str::contains($class, '\\') ? Str::afterLast('\\', $class) : $class;
            $class_name_space = sprintf("\\%s\\Services", static::getBaseNamespace($namespace) ?? "App");
            ComponentsScriptWriter($basePath)->write(
                ComponentBuilderHelpers::buildServiceDefinition(
                    true,
                    $name,
                    $class_name_space,
                    $model
                )
            );
            return sprintf("%s\\%s", $class_name_space, $name);
        }
    }

    /**
     * 
     * @param string $namespace 
     * @param string $basePath 
     * @param null|string $model 
     * @param null|string $class 
     * @param array $attributes 
     * @return string|void 
     * @throws PHPVariableException 
     */
    public static function createDto(string $namespace, string $basePath, ?string  $model = null, ?string $class = null, array $attributes = [])
    {
        if (null !== $class && !class_exists($class) && !class_exists(EloquentORMModelBuilder::defaultClassPath($class))) {
            $name = Str::contains($class, '\\') ? Str::afterLast('\\', $class) : $class;
            $class_name_space = sprintf("\\%s\\Dto", static::getBaseNamespace($namespace) ?? "App");
            ComponentsScriptWriter($basePath)->write(
                ComponentBuilderHelpers::buildDtoObjectDefinition(
                    $attributes ?? [],
                    [],
                    $name,
                    $class_name_space,
                    $model
                )
            );
            return sprintf("%s\\%s", $class_name_space, $name);
        }
    }

    /**
     * 
     * @param string $namespace 
     * @param string $basePath 
     * @param null|string $model 
     * @param null|string $class 
     * @param array $rules 
     * @param array $updateRules 
     * @return string|void 
     * @throws PHPVariableException 
     */
    public static function createViewModel(string $namespace, string $basePath, ?string  $model = null, ?string $class = null, array $rules = [], array $updateRules = [])
    {
        if (null !== $class && !class_exists($class) && !class_exists(EloquentORMModelBuilder::defaultClassPath($class))) {
            $name = Str::contains($class, '\\') ? Str::afterLast('\\', $class) : $class;
            $class_namespace = sprintf("\\%s\\Http\\ViewModels", static::getBaseNamespace($namespace) ?? "App");
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
            return sprintf("%s\\%s", $class_namespace, $name);
        }
    }

    /**
     * 
     * @param mixed $namespace 
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
