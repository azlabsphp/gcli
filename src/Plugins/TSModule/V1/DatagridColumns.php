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

class DatagridColumns
{
    /** @var string */
    private $module;

    /** @var Type */
    private $type;

    /** @var bool */
    private $camelize;

    /**
     * Class constructor.
     */
    public function __construct(
        string $module,
        Type $type,
        bool $camelize = false
    ) {
        $this->module = $module;
        $this->type = $type;
        $this->camelize = $camelize;
    }

    public function __toString(): string
    {
        $lines = [
            '/** returns the list of datagrid columns to display */',
            'export const gridColumns: SearchableGridColumnType[] = [',
        ];

        foreach ($this->type->getProperties() as $property) {
            $propertyName = $property->name();
            $label = $this->camelize ? Str::camelize($propertyName, false) : $propertyName;
            $lines = array_merge($lines, [
                "\t{",
                sprintf("\t\ttitle: 'app.modules.%s.datagrid.columns.%s',", $this->module, $label),
                sprintf("\t\tlabel: '%s',", $label),
                sprintf("\t\tfield: '%s',", $propertyName),
                "\t\tsortable: false,",
                \in_array(strtolower($property->getRawType()), ['date', 'datetime'], true) ? "\t\ttransform: 'date'" : "\t\t//transform: 'uppercase',",
                "\t\t// TODO: Uncomment codes below to enable search query",
                "\t\t//searcheable: true,",
                "\t\t//search: {",
                "\t\t\t//flexible: true,",
                "\t\t//}",
                "\t},",
            ]);
        }

        // Append the closing ] character to the end of the line
        $lines[] = '];';

        return implode("\n", $lines);
    }
}
