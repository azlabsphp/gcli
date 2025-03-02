<?php

namespace Drewlabs\PHPSQLC\Eloquent\Builders;

use Drewlabs\PHPSQLC\Utils\Expression;
use Drewlabs\PHPSQLC\Utils\SelectQueryTypes;

/**
 * This class constructs and produces following Query Builder methods :
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
class SelectBuilder extends AbstractBuilder implements Builder
{
    /** @var bool */
    private $closesQuery = false;

    /** @var string */
    public $queryType  = 'eq';

    /** @var RawBuilder */
    private $builder;

    /**
     * criterion statement builder class constructor
     * 
     * @param RawBuilder $builder 
     * @return void 
     */
    public function __construct(RawBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function build(array $parts, array &$skipBag = []): string
    {
        $type = $parts['s_type'];
        $parts = $parts['parts'];

        $qb = '';
        if ($parts['distinct']) {
            $qb = $this->distinctClause();
        }

        switch ($type) {
            case SelectQueryTypes::AGGREGATE:
                $qb .= $this->aggregateQuery($parts['suffix'], $parts['column'], $parts['alias']);
                $this->queryType = $this->closesQuery ? 'lastly' : 'eq';
                break;
            case SelectQueryTypes::COUNT_A_TABLE:
                $skipBag[] = 'FROM';
                $qb .= $this->countAllClause($parts['table']);
                $this->queryType = 'eq';
                $this->closesQuery = true;
                break;
            case SelectQueryTypes::OTHER:
                $qb .= $this->selectOnlyClause($parts['selected'], $parts['raws']);
                $this->queryType = 'eq';
                $this->closesQuery = false;
            default:
                break;
        }

        return $qb;
    }

    /**
     * returns boolean which value equals true if the statement mark end of the query
     * 
     * @return bool 
     */
    public function getClosesQuery(): bool
    {
        return $this->closesQuery;
    }

    /**
     * returns query type property value
     * 
     * @return string 
     */
    public function getQueryType(): string
    {
        return $this->queryType;
    }

    private function aggregateQuery(string $suffix, string $column, ?bool $alias = null)
    {
        $this->closesQuery = false;
        if ($alias !== false) {
            $fn = strtoupper($suffix) . '(' . $column . ')';
            $qb = "->selectRaw(" . $this->builder->build($fn . " AS " . $alias) . ")";
        } else {
            $this->closesQuery = true; // max(something) is the end of query / or count
            if ($column != '*') {
                $qb = '->' . $this->getValue($suffix) . '(' . Expression::quote($column) . ')';
            } else {
                $qb = '->' . $this->getValue($suffix) . '()';
            }
        }

        return $qb;
    }

    private function selectOnlyClause($parts, $raws)
    {
        $columnLen = count($parts);

        if ($columnLen == 1 && $parts[0] == '*') {
            return '';
        }

        $query = '->';
        $ciPart = 'select'; // to be done selectRaw
        $query .= $ciPart . "(";
        foreach ($parts as $k => $column) {
            $query .= $this->builder->build($column, $raws[$k]);
            if ($k + 1 != $columnLen) {
                $query .= ', ';
            }
        }
        $query .= ")";
        return $query;
    }

    private function countAllClause($table)
    {
        return 'table(' . Expression::quote($table) . ')->count()';
    }

    private function distinctClause()
    {
        return '->distinct()';
    }
}
