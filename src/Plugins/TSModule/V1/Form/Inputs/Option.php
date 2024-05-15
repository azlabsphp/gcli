<?php

namespace Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs;

use Drewlabs\CodeGenerator\Helpers\Str as StrHelper;
use Drewlabs\GCli\Contracts\HasUniqueConstraint;
use Drewlabs\GCli\Contracts\Property;

class Option
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

    /** @var string */
    private $optionType;

    public function __construct(
        string $module,
        Property $property,
        bool $camelize = false,
        string $indent = "\t",
        int $index = null,
        string $optionType = 'select'
    ) {
        $this->module = $module;
        $this->property = $property;
        $this->camelize = $camelize;
        $this->indent = $indent ?? '';
        $this->index = $index;
        $this->optionType = $optionType ?? 'select';
    }


    public function __toString(): string
    {
        $propertyName = $this->property->name();
        $name = $this->camelize ? StrHelper::camelize($propertyName) : $propertyName;
        $isRequired = $this->property->required();

        $lines = [
            "{",
            sprintf("\tlabel: '%s',", sprintf("app.modules.%s.form.inputs.%s", $this->module, $name)),
            sprintf("\tname: '%s',", $propertyName),
            // We assume the input type to be an email input if the property name contains the word email
            sprintf("\ttype: '%s',", $this->optionType),
            "\tclasses: undefined,",
            "\tplaceholder: '...',",
            "\tvalue: null,",
            "\tdescription: undefined,",
            sprintf("\tindex: %s,", $this->index ? $this->index : 'undefined'),
            "\tisRepeatable: false,",
            "\tcontainerClass: 'input-col-sm-12',",
            "\t//# TODO: Provide list of possible options or use `optionsConfig` property to query data from backend source",
            "\t options: [],",
            "\t//optionsConfig: { source: { resource: 'http://localhost'}},",
            "\tconstraints: {",
            sprintf("\t\trequired: %s,", $isRequired ? 'true' : 'false'),
            "\t\tdisabled: false,",
        ];

        if ($this->property instanceof HasUniqueConstraint && $this->property->hasUniqueConstraint()) {
            $lines = array_merge($lines, [
                "\t\t//# TODO: column requires a unique constraint, consider adding it",
                "\t\t//unique: { fn: () => true }"
            ]);
        }

        $lines = array_merge($lines, [
            "\t}",
            "} as OptionsInputConfigInterface"
        ]);

        return implode("\n", array_map(function ($line) {
            return $this->indent ? sprintf("%s%s", $this->indent, $line) : $line;
        }, $lines));
    }
}
