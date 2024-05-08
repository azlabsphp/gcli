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

namespace Drewlabs\GCli\Extensions\Console\Commands;

use Drewlabs\GCli\Helpers\ComponentBuilder;
use Illuminate\Console\Command;
use Illuminate\Container\Container;

use function Drewlabs\CodeGenerator\Proxy\PHPClass;
use function Drewlabs\GCli\Proxy\ComponentsScriptWriter;
use function Drewlabs\GCli\Proxy\PHPScript;
/**
 * @property \Illuminate\Contracts\Foundation\Application app
 */
class MakeClassCommand extends Command
{
    /** @var string */
    protected $signature = 'gcli:make:class '
        . '{--name= : Class name }'
        . '{--namespace= : View model namespace }'
        . '{--path= : Project source code path }'
        . '{--final : Creates a final class }';

    /** @var string */
    protected $description = 'Creates a Drewlabs package MVC controller';

    public function __construct()
    {
        $this->app = $this->getLaravel() ?? Container::getInstance();
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
        $component = PHPClass($name)->addConstructor()->addToNamespace($namespace);
        if ($this->option('final')) {
            $component = $component->asFinal();
        }

        ComponentsScriptWriter('')->write(PHPScript(
            $component->getName(),
            $component,
            ComponentBuilder::rebuildComponentPath(
                $namespace ?? '\\App',
                $basePath
            )
        )->setNamespace($component->getNamespace()));

        $this->info("Class successfully generated\n");
    }
}
