<?php

namespace Drewlabs\GCli\Plugins\Laravel;

use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Factories\ComponentPath;
use Drewlabs\GCli\Plugins\Laravel\Traits\HasNamespaceAttribute;
use Drewlabs\CodeGenerator\Contracts\Blueprint;
use Drewlabs\Core\Helpers\Str;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPFunctionParameter;
use function Drewlabs\GCli\Proxy\PHPScript;

final class ListenerBuilder implements AbstractBuilder
{
    use HasNamespaceAttribute;

    /** @var string */
    private const __NAMESPACE__ = 'App\\Listener';

    /** @var string */
    private const __NAME__ = 'ExampleEventListener';

    /** @var string */
    private const __PATH__ = 'app/Listeners/';

    /** @var string */
    private $event;

    public function __construct(string $event, string $name, ?string $namespace = null, ?string $path = null)
    {
        $this->setName($name ?? self::__NAME__);
        $this->setWritePath($path ?? self::__PATH__);
        $this->setNamespace($namespace ?? self::__NAMESPACE__);
        $this->event = $event;
    }

    public function build()
    {

        /** @var Blueprint */
        $component = PHPClass($this->name())->withPromotedProperties()->addConstructor()->asFinal();
        if ($isClasspath = Str::contains(rtrim($this->event, '\\'), '\\')) {
            $component = $component->addClassPath($this->event);
        }

        $paramType = $this->event;
        if ($isClasspath) {
            $values = explode('\\', rtrim($this->event, '\\'));
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
