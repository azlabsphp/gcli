<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Drewlabs\ComponentGenerators\Builders\EloquentORMModelBuilder;
use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use Illuminate\Console\Command;
use Illuminate\Container\Container;

use Illuminate\Contracts\Foundation\Application;

class MakeDTOClassCommand extends Command
{
    protected $signature = 'drewlabs:mvc:make:dto '
        .'{name=TestDto : Generated view model name }'
        .'{--namespace= : View model namespace }'
        .'{--path= : Project source code path }'
        .'{--model= : Model attached to the view model class }'
        .'{--attributes=* : List of Jsonable attributes }'
        .'{--hidden=* : List of hidden attributes }';

    protected $description = 'Creates a Drewlabs package MVC controller';
    /**
     * @var Application
     */
    private $app;

    public function __construct()
    {
        $this->app = ($this->getLaravel() ?? Container::getInstance());
        parent::__construct();
    }

    public function handle()
    {        // Parameters initialization
        $name = $this->argument('name') ?? null;
        $model = $this->option('model') ? EloquentORMModelBuilder::defaultClassPath($this->option('model')) : null;
        $namespace = $this->option('namespace') ?? '\\App\\Dto';
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        $attributes = $this->option('attributes') ?? [];
        $hidden = $this->option('hidden') ?? [];
        // # End of parameters initialization
        ComponentsScriptWriter($basePath)->write(
            ComponentBuilderHelpers::buildDtoObjectDefinition(
                iterator_to_array((static function () use ($attributes) {
                    foreach ($attributes as $value) {
                        yield $value => 'mixed';
                    }
                })()),
                $hidden,
                $name,
                $namespace,
                $model
            )
        );
        $this->info("Data Transfert class successfully generated\n");
    }
}
