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

final class Property
{
    /** @var string */
    private $value;

    /** @var string */
    private $type;

    /** @var string cached `__toString` value of the current property */
    private $cached;

    /**
     * L-value expression class constructor.
     *
     * @return void
     */
    public function __construct(string $value, ?string $type = null)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function __toString(): string
    {
        if ((null === $this->cached) && str_contains($this->value, '\\')) {
            $this->cached = $this->value;
        }

        if (null === $this->cached) {
            $type = $this->type ?? 'mixed';
            if (((false !== ($offset_1 = strpos($this->value, '['))) && (false !== ($offset_2 = strpos($this->value, ']')))) || ((false !== ($offset_1 = strpos($this->value, '{'))) && (false !== ($offset_2 = strpos($this->value, '}'))))) {
                $param = trim(substr($this->value, $offset_1 + 1, $offset_2 - \strlen(substr($this->value, 0, $offset_1 + 1))));
                $ends_with_original = str_ends_with($param, ':original');
                $format = $ends_with_original ? "\$model->getOriginal('%s')" : "\$model->getRawPropertyValue('%s')";
                $param = $ends_with_original ? trim(str_replace(':original', '', $param)) : $param;
                $this->cached = $this->getExpression($type, sprintf($format, $param));
            } else {
                $this->cached = $this->getValueExpression($type, $this->value);
            }
        }

        return $this->cached;
    }

    /**
     * property class factory function form expression.
     *
     * @return static
     */
    public static function create(string $expr)
    {
        if (str_contains($expr, '\\')) {
            return new static($expr);
        }

        if (static::isPlaceholder($expr, $start, $end)) {
            $name = trim(substr($expr, $start, $end - \strlen(substr($expr, 0, $start + 1)) + 2));
            $type = empty($type = trim(str_replace($name . ':', '', $expr))) || ($name === $type) ? 'mixed' : $type;

            return new static($name, $type);
        }
        $pos = strpos($expr, ':');
        $type = false !== $pos ? trim(substr($expr, $pos + 1)) : 'mixed';
        $name = false !== $pos ? trim(substr($expr, 0, $pos)) : $expr;

        return new static($name, $type);
    }

    private static function isPlaceholder(string $expr, ?int &$start = null, ?int &$end = null)
    {
        return ((false !== ($start = strpos($expr, '['))) && (false !== ($end = strpos($expr, ']')))) || ((false !== ($start = strpos($expr, '{'))) && (false !== ($end = strpos($expr, '}'))));
    }

    private function getExpression(string $type, string $value)
    {
        switch (strtolower($type)) {
            case 'float':
            case 'decimal':
                return 'floatval(' . $value . ')';
            case 'int':
                return 'intval(' . $value . ')';
            case 'str':
            case 'string':
                return 'strval(' . $value . ')';
            case 'str::upper':
            case 'string::upper':
                return "\strtoupper(strval(" . $value . '))';
            case 'str::lower':
            case 'string::lower':
                return "\strtolower(strval(" . $value . '))';
            case 'date':
                return 'new \DateTimeImmutable(' . $value . ')';
            default:
                return $value;
        }
    }

    private function getValueExpression(string $type, string $value)
    {
        $lcvalue = strtolower($value);
        if ('now' === $lcvalue || 'now()' === $lcvalue || 'datetime' === $lcvalue || 'datetime()' === $lcvalue) {
            $value = "date('Y-m-d H:i:s')";
        }

        if ('date' === $lcvalue || 'date()' === $lcvalue) {
            $value = "date('Y-m-d')";
        }
        
        switch (strtolower($type)) {
            case 'float':
            case 'decimal':
                $pos_2 = strpos($value, ':');
                $p = $pos_2 ? trim(substr($value, 0, $pos_2)) : $value;
                $precision = $pos_2 ? (int) (empty($result = trim(substr($value, $pos_2 + 1))) ? 2 : $result) : 2;

                return sprintf('%.' . $precision . 'f', $p);
            case 'int':
                return sprintf('%s', (int) $value);
            case 'str':
            case 'string':
                return sprintf("'%s'", $value);
            case 'str::upper':
            case 'string::upper':
                return sprintf("\strtoupper('%s')", $value);
            case 'str::lower':
            case 'string::lower':
                return sprintf("\strtolower('%s')", $value);
            case 'date':
                return sprintf("new \DateTimeImmutable(%s)", $value);
            default:
                return $value;
        }
    }
}
