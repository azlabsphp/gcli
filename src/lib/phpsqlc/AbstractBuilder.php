<?php


namespace Drewlabs\PHPSQLC;

/**
 * This class defines the base class for expression builder classes
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
abstract class AbstractBuilder
{
    /** @var string */
    public $qb;

    /** @var string */
    public $lastly;

    protected function isSingleTable(array $parsed)
    {
        if (isset($parsed['FROM']) && count($parsed['FROM']) == 1) {
                return $parsed['FROM'][0]['expr_type'] == 'table';
        }

        return false;
    }

    /**
     * reset query statements
     * 
     * @return void 
     */
    public function resetQuery()
    {
        $this->qb = '';
        $this->lastly = '';
    }

    /**
     * return the builded query
     * 
     * @param bool $collect
     * 
     * @return string 
     */
    abstract public function getQuery(bool $collect = false): string;


    /**
     * Build and output expression from prepared SQL query
     * 
     * @param array $prepared 
     * @param array $unions
     * 
     * @return AbstractBuilder 
     */
    abstract public function build(array $prepared, array $unions = [], bool $collect = false): AbstractBuilder;
}
