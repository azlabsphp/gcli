<?php

namespace Drewlabs\GCli\Plugins\Laravel\Expressions;

final class AndExpression
{
    /** @var LiteralExpression[]|OrExpression[] */
    private $expressions;

    public function __construct(array $expressions)
    {
        $this->expressions = $expressions;
    }

    public static function compile(string $expr)
    {
        return new static(array_map(function (string $e) {
            return false !== strpos($e, ' OR ') ? OrExpression::compile($e) : LiteralExpression::compile($e);
        }, explode(' AND ', $expr)));
    }

    public function __toString(): string
    {
        $values = array_map(function ($p) {
            return sprintf("(%s)", $p);
        }, $this->expressions);

        return implode(' && ', $values);
    }
}
