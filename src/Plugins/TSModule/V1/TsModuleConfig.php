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

class TsModuleConfig
{
    /** @var string|null */
    private $name;

    /** @var Type */
    private $type;

    /**
     * Class constructor.
     */
    public function __construct(
        Type $type,
        ?string $name = null
    ) {
        $this->type = $type;
        $this->name = $name;
    }

    public function __toString(): string
    {
        /** @var string */
        $builtType = Str::camelize($this->type->name());
        $lines = [
            sprintf('import { %s } from \'./types\';', $builtType),
            'import { /*form,*/ createFormConfig } from \'./form\';',
            'import { gridColumns, viewColumns as detailColumns } from \'./columns\';',
            '',
            'export default (url: string, query?: { [k: string]: any }) => {',
            "\treturn {",
            sprintf("\t\t_type: %s,", $builtType),
            "\t\turl,",
            "\t\tform: {",
            "\t\t\t//# TODO: Add custom handlers",
            "\t\t\tvalue: createFormConfig(url), // form  // replace `createFormConfig` with `form` to use constant form declaration",
            "\t\t},",
            "\t\tdatagrid: {",
            "\t\t\ttransformColumnTitle: ['text', 'uppercase'],",
            "\t\t\tcolumns: gridColumns,",
            "\t\t\tquery,",
            "\t\t\tdetail: detailColumns,",
            "\t\t},",
            "\t\t//excludesActions: [/*'create',*/ 'update', 'delete'] as ActionType[],",
            "\t};",
            '};',
        ];

        return implode("\n", $lines);
    }
}
