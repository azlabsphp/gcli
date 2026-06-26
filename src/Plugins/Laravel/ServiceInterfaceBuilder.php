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

namespace Drewlabs\GCli\Plugins\Laravel;

use Drewlabs\CodeGenerator\Helpers\Str;

use function Drewlabs\CodeGenerator\Proxy\PHPInterface;

use Drewlabs\Contracts\Support\Actions\ActionHandler;
use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Factories\ComponentPath;

use Drewlabs\GCli\Plugins\Laravel\Traits\HasNamespaceAttribute;

use function Drewlabs\GCli\Proxy\PHPScript;

final class ServiceInterfaceBuilder implements AbstractBuilder
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

    /**
     * Creates class instances.
     *
     * @param (string|null)|null $name
     * @param (string|null)|null $namespace
     * @param (string|null)|null $path
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return void
     */
    public function __construct(
        ?string $name = null,
        ?string $namespace = null,
        ?string $path = null
    ) {
        $this->setName($name ? (!Str::endsWith($name, 'Interface') ? Str::camelize($name).'Interface' : Str::camelize($name)) : static::__NAME__);
        // Set the component write path
        $this->setWritePath($path ?? static::__PATH__);

        // Set the component namespace
        $this->setNamespace($namespace ?? static::__NAMESPACE__);
    }

    public function build()
    {
        $component = PHPInterface($this->name());
        $component
            ->addBaseInterface(ActionHandler::class)
            ->addToNamespace($this->package ?? static::__NAMESPACE__);

        // Returns the builded component
        return PHPScript(
            $component->getName(),
            $component,
            ComponentPath::new()->create($this->package ?? static::__NAMESPACE__, $this->path ?? static::__PATH__)
        )->setNamespace($component->getNamespace());
    }
}
