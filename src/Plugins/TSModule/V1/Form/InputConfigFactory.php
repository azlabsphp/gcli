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

use Drewlabs\GCli\Contracts\HasExistConstraint;
use Drewlabs\GCli\Contracts\Property;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Date;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Number;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Option;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Str;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Inputs\Textarea;

class InputConfigFactory
{
    /** @var string|null */
    private $module;

    /** @var bool */
    private $camelize;

    /**
     * Class constructor.
     */
    public function __construct(string $module = null, bool $camelize = false)
    {
        $this->module = $module;
        $this->camelize = $camelize;
    }

    public function createInputConfig(Property $property, string $indent = "\t", int $index = null)
    {
        if ($property instanceof HasExistConstraint && $property->hasExistContraint()) {
            return new Option($property, $this->module, $this->camelize, $indent, $index, 'select');
        }

        $factories = [
            'date' => static function (Property $property, string $module = null, bool $camelize = false) use ($indent, $index) {
                return new Date($property, $module, $camelize, $indent, $index);
            },
            'datetime' => static function (Property $property, string $module = null, bool $camelize = false) use ($indent, $index) {
                return new Date($property, $module, $camelize, $indent, $index);
            },
            'float' => static function (Property $property, string $module = null, bool $camelize = false) use ($indent, $index) {
                return new Number($property, $module, $camelize, $indent, $index);
            },
            'int' => static function (Property $property, string $module = null, bool $camelize = false) use ($indent, $index) {
                return new Number($property, $module, $camelize, $indent, $index);
            },
            'integer' => static function (Property $property, string $module = null, bool $camelize = false) use ($indent, $index) {
                return new Number($property, $module, $camelize, $indent, $index);
            },
            'decimal' => static function (Property $property, string $module = null, bool $camelize = false) use ($indent, $index) {
                return new Number($property, $module, $camelize, $indent, $index);
            },
            'string' => static function (Property $property, string $module = null, bool $camelize = false) use ($indent, $index) {
                return new Str($property, $module, $camelize, $indent, $index);
            },
            'text' => static function (Property $property, string $module = null, bool $camelize = false) use ($indent, $index) {
                return new Textarea($property, $module, $camelize, $indent, $index);
            },
        ];

        $fn = $factories[strtolower($property->getRawType())] ?? static function () {
            return null;
        };

        return $fn($property, $this->module, $this->camelize);
    }
}
