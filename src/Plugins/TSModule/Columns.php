<?php

namespace Drewlabs\GCli\Plugins\TSModule;

use Drewlabs\GCli\Contracts\Type;

class Columns
{
    /** @var string */
    private $module;

    /** @var Type */
    private $type;

    /** @var bool */
    private $camelize;

    /** @var string */
    private $gridImportPath;

    /** @var string */
    private $detailImportPath;

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
        bool $camelize = false,
        string $gridImportPath = '../components/data',
        string $detailImportPath = '../components/datagrid'
    ) {
        $this->module = $module;
        $this->type = $type;
        $this->camelize = $camelize;
        $this->gridImportPath = $gridImportPath;
        $this->detailImportPath = $detailImportPath;
    }

    public function __toString()
    {
        $lines = [
            sprintf('import { DetailColumnType } from \'%s\';', $this->detailImportPath ?? '../components/data'),
            sprintf('import { SearchableGridColumnType } from \'%s\';', $this->gridImportPath ?? '../components/datagrid'),
            '',
        ];

        // TODO: Generate UI configuration for datagrid columns
        $lines[] = (string)(new DatagridColumns($this->module, $this->type, $this->camelize));

        // TODO: Generate UI configuration for data detail columns
        $lines[] = (string)(new DetailColumns($this->module, $this->type, $this->camelize));

        return implode("\n", $lines);
    }
}
