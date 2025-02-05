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
use Drewlabs\GCli\Contracts\Type;

class DetailColumns
{
    /** @var string|null */
    private $module;

    /** @var Type */
    private $type;

    /** @var bool */
    private $camelize;

    /**
     * Class constructor.
     */
    public function __construct(
        Type $type,
        bool $camelize = false,
        ?string $module = null
    ) {
        $this->type = $type;
        $this->camelize = $camelize;
        $this->module = $module;
    }

    public function __toString(): string
    {
        $lines = [
            '/** returns the list of detail view columns to display */',
            'export const viewColumns: GridDetailColumnType[] = [',
        ];

        foreach ($this->type->getProperties() as $property) {
            $propertyName = $property->name();
            $label = $this->camelize ? Str::camelize($propertyName, false) : $propertyName;
            $lines = array_merge($lines, [
                "\t{",
                "\t\ttitleTransform: ['text', 'uppercase'],",
                $this->module ? sprintf("\t\ttitle: 'app.modules.%s.columns.%s',", $this->module, $label) : sprintf("\t\ttitle: '%s',", $label),
                sprintf("\t\tproperty: '%s',", $label),
                "\t\t// TODO: Uncomment codes below to enable data transformation and search query",
                \in_array(strtolower($property->getRawType()), ['date', 'datetime'], true) ? "\t\ttransform: 'date'" : "\t\t//transform: 'uppercase',",
                "\t},",
            ]);
        }

        // Append the closing ] character to the end of the line
        $lines[] = '];';

        return implode("\n", $lines);
    }
}
