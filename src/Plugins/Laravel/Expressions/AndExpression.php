<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\GCli\Plugins\Laravel\Expressions;

final class AndExpression
{
    /** @var LiteralExpression[]|OrExpression[] */
    private $expressions;

    public function __construct(array $expressions)
    {
        $this->expressions = $expressions;
    }

    public function __toString(): string
    {
        $values = array_map(static function ($p) {
            return sprintf('(%s)', $p);
        }, $this->expressions);

        return implode(' && ', $values);
    }

    public static function compile(string $expr)
    {
        return new static(array_map(static function (string $e) {
            return str_contains($e, ' OR ') ? OrExpression::compile($e) : LiteralExpression::compile($e);
        }, explode(' AND ', $expr)));
    }
}
