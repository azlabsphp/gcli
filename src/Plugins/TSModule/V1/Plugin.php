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

use Drewlabs\GCli\Contracts\Type;
use Drewlabs\GCli\IO\Disk;
use Drewlabs\GCli\Plugins\Plugin as AbstractPlugin;
use Drewlabs\GCli\Plugins\TSModule\V1\Form\Config;

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

    public function generate(Type $type, string $module = null): void
    {
        $builder = new Types($type, $this->camelize);
        $columns = new Columns($module, $type, $this->camelize);
        $config = new TsModuleConfig($module, $type);
        $form = new Config('posts', $type);
        Disk::new($this->basePath)->write($module ? ($module . \DIRECTORY_SEPARATOR . 'types.ts') : 'types.ts', $builder->__toString());
        Disk::new($this->basePath)->write($module ? ($module . \DIRECTORY_SEPARATOR . 'columns.ts') : 'columns.ts', $columns->__toString());
        Disk::new($this->basePath)->write($module ? ($module . \DIRECTORY_SEPARATOR . 'form.ts') : 'form.ts', $form->__toString());
        Disk::new($this->basePath)->write($module ? ($module . \DIRECTORY_SEPARATOR . 'index.ts') : 'index.ts', $config->__toString());
    }
}
