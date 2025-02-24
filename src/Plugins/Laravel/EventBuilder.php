<?php

namespace Drewlabs\GCli\Plugins\Laravel;


use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Factories\ComponentPath;
use Drewlabs\GCli\Plugins\Laravel\Traits\HasNamespaceAttribute;
use Drewlabs\CodeGenerator\Contracts\Blueprint;
use Drewlabs\CodeGenerator\Contracts\FunctionParameterInterface;
use Drewlabs\Core\Helpers\Str;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\GCli\Proxy\PHPScript;

final class EventBuilder implements AbstractBuilder
{
    use HasNamespaceAttribute;

    /** @var string */
    private const __NAMESPACE__ = 'App\\Events';

    /** @var string */
    private const __NAME__ = 'ExampleEvent';

    /** @var string */
    private const __PATH__ = 'app/Events/';

    /** @var FunctionParameterInterface[] */
    private $properties;

    public function __construct(Event $e, ?string $path = null)
    {
        $this->setName($e->getName() ?? self::__NAME__);
        $this->setNamespace($e->getNamespace() ?? self::__NAMESPACE__);
        $this->setWritePath($path ?? self::__PATH__);
        $this->properties = $e->getParams() ?? [];
    }

    public function build()
    {

        /** @var Blueprint */
        $component = PHPClass($this->name())
                                ->withPromotedProperties()
                                ->asFinal()
                                ->addConstructor($this->properties);

        $component = $component->addClassPath(Dispatchable::class)
                                ->addClassPath(SerializesModels::class);

        $component = $component->addTrait(Dispatchable::class)
                               ->addTrait(SerializesModels::class);

        foreach ($this->properties as $property) {
            $component = $component->addMethod(PHPClassMethod(
                sprintf('get%s', Str::camelize($property->name())),
                [],
                $property->getType() ?? 'mixed',
                'public',
                sprintf('returns `%s` property value', $property->name())
            )->addLine(sprintf("return \$this->%s", $property->name())));
        }

        return PHPScript(
            $component->getName(),
            $component,
            ComponentPath::new()->create($this->namespace_ ?? self::__NAMESPACE__, $this->path_ ?? self::__PATH__)
        )->setNamespace($component->getNamespace());
    }
}
