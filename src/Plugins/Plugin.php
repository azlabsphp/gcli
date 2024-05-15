<?php

namespace Drewlabs\GCli\Plugins;

interface Plugin
{

    /**
     * return the directory in which the components should be written
     * 
     * @return string 
     */
    public function getOutputPath(): string;

    /**
     * returns the list component the plugin exposed to the generator
     * 
     * @return PluginComponent[] 
     */
    public function getComponents(): array;
}