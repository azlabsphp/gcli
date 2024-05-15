<?php

namespace Drewlabs\GCli\Plugins;

use Drewlabs\GCli\Contracts\Type;

interface Plugin
{

    /**
     * compile source code for the provided type and write all components
     * source code to disk.
     * 
     * @param string module
     * @param Type $type
     * 
     * @return void 
     */
    public function generate(Type $type, string $module = null): void;
}
