<?php

namespace Drewlabs\GCli\Plugins\Laravel;

use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Factories\ComponentPath;
use Drewlabs\GCli\Plugins\Laravel\Traits\HasNamespaceAttribute;
use Drewlabs\CodeGenerator\Contracts\Blueprint;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Plugins\Laravel\Observers\Listener;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;
use function Drewlabs\GCli\Proxy\PHPScript;

final class ListenerBuilder implements AbstractBuilder
{
    use HasNamespaceAttribute;

    /** @var string */
    private const __NAMESPACE__ = 'App\\Listeners';

    /** @var string */
    private const __NAME__ = 'ExampleEventListener';

    /** @var string */
    private const __PATH__ = 'app/Listeners';

    /** @var Listener */
    private $listener;

    public function __construct(Listener $listener, ?string $path = null)
    {
        $this->setName($listener->getName() ?? self::__NAME__);
        $this->setNamespace($listener->getNamespace() ?? self::__NAMESPACE__);
        $this->setWritePath($path ?? self::__PATH__);
        $this->listener = $listener;
    }

    public function build()
    {
        /** @var Blueprint */
        $component = PHPClass($this->name())
                            ->withPromotedProperties()
                            ->addConstructor()
                            ->addToNamespace($this->namespace_ ?? self::__NAMESPACE__)
                            ->asFinal();
                            
        if ($isClasspath = Str::contains(rtrim($classPath = $this->listener->getEvent()->getClasspath(), '\\'), '\\')) {
            $component = $component->addClassPath($classPath);
        }

        $paramType = $classPath;
        if ($isClasspath) {
            $values = explode('\\', rtrim($classPath, '\\'));
            $paramType = $values[count($values) - 1];
        }

        $component = $component->addMethod(PHPClassMethod('handle', [PHPFunctionParameter('e', $paramType)], 'void', 'public', 'Handle the event.'));

        return PHPScript(
            $component->getName(),
            $component,
            ComponentPath::new()->create($this->namespace_ ?? self::__NAMESPACE__, $this->path_ ?? self::__PATH__)
        )->setNamespace($component->getNamespace());
    }
}
