<?php

namespace Drewlabs\GCli\Plugins;

interface PluginComponent
{
    /**
     * returns the string representation of the component that can we written to disk
     * 
     * @return string 
     */
    public function __toString(): string;
}