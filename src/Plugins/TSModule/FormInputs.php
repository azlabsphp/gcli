<?php

namespace Drewlabs\GCli\Plugins\TSModule;

use Drewlabs\GCli\Contracts\Type;

class FormInputs
{
    /** @var Type */
    private $type;

    /** @var string */
    private $module;


    /** @param Type $type */
    public function __construct(string $module, Type $type)
    {
        $this->module = $module;
        $this->type = $type;
    }


    public function __toString(): string
    {
        $importedInputs = [];
        $lines = [];


        return implode("\n", $lines);
    }
}
