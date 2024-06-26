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

    /**
     * Class constructor.
     */
    public function __construct(
        string $module,
        Type $type,
        bool $camelize = false,
        string $gridImportPath = '@azlabsjs/ngx-clr-smart-grid'
    ) {
        $this->module = $module;
        $this->type = $type;
        $this->camelize = $camelize;
        $this->gridImportPath = $gridImportPath;
    }

    public function __toString()
    {
        $lines = [
            sprintf('import { GridDetailColumnType, SearchableGridColumnType } from \'%s\';', $this->gridImportPath ?? '@azlabsjs/ngx-clr-smart-grid'),
            '',
        ];

        $lines[] = (string) (new DatagridColumns($this->module, $this->type, $this->camelize));
        $lines[] = (string) (new DetailColumns($this->module, $this->type, $this->camelize));

        return implode("\n", $lines);
    }
}
