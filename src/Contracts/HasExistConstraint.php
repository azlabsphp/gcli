<?php

namespace Drewlabs\GCli\Contracts;

interface HasExistConstraint
{
    /**
     * boolean flag that check if the property must exists
     * 
     * @return bool 
     */
    public function hasExistContraint(): bool;
}
