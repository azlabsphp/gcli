<?php

namespace Drewlabs\PHPSQLC;

use PHPSQLParser\PHPSQLParser;

class Compiler
{
    /** @var AbstractBuilder */
    private $builder;

    /**
     * class constructor
     * 
     * @param array $options 
     * @return void 
     */
    public function __construct(AbstractBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Converts a SQL Query into Query Builder
     * 
     * @param $sql
     * @return string
     * 
     * @throws \Exception
     */
    public function compile($sql, bool $collect = true): string
    {
        // reset the builder to, to prevent subsequent call to not concatenate results.
        $this->builder->resetQuery();

        // Track `union all` statements replacing them by `union` statements
        // as `greenlion/php-sql-parser` does not properly handle union all statements
        $sql = str_replace(["\n", "\r"], "", $sql);
        $exploded = explode(' ', $sql);
        $counter = 1;
        $unions = [];
        for ($index = 0; $index < count($exploded); $index++) {
            if (strtolower($exploded[$index]) == 'union') {
                $unions[$counter] = 0;
                if (isset($exploded[$index + 1])) {
                    if (strtolower($exploded[$index + 1]) == 'all') {
                        $unions[$counter] = 1;
                    }
                }
                $counter++;
            }
        }

        $parser = new PHPSQLParser(str_replace('union all', 'union', $sql));

        return $this->builder
            ->build(is_array($parser->parsed) ? $parser->parsed : [], $unions)
            ->getQuery($collect);
    }
}
