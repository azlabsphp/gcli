<?php

namespace Drewlabs\GCli\Plugins\Laravel\Expressions;

final class LiteralExpression
{
    /** @var string */
    private $variable;

    /** @var string */
    private $value;

    /** @var string */
    private $operator = '===';

    public function __construct(?string $variable = null, ?string $value = null, ?string $operator = null)
    {
        $this->variable = $variable;
        $this->value = $value;
        $this->operator = $operator ?? '===';
    }

    /**
     * compiles string literal expression
     * @param string $expr 
     * @return static 
     */
    public static function compile(string $expr)
    {
        $regex = "/^([\[\]A-Za-z\d_ \"'?:]+)([><!]?[=]+|[>]+|[<]+)([\[\]A-Za-z\d_ \"'?:])+$/";
        if (preg_match($regex, $expr) === 1) {
            $swap_array_values = function(&$array, $i, $j) {
                $tmp = $array[$i];
                $array[$i] = $array[$j];
                $array[$j] = $tmp;
            };
            $items = preg_split("/([><!]?[=]+|[>]+|[<]+)/", $expr, -1, \PREG_SPLIT_NO_EMPTY|\PREG_SPLIT_DELIM_CAPTURE);
            $items = array_map(function($item) {
                return trim($item);
            }, array_filter($items, function($item) {
                return $item !== false;
            }));
            if (count($items) >= 3) {
                $swap_array_values($items, 1, 2);
            }
            return new static(...$items);
        }

        return new static(null, $expr, null);
    }

    public function __toString(): string
    {
        
        if (is_null($this->variable)) {
            return is_null($this->value) ? 'true' : Property::create($this->value);
        }

        if (is_null($this->value)) {
            return sprintf("!is_null(%s)", Property::create($this->variable));
        }

        if ('null' === strtolower($this->value)) {
            return sprintf("is_null(%s)", Property::create($this->variable));
        }

        $op = '=' === $this->operator || '==' === $this->operator ? '===' : $this->operator;
        $op = '!=' ===  $op ? '!==' : $op;

        return sprintf('%s %s %s', Property::create($this->variable), $op, Property::create($this->value));
    }
}
