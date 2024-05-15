<?php

namespace Drewlabs\GCli\Plugins\TSModule\V1\Form;

use Drewlabs\GCli\Contracts\HasExistConstraint;
use Drewlabs\GCli\Contracts\Property;
use Drewlabs\GCli\Contracts\Type;

class Config
{

    /** @var Type */
    private $type;

    /** @var string */
    private $module;

    /** @var bool */
    private $camelize;


    /**
     * Class constructor
     * 
     * @param string $module 
     * @param Type $type 
     * @param bool $camelize 
     */
    public function __construct(string $module, Type $type, bool $camelize = false)
    {
        $this->module = $module;
        $this->type = $type;
        $this->camelize = $camelize;
    }


    public function __toString(): string
    {
        $importedInputs = [];

        /** List of database type to form input type declaration */
        $factories = [
            'date' => 'DateInput',
            'datetime' => 'DateInput',
            'float' => 'NumberInput',
            'int' => 'NumberInput',
            'integer' => 'NumberInput',
            'decimal' => 'NumberInput',
            'string' => 'TextInput',
            'text' => 'TextAreaInput',
        ];
        $values =  array_map(function (Property $property) use (&$importedInputs, $factories) {
            $factory = new InputConfigFactory($this->module, $this->camelize);

            if ($property instanceof HasExistConstraint && $property->hasExistContraint()) {
                $importedInputs[] = 'OptionsInputConfigInterface';
            } else if (!is_null($typeName = $factories[strtolower($property->getRawType())] ?? null)) {
                $importedInputs[] = $typeName;
            }
            return $factory->createInputConfig($property, "\t\t");
        }, $this->type->getProperties());

        $importedInputs = array_unique($importedInputs);
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
            "\tcontrolConfigs: [",
            implode(",\n", array_filter($values, function ($value) {
                return !is_null($value);
            })),
            "\t]"
        ];

        $lines[] = "} as FormConfigInterface;";
        return implode("\n", $lines);
    }
}
