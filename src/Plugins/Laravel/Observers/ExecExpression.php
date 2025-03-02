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
use Drewlabs\GCli\Plugins\Laravel\Expressions\Property;
use Drewlabs\GCli\Plugins\Laravel\Expressions\PropertyChangedExpression;

final class ExecExpression
{
    /** @var string */
    private $callback;

    /** @var string[] */
    private $params = [];

    /** @var \Stringable */
    private $changedExpression;

    /** @var \Stringable */
    private $condition;

    /**
     * query expression class constructor
     * 
     * @param string $callback 
     * @param string[]|array $params 
     * @param \Stringable $changedExpression 
     * @param \Stringable $condition 
     * @return void 
     */
    public function __construct(string $callback, array $params, $changedExpression = null, $condition = null)
    {
        $this->callback = $callback;
        $this->params = $params;
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
        $callback = null;

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

        if (str_contains($expr, 'EXEC')) {
            $pos = strpos($expr, 'EXEC');
            $callback = trim(substr($expr, $pos + strlen('EXEC')));
            $expr = substr($expr, 0, $pos);
        }

        if (empty($params = explode('WITH', $callback ?? ''))) {
            throw new \BadMethodCallException('exec expression not correctly formed, supported syntax is EXEC event_name WITH [id]:string, [name]:string IF property == value CHANGED [name]');
        }

        $callbackParams = array_map(function ($item) {
            return trim($item);
        }, preg_split('/\s+/', $params[1] ?? '', -1, \PREG_SPLIT_NO_EMPTY));

        return new static(trim($params[0]), $callbackParams, $changed, is_callable($condition) ? ($property ? call_user_func($condition, $property) : null) : $condition);
    }

    public function __toString(): string
    {
        if ($this->condition) {
            return sprintf("if (%s%s) {\n    %s \n}", $this->changedExpression ?? '', $this->condition ? sprintf('%s%s', $this->changedExpression ? ' && ' : '', $this->condition) : '', $this->createExpression($this->callback, $this->params));
        }
        return sprintf('%s', $this->createExpression($this->callback, $this->params));
    }

    private function createExpression(string $name, array $params)
    {
        $values = preg_split('/\s+/', $name);

        $fn = count($values) === 1 ? $values[0] : sprintf('[%s]', implode(', ', array_map(function (string $item) {
            if (str_ends_with(trim($item), ':class')) {
                return $item;
            }
            return str_replace("''", "'", sprintf("'%s'", trim($item)));
        }, $values)));

        $param_str = !empty($params) ? implode(', ', array_map(function (string $item) {
            return Property::create(trim($item));
        }, $params)) : '';

        return sprintf('call_user_func(%s%s);', $fn, empty($param_str) ? '' : sprintf(', %s', $param_str));
    }
}
