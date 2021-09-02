<?php

namespace Drewlabs\ComponentGenerators\Contracts;

interface UniqueKeyConstraintDefinition
{

    /**
     * Returns the column on which the rule must be defined
     * 
     * @return string 
     */
    public function getTable();

    /**
     * Returns the column or the list of columns making the constraint
     * 
     * @return string|string[] 
     */
    public function getColumns();
}