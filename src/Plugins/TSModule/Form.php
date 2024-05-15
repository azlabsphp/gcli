<?php

namespace Drewlabs\GCli\Plugins\TSModule;

use Drewlabs\GCli\Contracts\Type;

class Form
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
        $lines = [
            '// import { environment } from \'src/environments/environment\';',
            sprintf("import { FormConfigInterface %s } from '@azlabsjs/smart-form-core';", count($importedInputs) !== 0 ? sprintf(", %s", implode(", ", $importedInputs)) : ""),
            "",
            '/** Exported module form configuration */',
            "export const form = {",
            "\t//id: ,",
            sprintf("\ttitle: 'app.modules.%s.form.title',", $this->module),
            sprintf("\t//endpointURL: environment.api.endpoints.%s,", $this->module),
            sprintf("\tmethod: 'POST',"),
            // TODO: Add form inputs generator implementation
        ];

        $lines[] = "} as FormConfigInterface;";
        return implode("\n", $lines);
    }
}
