<?php

namespace Drewlabs\GCli\Contracts;

interface HasUniqueConstraint
{
    /**
     * boolean flag that check if the property must be unique or not
     * 
     * @return bool 
     */
    public function hasUniqueConstraint(): bool;

}