<?php

namespace Drewlabs\ComponentGenerators\Proxy;

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
use Drewlabs\ComponentGenerators\ORMModelDefinition as ComponentGeneratorsORMModelDefinition;

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
 * @param string|null $name 
 * @param array $json_attributes 
 * @return DataTransfertClassBuilder 
 */
function DataTransfertClassBuilder(string $name = null, array $json_attributes = [])
{
    return new DataTransfertClassBuilder($name, $json_attributes);
}