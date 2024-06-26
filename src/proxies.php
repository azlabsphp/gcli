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

namespace Drewlabs\GCli\Proxy;

use Drewlabs\CodeGenerator\Contracts\Stringable;
use Drewlabs\GCli\Builders\ControllerClassBuilder;
use Drewlabs\GCli\Builders\DataTransfertClassBuilder;
use Drewlabs\GCli\Builders\ORMModelBuilder;
use Drewlabs\GCli\Builders\PolicyClassBuilder;
use Drewlabs\GCli\Builders\ServiceClassBuilder;
use Drewlabs\GCli\Builders\ServiceInterfaceBuilder;
use Drewlabs\GCli\Builders\ServiceProviderBuilder;
use Drewlabs\GCli\Builders\ViewModelClassBuilder;
use Drewlabs\GCli\ComponentsScriptWriter;
use Drewlabs\GCli\Contracts\ORMModelDefinition;
use Drewlabs\GCli\Contracts\ScriptWriter;
use Drewlabs\GCli\ModulesIteratorFactory;
use Drewlabs\GCli\PHP\PHPScriptFile;
use Drewlabs\GCli\Validation\RulesFactory;

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
 * Provides a proxy function to the {@link \Drewlabs\GCli\Contracts\EloquentORMModelBuilder} constructor.
 *
 * @return ORMModelBuilder
 */
function EloquentORMModelBuilder(
    ORMModelDefinition $defintion,
    string $schema = null,
    string $path = null
) {
    return new ORMModelBuilder($defintion, $schema, $path);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\GCli\Builders\ControllerClassBuilder} constructor.
 *
 * @return ControllerClassBuilder
 */
function MVCControllerBuilder(
    string $name = null,
    string $namespace = null,
    string $path = null
) {
    return new ControllerClassBuilder($name, $namespace, $path);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\GCli\Builders\ServiceClassBuilder} constructor.
 *
 * @return ServiceClassBuilder
 */
function MVCServiceBuilder(
    string $name = null,
    string $namespace = null,
    string $path = null
) {
    return new ServiceClassBuilder($name, $namespace, $path);
}

/**
 * Provides a proxy function to the  {@link \Drewlabs\GCli\Builders\ViewModelClassBuilder} constructor.
 *
 * @return ViewModelClassBuilder
 */
function ViewModelBuilder(
    string $name = null,
    string $namespace = null,
    string $path = null
) {
    return new ViewModelClassBuilder($name, $namespace, $path);
}

/**
 * Provides a proxy function to the  {@link \Drewlabs\GCli\Builders\DataTransfertClassBuilder} constructor.
 *
 * @return DataTransfertClassBuilder
 */
function DataTransfertClassBuilder(
    array $json_attributes = [],
    string $name = null,
    string $namespace = null,
    string $path = null
) {
    return new DataTransfertClassBuilder($json_attributes, $name, $namespace, $path);
}

/**
 * Provides a proxy function to the  {@link \Drewlabs\GCli\PHP\PHPScriptFile} constructor.
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
 * @deprecated
 *
 * Provides a proxy function to the  {@link \Drewlabs\GCli\DatabaseSchemaReverseEngineeringRunner} constructor
 *
 * @return ModulesIteratorFactory
 */
function DatabaseSchemaReverseEngineeringRunner(RulesFactory $factory, string $directory, ?string $namespace = 'App')
{
    return new ModulesIteratorFactory($factory, $directory, $namespace);
}

/**
 * Creates policy builder class.
 *
 * @return PolicyClassBuilder
 */
function MVCPolicyBuilder(string $name = null, string $namespace = null, string $path = null)
{
    return new PolicyClassBuilder($name, $namespace, $path);
}

/**
 * Creates a service interface builder instance.
 *
 * @return ServiceInterfaceBuilder
 */
function ServiceInterfaceBuilderProxy(string $name = null, string $namespace = null, string $path = null)
{

    return new ServiceInterfaceBuilder($name, $namespace, $path);
}

/**
 * Creates new policy service providers instance.
 *
 * @return ServiceProviderBuilder
 */
function MVCServiceProviderBuilder(array $policies = [], array $bindings = [], string $namespace = null, string $path = null, string $name = null)
{
    return new ServiceProviderBuilder($policies, $bindings, $namespace, $path, $name);
}
