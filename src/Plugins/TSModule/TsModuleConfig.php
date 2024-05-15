<?php

namespace Drewlabs\GCli\Plugins\TSModule;

use Drewlabs\CodeGenerator\Helpers\Str;
use Drewlabs\GCli\Contracts\Type;

class TsModuleConfig
{

    /** @var string */
    private $module;

    /** @var Type */
    private $type;

    /** @var string */
    private $importPath;


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
        string $importPath = '../components/data'
    ) {
        $this->module = $module;
        $this->type = $type;
        $this->importPath = $importPath;
    }


    public function __toString(): string
    {
        /** @var string */
        $builtType = Str::camelize($this->type->name());
        $lines = [
            '// import { environment } from \'src/environments/environment\';',
            sprintf('import { %s } from \'./types\';', $builtType),
            'import { form } from \'./form\';',
            sprintf('import { DataConfigArgType } from \'%s\';', $this->importPath),
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
            "\t} as DataConfigArgType;",
            "};"
        ];

        return implode("\n", $lines);
    }
}
