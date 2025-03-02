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

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

use Drewlabs\GCli\Plugins\Laravel\Expressions\ComposedExpression;
use Drewlabs\GCli\Plugins\Laravel\Expressions\LiteralExpression;
use Drewlabs\GCli\Plugins\Laravel\Expressions\PropertyChangedExpression;

final class OutputExpression
{
    /** @var string */
    private $value;

    /** @var \Stringable */
    private $changedExpression;

    /** @var \Stringable */
    private $condition;

    /**
     * query expression class constructor
     * 
     * @param string $value 
     * @param \Stringable $changedExpression 
     * @param \Stringable $condition 
     * @return void 
     */
    public function __construct(string $value, $changedExpression = null, $condition = null)
    {
        $this->value = $value;
        $this->changedExpression = $changedExpression;
        $this->condition = $condition;
    }

    public static function create(string $expr)
    {
        /** @var string|null */
        $property = null;

        /** @var string|\Stringable|\Closure|null */
        $condition = null;

        /** @var string|null */
        $changed = null;

        /** @var string|null */
        $value = null;

        /** @var int */
        $pos = strlen($expr) - 1;

        if (str_contains($expr, 'CHANGED')) {
            $pos = strpos($expr, 'CHANGED');
            $changed = new PropertyChangedExpression($property = trim(substr($expr, $pos + strlen('CHANGED'))));
            $expr = substr($expr, 0, $pos);
        } else if (str_contains($expr, 'ON CHANGE')) {
            $pos = strpos($expr, 'ON CHANGE');
            $changed = new PropertyChangedExpression($property = trim(substr($expr, $pos + strlen('ON CHANGE'))));
            $expr = substr($expr, 0, $pos);
        }
        
        if (str_contains($expr, 'IF NOT NULL')) {
            $pos = strpos($expr, 'IF NOT NULL');
            $string = trim(substr($expr, $pos + strlen('IF NOT NULL')));
            $property = !empty(trim($string)) ? $string : $property;
            $condition = $property ? new LiteralExpression($property, 'null', '!==') : null;
            $expr = substr($expr, 0, $pos);
        } else if (str_contains($expr, 'IF NULL')) {
            $pos = strpos($expr, 'IF NULL');
            $string = trim(substr($expr, $pos + strlen('IF NULL')));
            $property = !empty(trim($string)) ? $string : $property;
            $condition = $property ? new LiteralExpression($property, 'null', '!==') : null;
            $expr = substr($expr, 0, $pos);
        } else if (str_contains($expr, 'IF')) {
            $pos = strpos($expr, 'IF');
            $condition = ComposedExpression::compile(trim(substr($expr, $pos + strlen('IF'))));
            $expr = substr($expr, 0, $pos);
        }

        if (str_contains($expr, 'PRINT')) {
            $pos = strpos($expr, 'PRINT');
            $value = trim(substr($expr, $pos + strlen('PRINT')));
            $expr = substr($expr, 0, $pos);
        }

        if (empty($value)) {
            throw new \BadMethodCallException('PRINT expression not correctly formed, supported syntax is PRINT <expression> IF property == value CHANGED [name]');
        }

        return new static($value, $changed, is_callable($condition) ? ($property ? call_user_func($condition, $property) : null) : $condition);
    }

    public function __toString(): string
    {
        $value = !str_ends_with(trim($this->value), ';') ? sprintf('%s;', $this->value) : $this->value;
        if ($this->condition) {
            return sprintf("if (%s%s) {\n    %s \n}", $this->changedExpression ?? '', $this->condition ? sprintf('%s%s', $this->changedExpression ? ' && ' : '', $this->condition) : '', $value);
        }
        return sprintf('%s', $value);
    }
}
