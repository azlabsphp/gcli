<?php

namespace Drewlabs\PHPSQLC\Eloquent\Builders;

/**
 * This class constructs and produces following Query Builder methods :
 *
 * table
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class FromBuilder extends AbstractBuilder implements Builder
{
    /** @var RawBuilder */
    private $builder;

    /**
     * from statement builder class constructor
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
        return 'table(' . $this->builder->build($parts['table'], $parts['is_raw']) . ')';
    }
}
