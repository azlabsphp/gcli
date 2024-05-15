<?php

namespace Drewlabs\GCli\Contracts;

interface ProvidesModuleMetadata
{

    /**
     * returns the module name of the current object
     * 
     * @return string 
     */
    public function getModuleName(): string;
}