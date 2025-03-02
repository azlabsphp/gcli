<?php

namespace Drewlabs\PHPSQLC\Eloquent\Builders;

/**
 * This class constructs and produces following Query Builder methods :
 *
 * delete
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class DeleteBuilder extends AbstractBuilder implements Builder
{
    public function build(array $parts, array &$skipBag = []): string
    {
        return '->delete()';
    }
}
