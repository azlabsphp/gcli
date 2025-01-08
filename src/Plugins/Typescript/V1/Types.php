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

namespace Drewlabs\GCli\Plugins\Typescript\V1;

use Drewlabs\CodeGenerator\Helpers\Str;
use Drewlabs\GCli\Contracts\HasModuleMetadata;
use Drewlabs\GCli\Contracts\HasRelations;
use Drewlabs\GCli\Contracts\ReversibleRelation;
use Drewlabs\GCli\Contracts\Type;

class Types
{
    /** @var Type */
    private $type;

    /** @var bool */
    private $camelize;

    /**
     * Class constructor.
     */
    public function __construct(Type $type, bool $camelize = false)
    {
        $this->type = $type;
        $this->camelize = $camelize;
    }

    public function __toString(): string
    {
        /** @var string */
        $builtType = Str::camelize($this->type->name());
        $lines = [
            'import { BuiltType, TypeOf } from \'@azlabsjs/built-type\';',
            '',
            sprintf('/** @description %s type builder declaration */', $builtType),
            $this->camelize ? sprintf('export const %s = BuiltType._object(', $builtType) : sprintf('export const %s = BuiltType._object({', $builtType),
        ];

        if ($this->camelize) {
            $lines[] = "\t{";
        }

        /** Database or PHP type to built-type library mapping declaration */
        $builtTypes = [
            'string' => 'BuiltType._str({ coerce: true })',
            'text' => 'BuiltType._str({ coerce: true })',
            'date' => 'BuiltType._date({ coerce: true })',
            'datetime' => 'BuiltType._date({ coerce: true })',
            'int' => 'BuiltType._num({ coerce: true })',
            'integer' => 'BuiltType._num({ coerce: true })',
            'decimal' => 'BuiltType._num({ coerce: true })',
            'float' => 'BuiltType._num({ coerce: true })',
            'boolean' => 'BuiltType._bool({ coerce: true })',
            'bool' => 'BuiltType._bool({ coerce: true })',
            'array' => 'BuiltType._array()',
            'json' => 'BuiltType._map({ coerce: true })',
            'object' => 'BuiltType._map({ coerce: true })',
        ];

        /** Property mapping for camel case property declaration */
        $mappings = [];
        $defaultProperties = [];

        foreach ($this->type->getProperties() as $value) {
            $name = $value->name();
            $tmpName = $name;
            $selected = $builtTypes[strtolower($value->getRawType())] ?? 'BuiltType._str({ coerce: true })';
            if ($this->camelize && (($name = Str::camelize($name, false)) !== $tmpName)) {
                $mappings[$name] = $tmpName;
            }
            $defaultProperties[] = $name;
            $lines[] = sprintf("\t\t%s: %s%s", $name, $selected, $value->required() ? ',' : '.nullish(),');
        }

        if ($this->type instanceof HasRelations) {
            $imports = [];
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
                $tmpName = $name;
                $typeName = sprintf('%s', $value->to());
                $import = sprintf('import { %s } from \'../%s\';', $typeName, str_replace('_', '-', $value->getModuleName() ?? ''));
                if (!in_array($import, $imports)) {
                    $imports[] = $import;
                }
                $type = $value->multi() ? sprintf('BuiltType._array(%s).nullish(),', $typeName) : sprintf('%s.nullish(),', $typeName);
                if ($this->camelize && (($name = Str::camelize($name, false)) !== $tmpName)) {
                    $mappings[$name] = $tmpName;
                }
                if (in_array($name, $defaultProperties)) {
                    $lines[] = '//#TODO: Fix property name to avoid duplicate keys on the object';
                }
                $lines[] = sprintf("\t\t%s%s: %s", in_array($name, $defaultProperties) ? '//' : '', isset($names[$name]) ? sprintf("%s_%d", $name, intval($names[$name])) : $name, $type);
                $names[$name] = 0;
            }
            array_unshift($lines, ...$imports);
        }

        $lines[] = 0 === \count($mappings) ? '});' : "\t},";

        if (0 !== \count($mappings)) {
            $lines[] = "\t{";
            foreach ($mappings as $key => $value) {
                $lines[] = sprintf("\t\t%s: \"%s\",", $key, $value);
            }
            $lines[] = "\t}";
            $lines[] = ');';
        }

        $lines = array_merge($lines, [
            '',
            sprintf('/** @description %s type declaration */', $builtType),
            sprintf('export type %sType = TypeOf<typeof %s>;', $builtType, $builtType),
        ]);

        return implode("\n", $lines);
    }
}
