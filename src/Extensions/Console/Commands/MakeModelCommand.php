<?php

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;

class MakeModelCommand extends Command
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

    protected $signature = 'drewlabs:mvc:make:model '
        . '{--increments : Makes the model primary key incrementable}'
        . '{--asViewModel : Generate the model as a view model class}'
        . '{--comment= Comment to be added to the model }'
        . '{--table= : Table name to attached to the model }'
        . '{--namespace= : Component namespace }'
        . '{--primaryKey= : Model primary key }'
        . '{--path= : Project source code path }'
        . '{--columns=* : List of model table fillable columns}'
        . '{--hidden=* List of hidden properties}'
        . '{--appends=* List of properties to append to the model }';

    protected $description = 'Creates a model using Drewlabs package model definitions';

    public function handle()
    {
        if (null === ($table = $this->option('table') ?? null)) {
            return $this->error('Model generator requires at least the table name');
        }
        // Parameters initialization
        $primaryKey = $this->option('primaryKey') ?? 'id';
        $increments = $this->option('increments') ?? false;
        $namespace = $this->option('namespace') ?? "\\App\\Models";
        $columns = $this->option('columns') ?? [];
        $vm = $this->option('asViewModel') ?? false;
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        // # End of parameters initialization
        ComponentsScriptWriter($basePath)->write(
            ComponentBuilderHelpers::buildModelDefinition(
                $table,
                $columns ?? [],
                $namespace,
                $primaryKey,
                $increments,
                $vm,
                $this->option('hidden'),
                $this->option('appends') ?? [],
                $this->option('comment') ?? null
            )
        );
        $this->info("Model successfully generated for table : $table\n");
    }
}
