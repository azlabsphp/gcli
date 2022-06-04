<?php

namespace Drewlabs\ComponentGenerators\Builders;

interface ViewModelRulesFactory
{
    /**
     * Create view model rules array based.
     * If $update parameter is provided, the returns rules
     * will not require most attributes
     * 
     * @param bool $update 
     * @return array 
     */
    public function createRules(bool $update = false);
}