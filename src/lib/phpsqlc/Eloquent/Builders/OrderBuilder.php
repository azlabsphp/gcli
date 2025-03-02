<?php

namespace Drewlabs\PHPSQLC\Eloquent\Builders;

use Drewlabs\PHPSQLC\Utils\Expression;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  orderBy
 *  orderByRaw
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class OrderBuilder extends AbstractBuilder implements Builder
{
    function build(array $parts, array &$skipBag = []): string
    {
        $query = '';
        $isRaw = false;
        foreach ($parts as $part)
            if ($part['raw']) {
                $isRaw = true;
                break;
            }

        if ($isRaw) {
            $inner = '';
            foreach ($parts as $val) {
                if (!empty($inner)) {
                    $inner .= ', ';
                }

                if ($val['type'] == 'fn') {
                    $inner .= ($val['dir']) . ' (' . ($val['field']) . ')';
                } else {
                    $inner .= ($val['field']) . ' ' . ($val['dir']);
                }
            }
            $query .= '->orderByRaw(' . Expression::quote($inner) . ')';
        } else {
            foreach ($parts as $val) {
                if (trim(mb_strtolower(Expression::quote($val['dir']))) == "'asc'") {
                    $query .= "->orderBy(" . Expression::quote($val['field']) . ')';
                } else {
                    $query .= "->orderByDesc(" . Expression::quote($val['field']) . ')';
                }
            }
        }

        return $query;
    }

}
