<?php

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Drewlabs\ComponentGenerators\Builders\EloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;

class MakeDTOClassCommand extends Command
{
    /**
     * 
     * @var Application
     */
    private $app;

    public function __construct()
    {
        $this->app = ($this->getLaravel() ?? Container::getInstance());
        parent::__construct();
    }

    protected $signature = 'drewlabs:mvc:make:dto'
        . '{--namespace= : View model namespace }'
        . '{--path= : Project source code path }'
        . '{--name= : Generated view model name }'
        . '{--model= : Model attached to the view model class }'
        . '{--attributes=* List of Jsonable attributes }'
        . '{--guarded=* : List of guarded attributes }'
        . '{--hidden=* List of hidden attributes }';

    protected $description = 'Creates a Drewlabs package MVC controller';

    public function handle()
    {        // Parameters initialization
        $name = $this->option('name') ?? null;
        $model = EloquentORMModelBuilder::defaultClassPath($this->option('model'));
        $namespace = $this->option('namespace') ?? null;
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        $attributes = $this->option('attributes') ?? [];
        $hidden = $this->option('hidden') ?? []; //
        $guarded = $this->option('guarded') ?? [];
        // # End of parameters initialization
        ComponentsScriptWriter($basePath)->write(
            ComponentBuilderHelpers::buildDtoObjectDefinition(
                $attributes,
                $hidden,
                $guarded,
                $name,
                $namespace,
                $model
            )
        );
        $this->info("Data Transfert class successfully generated\n");
    }
}
