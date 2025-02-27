<?php

namespace Drewlabs\GCli\Plugins\Laravel\Expressions;

final class OrExpression
{
    /** @var array */
    private $expressions;

    public function __construct(array $expressions)
    {
        $this->expressions = $expressions;
    }

    public static function compile(string $expr)
    {
        return new static(explode(' OR ', $expr));
    }

    public function __toString()
    {
        $values = array_map(function ($p) {
            return (string)$p;
        }, $this->expressions);

        return implode(' || ', $values);
    }
}