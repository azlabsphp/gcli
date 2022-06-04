<?php

namespace Drewlabs\ComponentGenerators\Builders;

interface DtoAttributesFactory
{
    /**
     * Creates the list of attributes required by the dto
     * object
     * 
     * @return array 
     */
    public function createDtoAttributes();
}