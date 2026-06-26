<?php

namespace Drewlabs\PHPSQLC\Extractors;

use Drewlabs\PHPSQLC\Utils\Expression;
use Drewlabs\PHPSQLC\Utils\SelectQueryTypes;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  table
 *  distinct
 *  select
 *  sum
 *  avg
 *  min
 *  max
 *  count
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class SelectExtractor extends AbstractExtractor implements Extractor
{
    /** @var array */
    private $aggregators;

    /**
     * select extractor class constructor
     * 
     * @param array $aggregators
     * 
     * @return void 
     */
    public function __construct(array $aggregators)
    {
        $this->aggregators = $aggregators;
    }

    public function extract(array $value, array $parsed = []): array
    {
        $distinct = $this->isDistinct($value);
        if ($distinct) {
            array_shift($value);
        }

        if ($this->isSingleTable($parsed) &&  $this->isCountTable($value) && $this->validCountTable($parsed)) {
            return [
                's_type' => SelectQueryTypes::COUNT_A_TABLE,
                'parts' => ['table' => $parsed['FROM'][0]['base_expr'], 'distinct' => $distinct, 'selected' => 'COUNT(*)'],
            ];
        } else if ($this->isAggregate($value)) {
            return [
                's_type' => SelectQueryTypes::AGGREGATE,
                'parts' => $this->extractAggregateParts($value, $distinct)
            ];
        }

        $this->getExpressionParts($value, $parts, $raws);
        
        return ['s_type' => SelectQueryTypes::OTHER, 'parts' => ['selected' => $parts, 'distinct' => $distinct, 'raws' => $raws]];
    }

    /**
     * @param mixed $value 
     * @return bool 
     */
    private function isAggregate($value)
    {
        return count($value) == 1 && $value[0]['expr_type'] == 'aggregate_function' && in_array(Expression::lowercase($value[0]['base_expr']), $this->aggregators);
    }

    /**
     * @param mixed $value 
     * @return bool 
     */
    private function isDistinct($value)
    {
        return count($value) > 0 && $value[0]['expr_type'] == 'reserved' && Expression::lowercase($value[0]['base_expr']) == 'distinct';
    }

    /**
     * @param mixed $value 
     * @return bool 
     */
    private function isCountTable($value)
    {
        /** @var ?string */
        $d = null;
        return count($value) == 1 && $value[0]['expr_type'] == 'aggregate_function' && Expression::lowercase($value[0]['base_expr']) == 'count' && $this->getFnParams($value[0], $d) === "*";
    }

    /**
     * @param mixed $value 
     * @param mixed $distinct 
     * @return array{suffix: string, column: string, alias: mixed, distinct: mixed} 
     */
    private function extractAggregateParts($value, $distinct)
    {
        $fn_suffix = Expression::lowercase($value[0]['base_expr']);
        $this->getExpressionParts($value[0]['sub_tree'], $parts);
        $column = implode('', $parts);

        $alias = $this->hasAlias($value[0]);
        if ($alias) {
            $alias = $value[0]['alias']['name'];
        }

        return ['suffix' => $fn_suffix, 'column' => $column, 'alias' => $alias, 'distinct' => $distinct];
    }
}
