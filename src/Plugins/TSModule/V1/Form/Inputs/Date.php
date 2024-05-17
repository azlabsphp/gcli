<?php

namespace Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs;

use Drewlabs\CodeGenerator\Helpers\Str;
use Drewlabs\GCli\Contracts\Property;

class Date
{
    /** @var string */
    private $module;

    /** @var Property */
    private $property;

    /** @var bool */
    private $camelize;

    /** @var string */
    private $indent;

    /** @var int */
    private $index;

    public function __construct(string $module, Property $property, bool $camelize = false, string $indent = "\t", int $index = null)
    {
        $this->module = $module;
        $this->property = $property;
        $this->camelize = $camelize;
        $this->indent = $indent ?? '';
        $this->index = $index;
    }


    public function __toString(): string
    {
        $propertyName = $this->property->name();
        $name = $this->camelize ? Str::camelize($propertyName) : $propertyName;

        $lines = [
            "{",
            sprintf("\tlabel: '%s',", sprintf("app.modules.%s.form.inputs.%s", $this->module, $name)),
            sprintf("\tname: '%s',", $propertyName),
            "\ttype: 'date',",
            "\tclasses: '',",
            "\tplaceholder: '...',",
            "\tvalue: null,",
            "\tdescription: '', // TODO: Add input description",
            sprintf("\tindex: %s,", $this->index ? $this->index : 'undefined'),
            "\tisRepeatable: false,",
            "\tcontainerClass: 'input-col-sm-12',",
            "\tconstraints: {",
            sprintf("\t\trequired: %s,", $this->property->required() ? 'true' : 'false'),
            "\t\tdisabled: false,",
            "\t}",
            "} as DateInput"
        ];

        return implode("\n", array_map(function ($line) {
            return $this->indent ? sprintf("%s%s", $this->indent, $line) : $line;
        }, $lines));
    }
}
