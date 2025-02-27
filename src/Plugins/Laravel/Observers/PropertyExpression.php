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

final class PropertyExpression
{
    /** @var string */
    private $property;

    /** @var string */
    private $value;

    /** @var \Stringable */
    private $condition;

    /** @var \Stringable */
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

        if ($pos = strpos($this->value, ':')) {
            $op = trim(substr($this->value, 0, $pos));
            $params = trim(substr($this->value, $pos + 1));
            switch (strtolower($op)) {
                case 'float':
                case 'decimal':
                    $pos_2 = strpos($params, ':');
                    $p = $pos_2 ? trim(substr($params, 0, $pos_2)) : $params;
                    $precision = $pos_2 ? (int) (empty($result = trim(substr($params, $pos_2 + 1))) ? 2 : $result) : 2;
                    return $this->createExpression($this->property, sprintf('%.' . $precision . 'f', $p));
                case 'str':
                case 'string':
                    return $this->createExpression($this->property, sprintf("'%s'", $params));
                case 'str::upper':
                    return $this->createExpression($this->property, sprintf("\strtoupper('%s')", $params));
                case 'str::lower':
                    return $this->createExpression($this->property, sprintf("\strtolower('%s')", $params));
                case 'string::upper':
                    return $this->createExpression($this->property, sprintf("\strtoupper('%s')", $params));
                case 'string::lower':
                    return $this->createExpression($this->property, sprintf("\strtolower('%s')", $params));
                case 'date':
                    return $this->createExpression($this->property, sprintf("date('%s')", $params));
                default:
                    $this->createExpression($this->property, $this->value);
            }
        }

        return $this->createExpression($this->property, $this->value);
    }

    public static function create(string $haystack)
    {
        if (!empty($params = Expression::new($haystack)->read('set', $offset))) {
            $next = trim(substr($haystack, $offset + 1));

            if (empty($next)) {
                return new self(...$params);
            }

            if ((strpos($next, '->onChange') !== false) && !empty($p = Expression::new($next)->read('->onChange', $offset))) {
                $next = ltrim(substr($next, $offset + 1));
                $params[] = new PropertyChangedExpression(...$p);
            } else if ((strpos($next, '->changed') !== false) && !empty($p = Expression::new($next)->read('->changed', $offset))) {
                $next = ltrim(substr($next, $offset + 1));
                $params[] = new PropertyChangedExpression(...$p);
            } else {
                $params[] = null;
            }

            $next = ltrim($next);
            if ('->null()' === strtolower($next)) {
                $property = $params[0];
                $params[] = new LiteralExpression($property, 'null');

            } else if (str_starts_with($next, '->if')) {
                $params[] = ComposedExpression::compile(substr($next, strlen('->if')));
            }

            return new self(...$params);
        }

        throw new \BadMethodCallException('set expression not correctly formed, supported syntax is set(property, value)->null()');
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
        $condition = null === $this->condition ? sprintf("is_null(%s)", Property::create($property)) :  $this->condition;
        return sprintf("if (%s%s) {\n    \$model->setRawPropertyValue('%s', %s); \n}\n", $this->changedExpression ?? '', sprintf("%s%s", $this->changedExpression ? ' && ' : '', $condition), new PropertyName($property), $value);
    }
}
