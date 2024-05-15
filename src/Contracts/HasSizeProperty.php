<?php

namespace Drewlabs\GCli\Contracts;

interface HasSizeProperty
{
    /**
     * boolean flag for property that has a size limit
     * 
     * @return bool 
     */
    public function hasSize(): bool;

    /**
     * returns the size limit of the property
     * 
     * @return int 
     */
    public function getSize(): int;

}