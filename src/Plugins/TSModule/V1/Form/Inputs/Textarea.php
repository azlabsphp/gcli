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

namespace Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs;

use Drewlabs\CodeGenerator\Helpers\Str as StrHelper;
use Drewlabs\GCli\Contracts\HasSizeProperty;
use Drewlabs\GCli\Contracts\HasUniqueConstraint;
use Drewlabs\GCli\Contracts\Property;

class Textarea
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
        $isRequired = $this->property->required();

        $lines = [
            '{',
            sprintf("\tlabel: '%s',", sprintf('app.modules.%s.form.inputs.%s', $this->module, $name)),
            sprintf("\tname: '%s',", $propertyName),
            "\ttype: 'textarea',",
            "\tclasses: '',",
            "\tplaceholder: '...',",
            "\tvalue: null,",
            "\tdescription: '', // TODO: Add input description",
            sprintf("\tindex: %s,", $this->index ?: 'undefined'),
            "\tisRepeatable: false,",
            "\tcontainerClass: 'input-col-sm-12',",
            "\tconstraints: {",
            sprintf("\t\trequired: %s,", $isRequired ? 'true' : 'false'),
            "\t\tdisabled: false,",
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
                "\t\t//unique: { fn: () => true }",
            ]);
        }

        $lines = array_merge($lines, [
            "\t}",
            '} as TextAreaInput',
        ]);

        return implode("\n", array_map(function ($line) {
            return $this->indent ? sprintf('%s%s', $this->indent, $line) : $line;
        }, $lines));
    }
}
