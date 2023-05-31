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

namespace Drewlabs\GCli\Proxy;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Drewlabs\CodeGenerator\Contracts\Stringable;
use Drewlabs\GCli\Builders\ControllerClassBuilder;
use Drewlabs\GCli\Builders\DataTransfertClassBuilder;
use Drewlabs\GCli\Builders\EloquentORMModelBuilder;
use Drewlabs\GCli\Builders\PolicyClassBuilder;
use Drewlabs\GCli\Builders\PolicyServiceProviderBuilder;
use Drewlabs\GCli\Builders\ServiceClassBuilder;
use Drewlabs\GCli\Builders\ViewModelClassBuilder;
use Drewlabs\GCli\ComponentsScriptWriter;
use Drewlabs\GCli\Contracts\ORMModelDefinition;
use Drewlabs\GCli\Contracts\ScriptWriter;
use Drewlabs\GCli\ReverseEngineeringService;
use Drewlabs\GCli\PHP\PHPScriptFile;

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
 * Provides a proxy function to the {@link \Drewlabs\GCli\Builders\ControllerClassBuilder} constructor.
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
 * Provides a proxy function to the {@link \Drewlabs\GCli\Builders\ServiceClassBuilder} constructor.
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
 * Provides a proxy function to the  {@link \Drewlabs\GCli\Builders\ViewModelClassBuilder} constructor.
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
 * Provides a proxy function to the  {@link \Drewlabs\GCli\Builders\DataTransfertClassBuilder} constructor.
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
 * Provides a proxy function to the  {@link \Drewlabs\GCli\DatabaseSchemaReverseEngineeringRunner} constructor.
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
