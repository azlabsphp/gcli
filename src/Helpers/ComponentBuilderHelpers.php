<?php

namespace Drewlabs\ComponentGenerators\Helpers;

use Drewlabs\CodeGenerator\Exceptions\PHPVariableException;
use Drewlabs\ComponentGenerators\Contracts\SourceFileInterface;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\DataTransfertClassBuilder;
use function Drewlabs\ComponentGenerators\Proxy\EloquentORMModelBuilder;
use function Drewlabs\ComponentGenerators\Proxy\MVCControllerBuilder;
use function Drewlabs\ComponentGenerators\Proxy\MVCServiceBuilder;
use function Drewlabs\ComponentGenerators\Proxy\ORMColumnDefinition;
use function Drewlabs\ComponentGenerators\Proxy\ORMModelDefinition;
use function Drewlabs\ComponentGenerators\Proxy\ViewModelBuilder;
use function Drewlabs\Filesystem\Proxy\Path;

class ComponentBuilderHelpers
{
    /**
     * 
     * @param string $table 
     * @param array $columns 
     * @param string $namespace 
     * @param string $primaryKey 
     * @param bool $increments 
     * @param bool $vm 
     * @return SourceFileInterface 
     */
    public static function buildModelDefinition(
        string $table,
        array $columns = [],
        string $namespace = "App\\Models",
        string $primaryKey = 'id',
        bool $increments = true,
        $vm = false
    ) {
        $component = EloquentORMModelBuilder(
            ORMModelDefinition([
                'primaryKey' => $primaryKey,
                'name' => null,
                'table' => $table,
                'columns' => array_map(
                    function ($definition) {
                        $name = drewlabs_core_strings_before('|', $definition);
                        $least = explode(',', drewlabs_core_strings_after('|', $definition));
                        $type = $least[0];
                        // TODO : Load the remaining parts
                        return ORMColumnDefinition([
                            'name' => $name,
                            'type' => $type
                        ]);
                    },
                    array_filter($columns, function ($definition) {
                        return null !== $definition && drewlabs_core_strings_contains($definition, '|');
                    })
                ),
                'increments' => $increments,
                'namespace' => $namespace
            ])
        );
        if ($vm) {
            $component = $component->asViewModel();
        }
        return $component->build();
    }

    /**
     * 
     * @param bool $asCRUD
     * @param string|null $name 
     * @param string|null $namespace 
     * @param string|null $model 
     * @return SourceFileInterface 
     */
    public static function buildServiceDefinition(
        bool $asCRUD = false,
        string $name = null,
        string $namespace = null,
        string $model = null
    ) {

        $component = (MVCServiceBuilder($name, $namespace));
        if (is_string($model)) {
            $component = $component->bindModel(
                $model
            );
        }
        if ($asCRUD) {
            $component = $component->asCRUDService();
        }
        return $component->build();
    }

    /**
     * 
     * @param bool $handleSignleAction 
     * @param array $rules 
     * @param array $updateRules 
     * @param string|null $name 
     * @param string|null $namespace 
     * @param string|null $model 
     * @return SourceFileInterface 
     * @throws PHPVariableException 
     */
    public static function buildViewModelDefinition(
        bool $handleSignleAction = false,
        array $rules = [],
        array $updateRules = [],
        string $name = null,
        string $namespace = null,
        string $model = null
    ) {
        $rulesParserFunc = function ($definitions) {
            $definitions_ = [];
            foreach ($definitions as $key => $value) {
                if (is_string($value) && !drewlabs_core_strings_contains($value, '=')) {
                    continue;
                }
                if (is_numeric($key) && is_string($value)) {
                    $k = drewlabs_core_strings_before('=', $value);
                    $v = drewlabs_core_strings_after('=', $value);
                    $definitions_[$k] = $v;
                    continue;
                }
                $definitions_[$key] = $value;
            }
            foreach ($definitions_ ?? [] as $key => $definition) {
                yield $key => $definition;
            }
        };
        $component = (ViewModelBuilder($name, $namespace));
        if (is_string($model)) {
            $component = $component->bindModel(
                $model
            );
        }
        if (!$handleSignleAction) {
            $component = $component->setUpdateRules(
                iterator_to_array(
                    $rulesParserFunc($updateRules)
                )
            );
        } else {
            $component = $component->asSingleActionValidator();
        }
        return $component->addInputsTraits()
            ->addFileInputTraits()
            ->addAuthenticatableTraits()
            ->setRules(
                iterator_to_array(
                    $rulesParserFunc($rules)
                )
            )
            ->build();
    }

    /**
     * 
     * @param array $attributes 
     * @param array $hidden 
     * @param array $guarded 
     * @param string|null $name 
     * @param string|null $namespace 
     * @param string|null $model 
     * @return SourceFileInterface 
     * @throws PHPVariableException 
     */
    public static function buildDtoObjectDefinition(
        array $attributes = [],
        array $hidden = [],
        array $guarded = [],
        string $name = null,
        string $namespace = null,
        string $model = null
    ) {
        $component = (DataTransfertClassBuilder($attributes, $name, $namespace));
        if (is_string($model)) {
            $component = $component->bindModel(
                $model
            );
        }
        return $component
            ->setHidden($hidden ?? [])
            ->setGuarded($guarded ?? [])
            ->build();
    }

    /**
     * 
     * @param mixed|null $model 
     * @param mixed|null $service 
     * @param mixed|null $viewModel 
     * @param mixed|null $dto 
     * @param string|null $name 
     * @param string|null $namespace 
     * @return SourceFileInterface 
     */
    public static function buildController(
        $model = null,
        $service = null,
        $viewModel = null,
        $dto = null,
        string $name = null,
        string $namespace = null
    ) {
        $component = (MVCControllerBuilder($name, $namespace));
        // Check null state of the service parameter
        if (null !== $service) {
            $component = $component->bindService(is_string($service) ? $service : get_class($service));
        }
        // Check null state of the model parameter
        if (null !== $model) {
            $component = $component->bindModel(is_string($model) ? $model : get_class($model));
        }
        // Check null state of the viewModel parameter
        if (null !== $viewModel) {
            $component = $component->bindViewModel(is_string($viewModel) ? $viewModel : get_class($viewModel));
        }
        // Check null state of the dtoObject parameter
        if (null !== $dto) {
            $component = $component->bindDTOObject(is_string($dto) ? $dto : get_class($dto));
        }
        return $component->build();
    }

    public static function rebuildComponentPath(string $namespace, string $path)
    {
        $namespaceFolder = drewlabs_core_strings_after_last("\\", $namespace);
        $basename = Path($path)->basename();
        if (drewlabs_core_strings_to_lower_case($namespaceFolder) !== drewlabs_core_strings_to_lower_case($basename)) {
            // If the last part of both namespace and path are not the same
            $parts = array_reverse(explode("\\", $namespace));
            foreach ($parts as $value) {
                if (drewlabs_core_strings_contains($path, $value)) {
                    $path = sprintf("%s%s%s", rtrim(
                        $path,
                        DIRECTORY_SEPARATOR
                    ), DIRECTORY_SEPARATOR, ltrim(
                        drewlabs_core_strings_replace(
                            "\\",
                            DIRECTORY_SEPARATOR,
                            drewlabs_core_strings_after_last($value, $namespace)
                        ),
                        DIRECTORY_SEPARATOR
                    ));
                    break;
                }
            }
        }
        return $path;
    }
}
