<?php

namespace Drewlabs\GCli\Contracts;

interface Type
{
    /**
     * returns the type name
     * 
     * @return string
     */
    public function name(): string;


    // /**
    //  * returns the actual name from the name property of the current type
    //  * 
    //  * @return string 
    //  */
    // public function getActualName(): string;


    /**
     * returns the list of type properties
     * 
     * @return Property[] 
     */
    public function getProperties(): array;
}
