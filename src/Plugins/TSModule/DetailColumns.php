<?php

namespace Drewlabs\GCli\Plugins\TSModule;

use Drewlabs\CodeGenerator\Helpers\Str;
use Drewlabs\GCli\Contracts\Type;

class DetailColumns
{
    /** @var string */
    private $module;

    /** @var Type */
    private $type;

    /** @var bool */
    private $camelize;

    /**
     * Class constructor
     * 
     * @param string $module
     * @param Type $type 
     * @param bool $camelize 
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
            '/** returns the list of detail view columns to display */',
            'export const viewColumns: DetailColumnType[] = ['
        ];

        foreach ($this->type->getProperties() as $property) {
            $propertyName = $property->name();
            $label = $this->camelize ? Str::camelize($propertyName, false) : $propertyName;
            $lines = array_merge($lines, [
                "\t{",
                "\t\ttitleTransform: ['translate', 'uppercase'],",
                sprintf("\t\ttitle: 'app.modules.%s.datagrid.columns.%s',", $this->module, $label),
                sprintf("\t\tfield: '%s',", $label),
                "\t\t// TODO: Uncomment codes below to enable data transformation and search query",
                # TODO: Add date transform implementation
                in_array(strtolower($property->getRawType()), ['date', 'datetime']) ? "\t\ttransform: 'date'" : "\t\t//transform: 'uppercase',",
                "\t},"
            ]);
        }

        // Append the closing ] character to the end of the line
        $lines[] = '];';

        return implode("\n", $lines);
    }
}
