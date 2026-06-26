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

namespace Drewlabs\GCli\Plugins\Typescript\V1\Form\Inputs;

use Drewlabs\CodeGenerator\Helpers\Str as StrHelper;

class Group
{
    /** @var string|null */
    private $module;

    /** @var string */
    private $name;

    /** @var bool */
    private $required;

    /** @var bool */
    private $repeatable;

    /** @var bool */
    private $camelize;

    /** @var string */
    private $indent;

    /** @var int */
    private $index;

    /** @var string */
    private $valueProperty;

    public function __construct(
        string $name,
        string $valueProperty,
        bool $required = false,
        bool $repeatable = false,
        ?string $module = null,
        bool $camelize = false,
        string $indent = "\t",
        ?int $index = null
    ) {
        $this->module = $module;
        $this->required = $required;
        $this->repeatable = $repeatable;
        $this->name = $name;
        $this->valueProperty = $valueProperty;
        $this->camelize = $camelize;
        $this->indent = $indent;
        $this->index = $index;
    }

    public function __toString(): string
    {
        $propertyName = $this->name;
        $name = $this->camelize ? StrHelper::camelize($propertyName) : $propertyName;
        $isRequired = $this->required;

        $lines = [
            '{',
            $this->module ? sprintf("\tlabel: '%s',", sprintf('app.modules.%s.columns.%s', $this->module, $name)) : sprintf("\tlabel: '%s',", $name),
            sprintf("\tname: '%s',", $propertyName),
            "\ttype: 'control_group',",
            "\tclasses: 'controls-header table',",
            "\tplaceholder: '...',",
            "\tvalue: null,",
            "\tdescription: '', // TODO: Add input description",
            sprintf("\tindex: %s,", $this->index ?: 'undefined'),
            sprintf("\tisRepeatable: %s,", $this->repeatable ? 'true' : 'false'),
            "\tcontainerClass: 'input-col-sm-12',",
            sprintf("\tchildren: %s,", $this->valueProperty),
            "\tconstraints: {",
            sprintf("\t\trequired: %s,", $isRequired ? 'true' : 'false'),
            "\t\tdisabled: false,",
        ];

        $lines = array_merge($lines, [
            "\t}",
            '} as InputGroup',
        ]);

        return implode("\n", array_map(function ($line) {
            return $this->indent ? sprintf('%s%s', $this->indent, $line) : $line;
        }, $lines));
    }
}
