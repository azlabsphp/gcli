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

namespace Drewlabs\GCli\Plugins\TSModule\V1\Form;

use Drewlabs\Core\Helpers\Str;
use Drewlabs\GCli\Contracts\HasExistConstraint;
use Drewlabs\GCli\Contracts\HasModuleMetadata;
use Drewlabs\GCli\Contracts\HasRelations;
use Drewlabs\GCli\Contracts\Property;
use Drewlabs\GCli\Contracts\ReversibleRelation;
use Drewlabs\GCli\Contracts\Type;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Group;

class Config
{
    /** @var Type */
    private $type;

    /** @var string|null */
    private $module;

    /** @var bool */
    private $camelize;

    /**
     * Class constructor.
     */
    public function __construct(Type $type, ?string $module = null, bool $camelize = false)
    {
        $this->type = $type;
        $this->module = $module;
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
            'group' => 'InputGroup'
        ];
        $values = array_map(function (Property $property) use (&$importedInputs, $factories) {
            $factory = new InputConfigFactory($this->module, $this->camelize);

            if ($property instanceof HasExistConstraint && $property->hasExistContraint()) {
                $importedInputs[] = 'OptionsInputConfigInterface';
            } elseif (null !== ($typeName = $factories[strtolower($property->getRawType())] ?? null)) {
                $importedInputs[] = $typeName;
            }

            return $factory->createInputConfig($property, "\t\t");
        }, $this->type->getProperties());

        $groups = [];
        $groupsImports = [];
        if ($this->type instanceof HasRelations) {
            $names = [];
            foreach ($this->type->getRelations() as $value) {
                if (!($value instanceof HasModuleMetadata)) {
                    continue;
                }

                if ($value instanceof ReversibleRelation && $value->isInverse()) {
                    continue;
                }
                $name = $value->getName();
                if (isset($names[$name])) {
                    $names[$name] += 1;
                }

                $inputConfig = $value->getModuleName() ? sprintf('%sInputConfigs', lcfirst(Str::camelize($value->getModuleName(), false))) : 'inputConfigs';
                $import = sprintf('import { %s } from \'../%s\';', $inputConfig, str_replace('_', '-', $value->getModuleName() ?? ''));
                if (!in_array($import, $groupsImports)) {
                    $groupsImports[] = $import;
                }

                $inputName = isset($names[$name]) ? sprintf("%s_%d", $name, intval($names[$name])) : $name;
                $group = new Group($inputName, $inputConfig, false, $value->multi(), $this->module, false, "\t\t");
                $groups[] = $group;

                $names[$name] = 0;
            }
        }
        if (!empty($groups)) {
            $values = array_merge($values, $groups);
            $importedInputs[] = 'InputGroup';
        }

        $importedInputs = array_unique($importedInputs);
        $lines = [
            sprintf("import { FormConfigInterface %s } from '@azlabsjs/smart-form-core';", 0 !== \count($importedInputs) ? sprintf(', %s', implode(', ', $importedInputs)) : ''),
            ...$groupsImports,
            '',
            '/** Exported module form configuration */',
            'export const form = {',
            "\t//id: ,",
            $this->module ? sprintf("\ttitle: 'app.modules.%s.form.title',", $this->module) : sprintf("\ttitle: 'title',"),
            "\tmethod: 'POST',",
            // TODO: Add form inputs generator implementation
            "\tcontrolConfigs: [",
            implode(",\n", array_filter($values, static function ($value) {
                return null !== $value;
            })),
            "\t]",
        ];
        $lines[] = '} as FormConfigInterface;';

        $lines[] = '';

        $lines[] = '/** Exported form factory function */';
        $lines[] = 'export function createFormConfig(url: string, method: string = \'POST\') {';
        $lines[] = "\treturn {...form, endpointURL: url, method} as FormConfigInterface;";
        $lines[] = '}';

        $lines[] = '';
        $lines[] = '/** Exported inputs configurations */';
        $lines[] = sprintf('export const %s = form.controlConfigs', $this->module ? sprintf('%sInputConfigs', lcfirst(Str::camelize($this->module, false))) : 'inputConfigs');

        return implode("\n", $lines);
    }
}
