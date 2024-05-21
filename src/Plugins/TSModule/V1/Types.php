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

namespace Drewlabs\GCli\Plugins\TSModule\V1;

use Drewlabs\CodeGenerator\Helpers\Str;
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
            'string' => 'BuiltType._str()',
            'text' => 'BuiltType._str()',
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

        foreach ($this->type->getProperties() as $value) {
            $name = $value->name();
            $selected = $builtTypes[strtolower($value->getRawType())] ?? 'BuiltType._str()';
            if ($this->camelize) {
                $tmpName = $name;
                $name = Str::camelize($value->name(), false);
                $mappings[$name] = $tmpName;
                $lines[] = sprintf("\t\t%s: %s%s", $name, $selected, $value->required() ? ',' : '.nullish(),');
            } else {
                $lines[] = sprintf("\t%s: %s%s", $name, $selected, $value->required() ? ',' : '.nullish(),');

            }
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
