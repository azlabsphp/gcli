<?php

namespace Drewlabs\PHPSQLC\Extractors;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  update
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class UpdateExtractor extends AbstractExtractor implements Extractor
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

        $records = []; // collect data so you gave only records and know about if is it batch or not

        foreach ($parsed['SET'] as $item) {
            if ($item['expr_type'] == 'expression') {
                $curr_index = 0;
                foreach ($item['sub_tree'] as $index => $inner) {
                    if ($index < $curr_index) {
                        continue; // skip those collected in inner loop
                    }

                    if (in_array($inner['expr_type'], array('operator', 'reserved'))) {
                        $left = $this->extrator->getLeft($index, $item['sub_tree']);
                        $right = $this->extrator->getRight($index, $item['sub_tree'], $curr_index);
                        $records[] = array('field' => $left['value'], 'value' => $right['value'], 'raw_val' => $right['is_raw']);
                    }
                }
            }
        }

        return ['records' => $records, 'is_batch' => false];
    }
}
