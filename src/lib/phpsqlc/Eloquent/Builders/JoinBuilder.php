<?php

namespace Drewlabs\PHPSQLC\Eloquent\Builders;

use Drewlabs\PHPSQLC\Utils\Expression;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  join
 *  leftJoin
 *  rightJoin
 *  crossJoin
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class JoinBuilder extends AbstractBuilder implements Builder
{
    /** @var RawBuilder */
    private $builder;

    /** @var CriterionBuilder */
    private $whereBuilder;

    /**
     * criterion statement builder class constructor
     * 
     * @param RawBuilder $builder 
     * @return void 
     */
    public function __construct(RawBuilder $builder)
    {
        $this->builder = $builder;
        $this->whereBuilder = new CriterionBuilder($builder);
    }

    public function build(array $parts, array &$skipBag = []): string
    {
        $qb = '';

        foreach ($parts as $join) {

            if ($this->getValue($join['type']) !== 'join') { // left,right,cross etc
                $fn = $this->fnMerger(array(strtolower($join['type']), 'join'));
            } else {
                $fn = $this->fnMerger(array('join'));
            }

            $qb .= "->" . $fn . "(" . $this->builder->build($join['table'], $join['table_is_raw']);
            if (isset($join['on_clause']) && count($join['on_clause']) > 0) // in cross join no on_clause!
            {
                // everything except columns are raw !
                if (
                    count($join['on_clause']) == 1
                    && $join['on_clause'][0]['type'] !== 'between'
                    && $join['on_clause'][0]['raw_field'] === false
                    && $join['on_clause'][0]['raw_value'] === false
                ) {

                    $onClause = $join['on_clause'][0];
                    $qb .= ", " . Expression::quote($onClause['field'])
                        . ", " . Expression::quote(implode(' ', $onClause['operators']))
                        . ", " . Expression::quote($onClause['value']);
                } else {

                    $qb .= ', function($join) {';
                    $qb .= '$join';

                    foreach ($join['on_clause'] as $onClause) {

                        if ($onClause['type'] == 'between' || $onClause['raw_field'] || $onClause['raw_value']) {
                            if (isset($onClause['const_value']))
                                $onClause['raw_value'] = !$onClause['const_value'];
                            $q = $this->whereBuilder->build(array($onClause));
                            $qb .= $q;
                        } else {
                            // no raw found and not between
                            $operators = implode(' ', $onClause['operators']);
                            $fnParts = $onClause['sep'] == 'and' ? ['on'] : ['or', 'on'];

                            $qb .= '->';
                            $qb .= $this->fnMerger($fnParts);
                            $qb .= '(';

                            $qb .= Expression::quote($onClause['field']). ", " . Expression::quote($operators) . ", " . Expression::quote($onClause['value']); // , !$onClause['const_value'] && $onClause['raw_value']

                            $qb .= ')';
                        }
                    }
                    $qb .= '; }';
                }
            }
            $qb .= ")";
        }

        return $qb;
    }
}
