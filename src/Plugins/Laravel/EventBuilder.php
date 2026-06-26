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
use Drewlabs\CodeGenerator\Contracts\FunctionParameterInterface;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\CodeGenerator\Proxy\PHPClassMethod;
use function Drewlabs\CodeGenerator\Proxy\PHPClassProperty;

use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\ComponentBuilder as AbstractBuilder;
use Drewlabs\GCli\Factories\ComponentPath;
use Drewlabs\GCli\Plugins\Laravel\Observers\Event;

use Drewlabs\GCli\Plugins\Laravel\Traits\HasNamespaceAttribute;

use function Drewlabs\GCli\Proxy\PHPScript;
use Illuminate\Queue\SerializesModels;

final class EventBuilder implements AbstractBuilder
{
    use HasNamespaceAttribute;

    /** @var string */
    private const __NAMESPACE__ = 'App\\Events';

    /** @var string */
    private const __PATH__ = 'app/Events/';

    /** @var FunctionParameterInterface[] */
    private $properties;

    public function __construct(Event $e, ?string $path = null)
    {
        $this->setName($e->getName());
        $this->setNamespace($e->getNamespace());
        $this->setWritePath($path ?? static::__PATH__);
        $this->properties = $e->getParams();
    }

    public function build()
    {
        /** @var Blueprint */
        $component = PHPClass($this->name())
            ->withPromotedProperties()
            ->addConstructor($this->properties)
            ->addToNamespace($this->package ?? static::__NAMESPACE__)
            ->asFinal();

        foreach ($this->properties as $property) {
            $component = $component->addProperty(PHPClassProperty($property->name(), $property->getType(), PHPTypesModifiers::PRIVATE, '', sprintf('class %s property', $property->name())));
        }

        $component = $component->addClassPath(SerializesModels::class)
            ->addTrait(SerializesModels::class);

        if (class_exists('\Illuminate\Foundation\Events\Dispatchable')) {
            $component = $component->addClassPath(\Illuminate\Foundation\Events\Dispatchable::class)
                ->addTrait(\Illuminate\Foundation\Events\Dispatchable::class);
        }

        foreach ($this->properties as $property) {
            $component = $component->addMethod(PHPClassMethod(
                sprintf('get%s', Str::camelize($property->name())),
                [],
                $property->getType() ?? 'mixed',
                'public',
                sprintf('returns `%s` property value', $property->name())
            )->addLine(sprintf('return $this->%s', $property->name())));
        }

        return PHPScript(
            $component->getName(),
            $component,
            ComponentPath::new()->create($this->package ?? static::__NAMESPACE__, $this->path ?? static::__PATH__)
        )->setNamespace($component->getNamespace());
    }
}
