<?php

namespace Drewlabs\PHPSQLC\Eloquent\Builders;

use Drewlabs\PHPSQLC\Utils\Expression;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  insert
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class InsertBuilder extends AbstractBuilder implements Builder
{

    public function build(array $parts, array &$skipBag = []): string
    {
        $qb = '';
        $recordLen = count($parts['records']);
        $columnLen = count($parts['columns']);

        if ($recordLen == 0) {
            return $qb;
        }

        $isBatch = $recordLen > 1;

        $innerArrays = '';
        foreach ($parts['records'] as $recordKey => $record) {

            if (count($record) != $columnLen) {
                return '';
            }

            if ($isBatch && $recordKey > 0) {
                $innerArrays .= ", ";
            }

            $singleArray = $isBatch ? '[' : '';
            foreach ($record as $k => $colVal) {
                if ($k > 0) {
                    $singleArray .= ', ';
                }
                $singleArray .= Expression::quote(($parts['columns'][$k])) . ' => ' . ($this->wrapValue($colVal));

            }
            $innerArrays .= $singleArray . ($isBatch ? ']' : '');
        }

        if (!empty($innerArrays)) {
            $outerArray = '[' . $innerArrays . ']';
            $qb = '->insert(' . $outerArray . ')';
        }
        return $qb;
    }

}
