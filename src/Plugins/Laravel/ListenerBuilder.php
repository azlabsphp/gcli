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

use Drewlabs\CodeGenerator\Contracts\Blueprint;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;

use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;

use Drewlabs\GCli\Factories\ComponentPath;
use Drewlabs\GCli\Plugins\Laravel\Observers\Listener;
use Drewlabs\GCli\Plugins\Laravel\Traits\HasNamespaceAttribute;

use function Drewlabs\GCli\Proxy\PHPScript;

final class ListenerBuilder implements AbstractBuilder
{
    use HasNamespaceAttribute;

    /** @var string */
    private const __NAMESPACE__ = 'App\\Listeners';

    /** @var string */
    private const __PATH__ = 'app/Listeners';

    /** @var Listener */
    private $listener;

    public function __construct(Listener $listener, ?string $path = null)
    {
        $this->setName($listener->getName());
        $this->setNamespace($listener->getNamespace());
        $this->setWritePath($path ?? static::__PATH__);
        $this->listener = $listener;
    }

    public function build()
    {
        /** @var Blueprint */
        $component = PHPClass($this->name())
                            ->withPromotedProperties()
                            ->addConstructor()
                            ->addToNamespace($this->package ?? static::__NAMESPACE__)
                            ->asFinal();

        if ($isClasspath = Str::contains(rtrim($classPath = $this->listener->getEvent()->getClasspath(), '\\'), '\\')) {
            $component = $component->addClassPath($classPath);
        }

        $paramType = $classPath;
        if ($isClasspath) {
            $values = explode('\\', rtrim($classPath, '\\'));
            $paramType = $values[\count($values) - 1];
        }

        $component = $component->addMethod(PHPClassMethod('handle', [PHPFunctionParameter('e', $paramType)], 'void', 'public', 'Handle the event.'));

        return PHPScript(
            $component->getName(),
            $component,
            ComponentPath::new()->create($this->package ?? static::__NAMESPACE__, $this->path ?? static::__PATH__)
        )->setNamespace($component->getNamespace());
    }
}
