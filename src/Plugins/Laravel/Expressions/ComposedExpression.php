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

final class ComposedExpression
{
    /** @var \Stringable */
    private $expression;

    /**
     * composed expression class constructor.
     *
     * @param \Stringable $expression
     *
     * @return void
     */
    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function __toString(): string
    {
        return (string) $this->expression;
    }

    /**
     * compiles composed expression.
     *
     * @throws \LogicException
     *
     * @return mixed
     */
    public static function compile(string $expr)
    {
        $len = \strlen($expr);
        $offset = 0;
        $characters = [];
        $output = null;
        $op = null;

        $read_spaces = static function () use ($expr, &$offset) {
            while (true) {
                if (ctype_space(substr($expr, $offset, 1))) {
                    ++$offset;
                    continue;
                }
                break;
            }
        };

        if (!str_contains($expr, '(')) {
            return strpos($expr, ' AND ') ? AndExpression::compile($expr) : (strpos($expr, ' OR ') ? OrExpression::compile($expr) : LiteralExpression::compile($expr));
        }

        // Chop off all white spaces
        $read_spaces();
        $offset = strpos($expr, '(');
        $before = substr($expr, 0, $offset);
        $ends_with = false;
        $operator_pos = null;
        if (str_ends_with(trim($before), 'AND')) {
            $ends_with = true;
            $operator_pos = strrpos($before, 'AND');
            $op = 'AND';
        }

        if (str_ends_with(trim($before), 'OR')) {
            $ends_with = true;
            $operator_pos = strrpos($before, 'OR');
            $op = 'OR';
        }

        if (!empty(trim($before)) && !$ends_with) {
            throw new \LogicException('expression error, expressions should be combined with AND or OR logical operators');
        }

        if (null !== $operator_pos) {
            $before = substr($before, 0, $operator_pos);
        }

        if (!empty(trim($before))) {
            $output = strpos($before, ' AND ') ? AndExpression::compile($before) : (strpos($before, ' OR ') ? OrExpression::compile($before) : LiteralExpression::compile($before));
        }

        // Chop off ( character
        ++$offset;

        while ($offset < $len) {
            if (')' === ($char = substr($expr, $offset, 1))) {
                ++$offset;
                if (!empty($characters)) {
                    $expression = implode('', $characters);
                    $compiled = str_contains($expression, ' AND ') ? AndExpression::compile($expression) : (str_contains($expression, ' OR ') ? OrExpression::compile($expression) : LiteralExpression::compile($expression));

                    if (null !== $output && null === $op) {
                        throw new \LogicException('expression error, expressions should be combined with AND or OR logical operators');
                    }

                    $output = null !== $output ? ('AND' === $op ? new AndExpression([$output, $compiled]) : new OrExpression([$output, $compiled])) : $compiled;
                    $characters = [];
                    $op = null;
                }

                // Chop off all white spaces
                $read_spaces();

                $substr = substr($expr, $offset);
                if (str_starts_with($substr, 'AND')) {
                    $offset += \strlen('AND');
                    $characters = [];
                    $op = 'AND';
                }

                if (str_starts_with($substr, 'OR')) {
                    $offset += \strlen('OR');
                    $characters = [];
                    $op = 'OR';
                }

                // Chop off all white spaces
                $read_spaces();

                if ('(' === substr($expr, $offset, 1)) {
                    ++$offset;
                }

                // Chop off all white spaces
                $read_spaces();
                continue;
            }

            if ('(' === $char && !(0 === \count($characters))) {
                throw new \LogicException('bad expression, ( character can only be located after another (');
            }

            if ('(' === $char && (0 === \count($characters))) {
                ++$offset;
                continue;
            }

            $characters[] = $char;
            ++$offset;
        }

        return $output;
    }
}
