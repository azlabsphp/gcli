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

final class LiteralExpression
{
    /** @var string */
    private $variable;

    /** @var string */
    private $value;

    /** @var string */
    private $operator = '===';

    /**
     * literal expression class constructor
     * 
     * @param null|string $variable 
     * @param null|string $value 
     * @param null|string $operator 
     * @return void 
     */
    public function __construct(?string $variable = null, ?string $value = null, ?string $operator = null)
    {
        $this->variable = $variable;
        $this->value = $value;
        $this->operator = $operator ?? '===';
    }

    public function __toString(): string
    {
        if (null === $this->variable) {
            return null === $this->value ? 'true' : (string) Property::create($this->value);
        }

        if (null === $this->value) {
            return sprintf('!is_null(%s)', Property::create($this->variable));
        }

        if ('null' === strtolower($this->value)) {
            return sprintf('%sis_null(%s)', '!=' === $this->operator || '!==' === $this->operator ? '!' : '', Property::create($this->variable));
        }

        $op = '=' === $this->operator || '==' === $this->operator ? '===' : $this->operator;
        $op = '!=' === $op ? '!==' : $op;

        return sprintf('%s %s %s', Property::create($this->variable), $op, Property::create($this->value));
    }

    /**
     * compiles string literal expression.
     *
     * @return static
     */
    public static function compile(string $expr)
    {
        $regex = '/^((((?:\\\{1,2}\w+|\w+\\\{1,2})(?:\w+\\\{0,2}))+((:{2}\w+)?)|(\w+|\$\w+|\$\w+\b->\b\w+|(\[\w+(:\w+)?\](:\w+)?)))[\s]([><!]?[=]+|[>]+|[<]+)[\s](((?:\\\{1,2}\w+|\w+\\\{1,2})(?:\w+\\\{0,2}))+((:{2}\w+)?)|(\w+|\$\w+|\$\w+\b->\b\w+|(\[\w+(:\w+)?\](:\w+)?))))+$/';
        if (1 === preg_match($regex, $expr)) {
            $swap_array_values = static function (&$array, $i, $j) {
                $tmp = $array[$i];
                $array[$i] = $array[$j];
                $array[$j] = $tmp;
            };
            $items = preg_split('/[\s]([><!]?[=]+|[\b>\b]+|[<]+)[\s]/', $expr, -1, \PREG_SPLIT_NO_EMPTY | \PREG_SPLIT_DELIM_CAPTURE);
            $items = array_map(static function ($item) {
                return trim($item);
            }, array_filter($items, static function ($item) {
                return false !== $item;
            }));
            if (\count($items) >= 3) {
                $swap_array_values($items, 1, 2);
            }

            return new static(...$items);
        }

        return new static(null, $expr, null);
    }
}
