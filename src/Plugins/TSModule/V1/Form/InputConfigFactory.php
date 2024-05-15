<?php

namespace Drewlabs\GCli\Plugins\TSModule\V1\Form;

use Drewlabs\GCli\Contracts\HasExistConstraint;
use Drewlabs\GCli\Contracts\Property;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Date;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Number;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Option;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Str;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Textarea;

class InputConfigFactory
{
    /** @var string */
    private $module;

    /** @var bool */
    private $camelize;

    /**
     * Class constructor
     * 
     * @param string $module 
     * @param bool $camelize 
     */
    public function __construct(string $module, bool $camelize = false)
    {
        $this->module = $module;
        $this->camelize = $camelize;
    }

    public function createInputConfig(Property $property, string $indent = "\t", int $index = null)
    {
        if ($property instanceof HasExistConstraint && $property->hasExistContraint()) {
            return new Option($this->module, $property, $this->camelize, $indent, $index, 'select');
        }

        $factories = [
            'date' => function (string $module, Property $property, bool $camelize) use ($indent, $index) {
                return new Date($module, $property, $camelize, $indent, $index);
            },
            'datetime' => function (string $module, Property $property, bool $camelize) use ($indent, $index) {
                return new Date($module, $property, $camelize, $indent, $index);
            },
            'float' => function (string $module, Property $property, bool $camelize) use ($indent, $index) {
                return new Number($module, $property, $camelize, $indent, $index);
            },
            'int' => function (string $module, Property $property, bool $camelize) use ($indent, $index) {
                return new Number($module, $property, $camelize, $indent, $index);
            },
            'integer' => function (string $module, Property $property, bool $camelize) use ($indent, $index) {
                return new Number($module, $property, $camelize, $indent, $index);
            },
            'decimal' => function (string $module, Property $property, bool $camelize) use ($indent, $index) {
                return new Number($module, $property, $camelize, $indent, $index);
            },
            'string' => function (string $module, Property $property, bool $camelize) use ($indent, $index) {
                return new Str($module, $property, $camelize, $indent, $index);
            },
            'text' => function (string $module, Property $property, bool $camelize) use ($indent, $index) {
                return new Textarea($module, $property, $camelize, $indent, $index);
            },
        ];

        $fn = $factories[strtolower($property->getRawType())] ?? function () {
            return null;
        };

        return $fn($this->module, $property, $this->camelize);
    }
}
