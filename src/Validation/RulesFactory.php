<?php

namespace Drewlabs\GCli\Validation;

use Drewlabs\GCli\Contracts\ORMModelDefinition as AbstractTable;

interface RulesFactory
{
    /**
     * creates the list of rules for table columns
     * 
     * **Note** `$updates` flag when true, rules are generated
     *           for /PUT or /PATCH request
     * 
     * @param AbstractTable $table 
     * @param bool $updates 
     * @return array 
     */
    public function createRules(AbstractTable $table, bool $updates = false): array;
}