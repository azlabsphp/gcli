<?php

namespace Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs;

use Drewlabs\CodeGenerator\Helpers\Str as StrHelper;
use Drewlabs\GCli\Contracts\HasSizeProperty;
use Drewlabs\GCli\Contracts\HasUniqueConstraint;
use Drewlabs\GCli\Contracts\Property;


class Str
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
        $name = $this->camelize ? StrHelper::camelize($propertyName) : $propertyName;
        $isEmail = strpos($name, 'email') !== false ? true : false;
        $isRequired = $this->property->required();

        $lines = [
            "{",
            sprintf("\tlabel: '%s',", sprintf("app.modules.%s.form.inputs.%s", $this->module, $name)),
            sprintf("\tname: '%s',", $propertyName),
            // We assume the input type to be an email input if the property name contains the word email
            sprintf("\ttype: '%s',", $isEmail ? 'email' : "text"),
            "\tclasses: undefined,",
            "\tplaceholder: '...',",
            "\tvalue: null,",
            "\tdescription: undefined,",
            sprintf("\tindex: %s,", $this->index ? $this->index : 'undefined'),
            "\tisRepeatable: false,",
            "\tcontainerClass: 'input-col-sm-12',",
            "\tconstraints: {",
            sprintf("\t\trequired: %s,", $isRequired ? 'true' : 'false'),
            "\t\tdisabled: false,",
            sprintf("\t\temail: %s,", $isEmail ? 'true' : 'false'),
        ];

        if ($isRequired) {
            $lines[] = "\t\tmin: 1,";
        }

        if ($this->property instanceof HasSizeProperty && $this->property->hasSize()) {
            $lines[] = sprintf("\t\tmax: %s", $this->property->getSize());
        }

        if ($this->property instanceof HasUniqueConstraint && $this->property->hasUniqueConstraint()) {
            $lines = array_merge($lines, [
                "\t\t//# TODO: column requires a unique constraint, consider adding it",
                "\t\t//unique: { fn: () => true }"
            ]);
        }

        $lines = array_merge($lines, [
            "\t}",
            "} as TextInput"
        ]);

        return implode("\n", array_map(function ($line) {
            return $this->indent ? sprintf("%s%s", $this->indent, $line) : $line;
        }, $lines));
    }
    
}