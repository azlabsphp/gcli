<?php

namespace Drewlabs\ComponentGenerators\Proxy;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Drewlabs\CodeGenerator\Contracts\Stringable;
use Drewlabs\ComponentGenerators\Builders\ControllerClassBuilder;
use Drewlabs\ComponentGenerators\Builders\DataTransfertClassBuilder;
use Drewlabs\ComponentGenerators\Builders\EloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\Builders\ServiceClassBuilder;
use Drewlabs\ComponentGenerators\Builders\ViewModelClassBuilder;
use Drewlabs\ComponentGenerators\ComponentsScriptWriter;
use Drewlabs\ComponentGenerators\Contracts\ScriptWriter;
use Drewlabs\ComponentGenerators\Contracts\ORMModelDefinition;
use Drewlabs\ComponentGenerators\Contracts\EloquentORMModelBuilder as ContractsEloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\ORMColumnDefinition;
use Drewlabs\ComponentGenerators\Contracts\ORMColumnDefinition as ContractsORMColumnDefinition;
use Drewlabs\ComponentGenerators\DatabaseSchemaReverseEngineeringRunner;
use Drewlabs\ComponentGenerators\ORMModelDefinition as ComponentGeneratorsORMModelDefinition;
use Drewlabs\ComponentGenerators\PHP\PHPScriptFile;

/**
 * Provides a proxy function to the {@link ScriptWriter} constructor
 * 
 * @param string $srcPath 
 * @return ScriptWriter 
 */
function ComponentsScriptWriter(string $srcPath)
{
    return new ComponentsScriptWriter($srcPath);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\ComponentGenerators\Contracts\ContractsORMColumnDefinition} constructor
 * 
 * @param array $attributes 
 * @return ORMModelDefinition 
 */
function ORMModelDefinition($attributes = [])
{
    return new ComponentGeneratorsORMModelDefinition($attributes);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\ComponentGenerators\Contracts\EloquentORMModelBuilder} constructor
 * 
 * @param ORMModelDefinition $defintion 
 * @param null|string $path 
 * @return ContractsEloquentORMModelBuilder 
 */
function EloquentORMModelBuilder(
    ORMModelDefinition $defintion,
    ?string $path = null
) {
    return new EloquentORMModelBuilder($defintion, $path);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\ComponentGenerators\Contracts\ContractsORMColumnDefinition} constructor
 * 
 * @param array $attributes 
 * @return ContractsORMColumnDefinition 
 */
function ORMColumnDefinition($attributes = [])
{
    return new ORMColumnDefinition($attributes);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\ComponentGenerators\Builders\ControllerClassBuilder} constructor
 * 
 * @param null|string $name 
 * @param null|string $namespace 
 * @param null|string $path 
 * @return ControllerClassBuilder 
 */
function MVCControllerBuilder(
    ?string $name = null,
    ?string $namespace = null,
    ?string $path = null
) {
    return new ControllerClassBuilder(
        $name,
        $namespace,
        $path
    );
}

/**
 * Provides a proxy function to the {@link \Drewlabs\ComponentGenerators\Builders\ServiceClassBuilder} constructor
 * 
 * @param null|string $name 
 * @param null|string $namespace 
 * @param null|string $path 
 * @return ServiceClassBuilder 
 */
function MVCServiceBuilder(
    ?string $name = null,
    ?string $namespace = null,
    ?string $path = null
) {
    return new ServiceClassBuilder(
        $name,
        $namespace,
        $path
    );
}

/**
 * Provides a proxy function to the  {@link \Drewlabs\ComponentGenerators\Builders\ViewModelClassBuilder} constructor
 * 
 * @param null|string $name 
 * @param null|string $namespace 
 * @param null|string $path 
 * @return ViewModelClassBuilder 
 */
function ViewModelBuilder(
    ?string $name = null,
    ?string $namespace = null,
    ?string $path = null
) {
    return new ViewModelClassBuilder(
        $name,
        $namespace,
        $path
    );
}

/**
 * Provides a proxy function to the  {@link \Drewlabs\ComponentGenerators\Builders\DataTransfertClassBuilder} constructor
 * 
 * @param array $json_attributes 
 * @param string|null $name 
 * @param null|string $namespace 
 * @param string|null $path 
 * @return DataTransfertClassBuilder 
 */
function DataTransfertClassBuilder(
    array $json_attributes = [],
    string $name = null,
    ?string $namespace = null,
    string $path = null
) {
    return new DataTransfertClassBuilder($json_attributes, $name, $namespace, $path);
}

/**
 * Provides a proxy function to the  {@link \Drewlabs\ComponentGenerators\PHP\PHPScriptFile} constructor
 * 
 * @param string $name 
 * @param Stringable $content 
 * @param string $path 
 * @param string $extension 
 * @return PHPScriptFile 
 */
function PHPScript(
    string $name,
    Stringable $content,
    string $path,
    string $extension = 'php'
) {
    return new PHPScriptFile($name, $content, $path, $extension);
}

/**
 * Provides a proxy function to the  {@link \Drewlabs\ComponentGenerators\DatabaseSchemaReverseEngineeringRunner} constructor
 * 
 * @param AbstractSchemaManager $manager 
 * @param string $blocComponentPath 
 * @param string $blocComponentNamespace 
 * @return DatabaseSchemaReverseEngineeringRunner 
 */
function DatabaseSchemaReverseEngineeringRunner(
    AbstractSchemaManager $manager,
    string $blocComponentPath,
    string $blocComponentNamespace = 'App'
) {
    return new DatabaseSchemaReverseEngineeringRunner(
        $manager,
        $blocComponentPath,
        $blocComponentNamespace
    );
}
