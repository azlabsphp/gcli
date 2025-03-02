<?php

namespace Drewlabs\PHPSQLC\Eloquent\Builders;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  offset
 *  limit
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class LimitBuilder extends AbstractBuilder implements Builder
{
    public function build(array $parts, array &$skipBag = []): string
    {
        $query = '';

        if (isset($parts['offset']))
            $query .= "->offset(" . $parts['offset'] . ')';
        if (isset($parts['rowcount']))
            $query .= "->limit(" . $parts['rowcount'] . ')';

        return $query;
    }

}
