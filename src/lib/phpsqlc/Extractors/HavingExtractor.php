<?php

namespace Drewlabs\PHPSQLC\Extractors;

use Drewlabs\PHPSQLC\Utils\CriterionContext;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  having
 *  orHaving
 *  havingRaw
 *  orHavingRaw
 *  havingBetween
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class HavingExtractor extends AbstractExtractor implements Extractor
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
        $this->extrator->getCriteria($value, $parts, CriterionContext::HAVING);

        return $parts;
    }
}
