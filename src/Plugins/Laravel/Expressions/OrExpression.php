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

final class OrExpression
{
    /** @var array */
    private $expressions;

    public function __construct(array $expressions)
    {
        $this->expressions = $expressions;
    }

    public function __toString()
    {
        $values = array_map(static function ($p) {
            return (string) $p;
        }, $this->expressions);

        return implode(' || ', $values);
    }

    public static function compile(string $expr)
    {
        return new static(explode(' OR ', $expr));
    }
}
