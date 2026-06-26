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
use Drewlabs\GCli\Plugins\Laravel\Expressions\PropertyName;

final class PropertyExpression
{
    /** @var string */
    private $property;

    /** @var string */
    private $value;

    /** @var \Stringable|null */
    private $condition;

    /** @var \Stringable|null */
    private $changedExpression;

    /**
     * Null property observer expression.
     *
     * @return void
     */
    public function __construct(string $property, string $value, $changedExpression = null, $condition = null)
    {
        $this->property = $property;
        $this->value = $value;
        $this->condition = $condition;
        $this->changedExpression = $changedExpression;
    }

    public function __toString(): string
    {
        $lcvalue = strtolower($this->value);
        if ('now' === $lcvalue || 'now()' === $lcvalue || 'datetime' === $lcvalue || 'datetime()' === $lcvalue) {
            return $this->createExpression($this->property, "date('Y-m-d H:i:s')");
        }

        if ('date' === $lcvalue || 'date()' === $lcvalue) {
            return $this->createExpression($this->property, "date('Y-m-d H:i:s')");
        }

        return $this->createExpression($this->property, $this->value);
    }

    public static function create(string $haystack)
    {
        /** @var string|null */
        $property = null;

        /** @var string|\Stringable|\Closure|null */
        $condition = null;

        /** @var string|null */
        $changed = null;

        /** @var string|null */
        $setExpression = null;
        
        $pos = strlen($haystack) - 1;

        if (str_contains($haystack, 'CHANGED')) {
            $pos = strpos($haystack, 'CHANGED');
            $changedProperty = trim(substr($haystack, $pos + strlen('CHANGED')));
            $changed = empty($changedProperty) ? function ($p) {
                return new PropertyChangedExpression($p);
            } : new PropertyChangedExpression($changedProperty);
            $haystack = substr($haystack, 0, $pos);
        } else if (str_contains($haystack, 'ON CHANGE')) {
            $pos = strpos($haystack, 'ON CHANGE');
            $changedProperty = trim(substr($haystack, $pos + strlen('ON CHANGE')));
            $changed = empty($changedProperty) ? function ($p) {
                return new PropertyChangedExpression($p);
            } : new PropertyChangedExpression($changedProperty);
            $haystack = substr($haystack, 0, $pos);
        }

        if (str_contains($haystack, 'IF NOT NULL')) {
            $pos = strpos($haystack, 'IF NOT NULL');
            $string = trim(substr($haystack, $pos + strlen('IF NOT NULL')));
            $property = !empty(trim($string)) ? $string : $property;
            $condition = function ($p) {
                return new LiteralExpression($p, 'null', '!==');
            };
            $haystack = substr($haystack, 0, $pos);
        } else if (str_contains($haystack, 'IF NULL')) {
            $pos = strpos($haystack, 'IF NULL');
            $string = trim(substr($haystack, $pos + strlen('IF NULL')));
            $property = !empty(trim($string)) ? $string : $property;
            $condition = function ($p) {
                return new LiteralExpression($p, 'null');
            };
            $haystack = substr($haystack, 0, $pos);
        } else if (str_contains($haystack, 'IF')) {
            $pos = strpos($haystack, 'IF');
            $condition = ComposedExpression::compile(trim(substr($haystack, $pos + strlen('IF'))));
            $haystack = substr($haystack, 0, $pos);
        }

        if (str_contains($haystack, 'SET')) {
            $pos = strpos($haystack, 'SET');
            $setExpression = trim(substr($haystack, $pos + strlen('SET')));
            $haystack = substr($haystack, 0, $pos);
        }

        if (empty($params = preg_split("/\s+/", $setExpression ?? ''))) {
            throw new \BadMethodCallException('SET expression not correctly formed, supported syntax is `SET property value IF NULL` or `SET property value IF property == value`');
        }

        if (is_null($property)) {
            $property = $params[0];
        }

        return new static($params[0], $params[1] ?? 'null', is_callable($changed) ? ($property ? call_user_func($changed, $property) : null) : $changed, is_callable($condition) ? ($property ? call_user_func($condition, $property) : null) : $condition);
    }

    public static function new(string $format, $condition = null)
    {
        if ($pos = strpos($format, ',')) {
            return new static(trim(substr($format, 0, $pos)), trim(substr($format, $pos + 1)), $condition);
        }
        throw new \InvalidArgumentException('Expect the format to contains , seperator between property name and it default value');
    }

    /**
     * Create expression that set property value if provided value is null.
     *
     * @param mixed $value
     */
    private function createExpression(string $property, $value): string
    {
        $condition = null === $this->condition ? sprintf('is_null(%s)', Property::create($property)) : $this->condition;

        return sprintf("if (%s%s) {\n    \$model->setRawPropertyValue('%s', %s); \n}\n", $this->changedExpression ?? '', sprintf('%s%s', $this->changedExpression ? ' && ' : '', $condition), new PropertyName($property), Property::create($value));
    }
}
