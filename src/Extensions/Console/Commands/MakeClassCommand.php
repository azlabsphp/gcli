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

namespace Drewlabs\GCli\Extensions\Console\Commands;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use Drewlabs\GCli\Helpers\ComponentBuilderHelpers;
use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;
use function Drewlabs\GCli\Proxy\PHPScript;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

class MakeClassCommand extends Command
{
    protected $signature = 'drewlabs:mvc:make:class '
        .'{--name= : Class name }'
        .'{--namespace= : View model namespace }'
        .'{--path= : Project source code path }'
        .'{--final : Creates a final class }';

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
    {
        // Parameters initialization
        $name = $this->option('name') ?? null;
        if (null === $name) {
            return $this->error('Error while building class: name option is required!');
        }
        $namespace = $this->option('namespace') ?? '\\App';
        $basePath = $this->option('path') ?? 'app';
        // # End of parameters initialization
        $component = PHPClass($name)
            ->addConstructor()
            ->addToNamespace($namespace);
        if ($this->option('final')) {
            $component = $component->asFinal();
        }
        ComponentsScriptWriter('')->write(
            PHPScript(
                $component->getName(),
                $component,
                ComponentBuilderHelpers::rebuildComponentPath(
                    $namespace ?? '\\App',
                    $basePath
                )
            )->setNamespace($component->getNamespace())
        );
        $this->info("Class successfully generated\n");
    }
}
