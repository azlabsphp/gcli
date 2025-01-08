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

namespace Drewlabs\GCli\Plugins\Typescript\V1;

use Drewlabs\GCli\Contracts\Type;
use Drewlabs\GCli\IO\Disk;
use Drewlabs\GCli\Plugins\Plugin as AbstractPlugin;
use Drewlabs\GCli\Plugins\Typescript\V1\Form\Config;

class Plugin implements AbstractPlugin
{
    /** @var string */
    private $basePath;


    /** @var bool */
    private $camelize = false;

    /**
     * Creates TSModule source code generator plugin.
     *
     * @return void
     */
    public function __construct(string $basePath, bool $camelize = false)
    {
        $this->basePath = $basePath;
        $this->camelize = $camelize;
    }

    /**
     * Add support for camel case modifier
     * 
     * @return static 
     */
    public function withCamelCase()
    {
        $self = clone $this;
        $self->camelize = true;
        return $self;
    }


    public function getWritePath(string $name, string $module = null)
    {
        $directory  = $module ? str_replace('_', '-', $module) : $module;
        return $directory ? ($directory . \DIRECTORY_SEPARATOR . $name) : $name;
    }

    public function generate(Type $type, string $module = null): void
    {
        $builder = new Types($type, $this->camelize);
        $columns = new Columns($type, $module, $this->camelize);
        $config = new Config($type, $module);
        $form = new Config($type, $module, false);
        Disk::new($this->basePath)->write($this->getWritePath('types.ts', $module), $builder->__toString());
        Disk::new($this->basePath)->write($this->getWritePath('columns.ts', $module), $columns->__toString());
        Disk::new($this->basePath)->write($this->getWritePath('form.ts', $module), $form->__toString());
        Disk::new($this->basePath)->write($this->getWritePath('index.ts', $module), $config->__toString());
    }
}
