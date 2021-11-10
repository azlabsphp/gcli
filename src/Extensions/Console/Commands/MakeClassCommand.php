<?php

namespace Drewlabs\ComponentGenerators\Extensions\Console\Commands;

use Drewlabs\ComponentGenerators\Helpers\ComponentBuilderHelpers;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\ComponentGenerators\Proxy\ComponentsScriptWriter;
use function Drewlabs\ComponentGenerators\Proxy\PHPScript;

class MakeClassCommand extends Command
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

    protected $signature = 'drewlabs:mvc:make:class'
        . '{--namespace= : View model namespace }'
        . '{--path= : Project source code path }'
        . '{--name= : Generated view model name }'
        . '{--final : Creates a final class }';

    protected $description = 'Creates a Drewlabs package MVC controller';

    public function handle()
    {        
        // Parameters initialization
        $name = $this->option('name') ?? null;
        if (null === $name) {
            return $this->error('Error while building class: name option is required!');
        }
        $namespace = $this->option('namespace') ?? null;
        $basePath = $this->app->basePath($this->option('path') ?? 'app');
        // # End of parameters initialization
        $component = PHPClass($name)->addConstructor();
        if ($this->option('final')) {
            $component = $component->asFinal();
        }
        ComponentsScriptWriter($basePath)->write(
            PHPScript(
                $component->getName(),
                $component,
                ComponentBuilderHelpers::rebuildComponentPath(
                    $namespace ?? "App",
                    $basePath
                )
            )->setNamespace($component->getNamespace())
        );
        $this->info("Class successfully generated\n");
    }
}
