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

use Drewlabs\CodeGenerator\Contracts\NamespaceComponent;
use Drewlabs\CodeGenerator\Contracts\Stringable;
use Drewlabs\GCli\Contracts\ORMModelDefinition;
use Drewlabs\GCli\IO\ScriptWriter;
use Drewlabs\GCli\PHPScript;
use Drewlabs\GCli\Plugins\Laravel\ControllerClassBuilder;
use Drewlabs\GCli\Plugins\Laravel\DataTransfertClassBuilder;
use Drewlabs\GCli\Plugins\Laravel\ORMModelBuilder;
use Drewlabs\GCli\Plugins\Laravel\PolicyClassBuilder;
use Drewlabs\GCli\Plugins\Laravel\ServiceClassBuilder;
use Drewlabs\GCli\Plugins\Laravel\ServiceInterfaceBuilder;
use Drewlabs\GCli\Plugins\Laravel\ServiceProviderBuilder;
use Drewlabs\GCli\Plugins\Laravel\ViewModelClassBuilder;

/**
 * Provides a proxy function to the {@link ScriptWriter} constructor.
 *
 * @return \Drewlabs\GCli\Contracts\ScriptWriter
 */
function ComponentsScriptWriter(string $srcPath)
{
    return new ScriptWriter($srcPath);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\GCli\Contracts\EloquentORMModelBuilder} constructor.
 *
 * @return ORMModelBuilder
 */
function EloquentORMModelBuilder(
    ORMModelDefinition $defintion,
    ?string $schema = null,
    ?string $path = null
) {
    return new ORMModelBuilder($defintion, $schema, $path);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\GCli\Plugins\Laravel\ControllerClassBuilder} constructor.
 *
 * @return ControllerClassBuilder
 */
function MVCControllerBuilder(
    ?string $name = null,
    ?string $namespace = null,
    ?string $path = null
) {
    return new ControllerClassBuilder($name, $namespace, $path);
}

/**
 * Provides a proxy function to the {@link \Drewlabs\GCli\Plugins\Laravel\ServiceClassBuilder} constructor.
 *
 * @return ServiceClassBuilder
 */
function MVCServiceBuilder(
    ?string $name = null,
    ?string $namespace = null,
    ?string $path = null
) {
    return new ServiceClassBuilder($name, $namespace, $path);
}

/**
 * Provides a proxy function to the  {@link \Drewlabs\GCli\Plugins\Laravel\ViewModelClassBuilder} constructor.
 *
 * @return ViewModelClassBuilder
 */
function ViewModelBuilder(
    ?string $name = null,
    ?string $namespace = null,
    ?string $path = null
) {
    return new ViewModelClassBuilder($name, $namespace, $path);
}

/**
 * Provides a proxy function to the  {@link \Drewlabs\GCli\Plugins\Laravel\DataTransfertClassBuilder} constructor.
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
 * Provides a proxy function to the  {@link \Drewlabs\GCli\PHPScriptFile} constructor.
 *
 * @return PHPScript
 */
function PHPScript(
    string $name,
    NamespaceComponent&Stringable $content,
    string $path,
    string $extension = 'php'
) {
    return new PHPScript($name, $content, $path, $extension);
}

/**
 * Creates policy builder class.
 *
 * @return PolicyClassBuilder
 */
function MVCPolicyBuilder(?string $name = null, ?string $namespace = null, ?string $path = null)
{
    return new PolicyClassBuilder($name, $namespace, $path);
}

/**
 * Creates a service interface builder instance.
 *
 * @return ServiceInterfaceBuilder
 */
function ServiceInterfaceBuilderProxy(?string $name = null, ?string $namespace = null, ?string $path = null)
{

    return new ServiceInterfaceBuilder($name, $namespace, $path);
}

/**
 * Creates new policy service providers instance.
 *
 * @return ServiceProviderBuilder
 */
function MVCServiceProviderBuilder(array $policies = [], array $bindings = [], ?string $namespace = null, ?string $path = null, ?string $name = null)
{
    return new ServiceProviderBuilder($policies, $bindings, $namespace, $path, $name);
}
