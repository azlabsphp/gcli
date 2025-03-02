<?php

namespace Drewlabs\PHPSQLC\Extractors;

use Drewlabs\PHPSQLC\Utils\CriterionContext;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  join
 *  leftJoin
 *  rightJoin
 *  crossJoin
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class JoinExtractor extends AbstractExtractor implements Extractor
{

    /** @var CriterionExtractor */
    private $extrator;

    /**
     * having extractor class constructor
     * 
     * @param CriterionExtractor $extrator
     * 
     * @return void 
     */
    public function __construct(CriterionExtractor $extrator)
    {
        $this->extrator = $extrator;
    }

    public function extract(array $value, array $parsed = []): array
    {
        $joins = [];
        foreach ($value as $k => $val) {
            if ($k == 0) { // skip from table
                continue;
            }

            if (!$this->validJoin($val['join_type'])) { // skip joins such as natural
                continue;
            }

            $isRawTable = false;
            $joinTable = $this->getWithAlias($val, $isRawTable);

            $join = [
                'table' => $joinTable,
                'table_is_raw' => $isRawTable,
                'type' => $val['join_type'],
            ];

            if ($val['ref_clause'] !== false) {
                $join['on_clause'] = $this->getOnCriterion($val['ref_clause']);
            }

            $joins[] = $join;
        }

        return $joins;
    }

    private function getOnCriterion($val)
    {
        $parts = [];
        $this->extrator->getCriteria($val, $parts, CriterionContext::JOIN);
        return $parts;
    }
}
