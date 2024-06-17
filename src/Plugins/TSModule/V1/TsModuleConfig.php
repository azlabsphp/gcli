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
    /** @var string */
    private $module;

    /** @var Type */
    private $type;

    /**
     * Class constructor.
     */
    public function __construct(
        string $module,
        Type $type,
    ) {
        $this->module = $module;
        $this->type = $type;
    }

    public function __toString(): string
    {
        /** @var string */
        $builtType = Str::camelize($this->type->name());
        $lines = [
            '// import { environment } from \'src/environments/environment\';',
            sprintf('import { %s } from \'./types\';', $builtType),
            'import { form } from \'./form\';',
            // TODO: Add support for data config type import path to use the as `DataConfigArgType`
            // sprintf('import { DataConfigArgType } from \'%s\';', $this->importPath),
            'import { gridColumns, viewColumns } from \'./columns\';',
            'import { Injector } from \'@angular/core\';',
            'import { Observable } from \'rxjs\';',
            '',
            'export default (',
            "\ttranslateColumns: <T extends { title: string }>(columns: T[]) => (injector: Injector) => T[] | Observable<T[]>,",
            "\t_query?: { [k: string]: any }",
            ') => {',
            "\treturn {",
            sprintf("\t\t_type: %s,", $builtType),
            sprintf("\t\t//url: environment.api.endpoints.%s", $this->module),
            "\t\tform: {",
            "\t\t\tvalue: form",
            "\t\t},",
            "\t\tdatagrid: {",
            "\t\t\ttransformColumnTitle: ['uppercase'],",
            "\t\t\tcolumns: translateColumns(gridColumns),",
            "\t\t\tquery: {_query, _columns: ['*'] },",
            "\t\t\t//# TODO: Uncomment the code below to datagrid preview",
            "\t\t\t//detail: translateColumns(viewColumns),",
            "\t\t},",
            "\t\t//excludesActions: [/*'create'*/, 'update', 'delete'] as ActionType[],",
            "\t};",
            '};',
        ];

        return implode("\n", $lines);
    }
}
