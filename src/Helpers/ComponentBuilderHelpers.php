<?php

namespace Drewlabs\ComponentGenerators\Helpers;

use Drewlabs\CodeGenerator\Exceptions\PHPVariableException;

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
     * @param string $namespace 
     * @param array $columns 
     * @param mixed $basePath 
     * @param string $primaryKey 
     * @param bool $increments 
     * @param bool $vm 
     * @return void 
     */
    public static function buildModelDefinition(
        string $table,
        $basePath,
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
        ComponentsScriptWriter($basePath)->write($component->build());
    }

    /**
     * 
     * @param string $name 
     * @param string $namespace 
     * @param mixed $basePath 
     * @param string|null $model 
     * @return void 
     */
    public static function buildServiceDefinition(
        $basePath,
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
        (ComponentsScriptWriter($basePath))->write(
            $component->build()
        );
    }

    /**
     * 
     * @param mixed $basePath 
     * @param bool $handleSignleAction 
     * @param array $rules 
     * @param array $updateRules 
     * @param string|null $name 
     * @param string|null $namespace 
     * @param string|null $model 
     * @return void 
     * @throws PHPVariableException 
     */
    public static function buildViewModelDefinition(
        $basePath,
        bool $handleSignleAction = false,
        array $rules = [],
        array $updateRules = [],
        string $name = null,
        string $namespace = null,
        string $model = null
    ) {
        $rulesParserFunc = function ($definitions) {
            foreach (array_filter($definitions, function ($definition) {
                return (null !== $definition) && drewlabs_core_strings_contains($definition, '=');
            }) as $definition) {
                $key = drewlabs_core_strings_before('=', $definition);
                $value = drewlabs_core_strings_after('=', $definition);
                yield $key => $value;
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
                (iterator_to_array($rulesParserFunc($updateRules)))
            );
        } else {
            $component = $component->asSingleActionValidator();
        }
        (ComponentsScriptWriter($basePath))->write(
            $component->addInputsTraits()
                ->addFileInputTraits()
                ->addAuthenticatableTraits()
                ->setRules(
                    iterator_to_array(
                        $rulesParserFunc($rules)
                    )
                )
                ->build()
        );
    }

    /**
     * 
     * @param mixed $basePath 
     * @param array $attributes
     * @param array $hidden 
     * @param array $guarded 
     * @param string|null $name 
     * @param string|null $namespace 
     * @param string|null $model 
     * @return void 
     * @throws PHPVariableException 
     */
    public static function buildDtoObjectDefinition(
        $basePath,
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
        (ComponentsScriptWriter($basePath))->write(
            $component
                ->setHidden($hidden ?? [])
                ->setGuarded($guarded ?? [])
                ->build()
        );
    }

    /**
     * 
     * @param mixed $basePath 
     * @param string|stdClass|null $model 
     * @param string|stdClass|null $service 
     * @param string|stdClass|null $viewModel 
     * @param string|stdClass|null $dto 
     * @param string|null $name 
     * @param string|null $namespace 
     * @return void
     * @throws PHPVariableException 
     */
    public static function buildController(
        $basePath,
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
        ComponentsScriptWriter($basePath)->write($component->build());
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
