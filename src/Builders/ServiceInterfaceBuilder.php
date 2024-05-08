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

namespace Drewlabs\GCli\Builders;

use Drewlabs\CodeGenerator\Helpers\Str;

use function Drewlabs\CodeGenerator\Proxy\PHPInterface;

use Drewlabs\Contracts\Support\Actions\ActionHandler;
use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Helpers\ComponentBuilder;

use function Drewlabs\GCli\Proxy\PHPScript;

use Drewlabs\GCli\Traits\HasNamespaceAttribute;

class ServiceInterfaceBuilder implements AbstractBuilder
{
    use HasNamespaceAttribute;

    /**
     * class namespace.
     *
     * @var string
     */
    private const __NAMESPACE__ = 'App\\Contracts';

    /**
     * @var string
     */
    private const __NAME__ = 'TestServiceInterface';

    /**
     * @var string
     */
    private const __PATH__ = 'Contracts/';

    // /**
    //  * List of classes to imports.
    //  *
    //  * @var array
    //  */
    // private $classPaths = [
    //     ActionHandler::class
    // ];

    /**
     * Creates class instances.
     *
     * @param (string|null)|null $name
     * @param (string|null)|null $namespace
     * @param (string|null)|null $path
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws UnableToRetrieveMetadataException
     *
     * @return void
     */
    public function __construct(
        string $name = null,
        string $namespace = null,
        string $path = null
    ) {
        $this->setName($name ? (!Str::endsWith($name, 'Interface') ? Str::camelize($name).'Interface' : Str::camelize($name)) : self::__NAME__);
        // Set the component write path
        $this->setWritePath($path ?? self::__PATH__);

        // Set the component namespace
        $this->setNamespace($namespace ?? self::__NAMESPACE__);
    }

    public function build()
    {
        $component = PHPInterface($this->name());
        $component
            ->addBaseInterface(ActionHandler::class)
            ->addToNamespace($this->namespace_ ?? self::__NAMESPACE__);

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentBuilder::rebuildComponentPath($this->namespace_ ?? self::__NAMESPACE__, $this->path_ ?? self::__PATH__)
        )->setNamespace($component->getNamespace());
    }
}
