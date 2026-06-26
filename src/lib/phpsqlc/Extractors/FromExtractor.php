<?php

namespace Drewlabs\PHPSQLC\Extractors;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 * table
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class FromExtractor extends AbstractExtractor implements Extractor
{
    /** @var \Drewlabs\PHPSQLC\Options */
    // @phpstan-ignore property.onlyWritten
    private $options;

    public function __construct(?\Drewlabs\PHPSQLC\Options $options = null)
    {
        $this->options = $options;
    }

    public function extract(array $value, array $parsed = []): array
    {
        $parts = [];

        foreach ($value as $val) {
            if (!isset($parts['table'])) {
                $parts = $this->extractSingle($value);
            } else {
                if (!$this->validJoin($val['join_type'])) {
                    $join = [
                        'type' => $val['join_type'],
                        'table_expr' => $val['base_expr'],
                    ];
                    $parts['joins'][] = $join;
                }
            }
        }

        return $parts;
    }

    /**
     * 
     * @param array $value
     * 
     * @return array{table: mixed, is_raw: bool} 
     */
    public function extractSingle($value)
    {
        $isRaw = $value[0]['expr_type'] != 'table';
        $table = $this->getWithAlias($value[0], $isRaw);

        return ['table' => $table, 'is_raw' => $isRaw];
    }
}
