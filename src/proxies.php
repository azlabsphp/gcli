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

namespace Drewlabs\ComponentGenerators\Proxy;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Drewlabs\CodeGenerator\Contracts\Stringable;
use Drewlabs\ComponentGenerators\Builders\ControllerClassBuilder;
use Drewlabs\ComponentGenerators\Builders\DataTransfertClassBuilder;
use Drewlabs\ComponentGenerators\Builders\EloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\Builders\PolicyClassBuilder;
use Drewlabs\ComponentGenerators\Builders\PolicyServiceProviderBuilder;
use Drewlabs\ComponentGenerators\Builders\ServiceClassBuilder;
use Drewlabs\ComponentGenerators\Builders\ViewModelClassBuilder;
use Drewlabs\ComponentGenerators\ComponentsScriptWriter;
use Drewlabs\ComponentGenerators\Contracts\ORMColumnDefinition as ContractsORMColumnDefinition;
use Drewlabs\ComponentGenerators\Contracts\ORMModelDefinition;
use Drewlabs\ComponentGenerators\Contracts\ScriptWriter;
use Drewlabs\ComponentGenerators\ReverseEngineeringService;
use Drewlabs\ComponentGenerators\ORMColumnDefinition;
use Drewlabs\ComponentGenerators\ORMModelDefinition as ComponentGeneratorsORMModelDefinition;
use Drewlabs\ComponentGenerators\PHP\PHPScriptFile;

/**
 * Provides a proxy function to the {@link ScriptWriter} constructor.
 *
 * @return ScriptWriter
 */
function ComponentsScriptWriter(string $srcPath)
{
    return new ComponentsScriptWriter($srcPath);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\ComponentGenerators\Contracts\ContractsORMColumnDefinition} constructor.
 *
 * @param array $attributes
 *
 * @return ORMModelDefinition
 */
function ORMModelDefinition($attributes = [])
{
    return new ComponentGeneratorsORMModelDefinition($attributes);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\ComponentGenerators\Contracts\EloquentORMModelBuilder} constructor.
 *
 * @return EloquentORMModelBuilder
 */
function EloquentORMModelBuilder(
    ORMModelDefinition $defintion,
    ?string $schema = null,
    ?string $path = null
) {
    return new EloquentORMModelBuilder($defintion, $schema, $path);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\ComponentGenerators\Contracts\ContractsORMColumnDefinition} constructor.
 *
 * @param array $attributes
 *
 * @return ContractsORMColumnDefinition
 */
function ORMColumnDefinition($attributes = [])
{
    return new ORMColumnDefinition($attributes);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\ComponentGenerators\Builders\ControllerClassBuilder} constructor.
 *
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
 * Provides a proxy function to the {@link \Drewlabs\ComponentGenerators\Builders\ServiceClassBuilder} constructor.
 *
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
 * Provides a proxy function to the  {@link \Drewlabs\ComponentGenerators\Builders\ViewModelClassBuilder} constructor.
 *
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
 * Provides a proxy function to the  {@link \Drewlabs\ComponentGenerators\Builders\DataTransfertClassBuilder} constructor.
 *
 * @return DataTransfertClassBuilder
 */
function DataTransfertClassBuilder(
    array $json_attributes = [],
    ?string $name = null,
    ?string $namespace = null,
    ?string $path = null
) {
    return new DataTransfertClassBuilder($json_attributes, $name, $namespace, $path);
}

/**
 * Provides a proxy function to the  {@link \Drewlabs\ComponentGenerators\PHP\PHPScriptFile} constructor.
 *
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
 * Provides a proxy function to the  {@link \Drewlabs\ComponentGenerators\DatabaseSchemaReverseEngineeringRunner} constructor.
 *
 * @param string $blocComponentNamespace
 *
 * @return ReverseEngineeringService
 */
function DatabaseSchemaReverseEngineeringRunner(
    AbstractSchemaManager $manager,
    string $blocComponentPath,
    ?string $blocComponentNamespace = 'App'
) {
    return new ReverseEngineeringService(
        $manager,
        $blocComponentPath,
        $blocComponentNamespace
    );
}

/**
 * Creates policy builder class
 * 
 * @param string|null $name 
 * @param string|null $namespace 
 * @param string|null $path 
 * @return PolicyClassBuilder 
 */
function MVCPolicyBuilder(string $name = null, string $namespace = null, string $path = null)
{
    return new PolicyClassBuilder($name, $namespace, $path);
}

/**
 * Creates new policy service providers instance
 * 
 * @param array $policies 
 * @param string|null $namespace 
 * @param string|null $path 
 * @param string|null $name 
 * @return PolicyServiceProviderBuilder 
 */
function MVCPolicyServiceProviderBuilder(array $policies = [],  string $namespace = null, string $path = null, string $name = null)
{
    return new PolicyServiceProviderBuilder($policies);
}
