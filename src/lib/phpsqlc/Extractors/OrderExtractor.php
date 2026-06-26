<?php

namespace Drewlabs\PHPSQLC\Extractors;
/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  orderBy
 *  orderByRaw
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class OrderExtractor extends AbstractExtractor implements Extractor
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
        $partsTemp = [];
        $this->getExpressionParts($value, $partsTemp);
        $parts = [];
        foreach ($value as $k => $val) {
            $parts[] = ['field' => $partsTemp[$k], 'dir' => $val['direction'], 'type' => 'normal', 'raw' => $this->isRaw($val)];
        }

        return $parts;
    }
}