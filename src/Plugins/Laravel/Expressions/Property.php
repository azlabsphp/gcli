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
        if (null === $this->cached) {
            $type = $this->type ?? 'mixed';
            if (((false !== ($offset_1 = strpos($this->value, '['))) && (false !== ($offset_2 = strpos($this->value, ']')))) || ((false !== ($offset_1 = strpos($this->value, '{'))) && (false !== ($offset_2 = strpos($this->value, '}'))))) {
                $param = trim(substr($this->value, $offset_1 + 1, $offset_2 - \strlen(substr($this->value, 0, $offset_1 + 1))));
                if (str_contains($param, '\\') && empty(static::getPropertyWithoutType($param))) {
                    $this->cached = $this->getExpression($type, $param);
                } else if (str_ends_with($param, ':original')) {
                    $this->cached = $this->getOriginalExpression($type, $param);
                } else if ($param === 'pk') {
                    $this->cached = $this->getPrimaryKeyExpression($type, $param);
                } else {
                    $this->cached = $this->getExpression($type, sprintf("\$model->getRawPropertyValue('%s')", $param));
                }
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
        if (str_contains($expr, '\\') && !empty(static::getPropertyWithoutType($expr))) {
            return new static($expr);
        }

        if (static::isplaceholder($expr, $start, $end)) {
            $name = trim(substr($expr, $start, $end - \strlen(substr($expr, 0, $start + 1)) + 2));
            $type = empty($type = trim(str_replace($name . ':', '', $expr))) || ($name === $type) ? 'mixed' : $type;
            return new static($name, $type);
        }

        $pos = strpos($expr, ':');
        $type = false !== $pos ? trim(substr($expr, $pos + 1)) : 'mixed';
        $name = false !== $pos ? trim(substr($expr, 0, $pos)) : $expr;

        return new static($name, $type);
    }

    /**
     * return property name without type declaration
     * 
     * @param string $expr 
     * @return string 
     */
    private static function getPropertyWithoutType(string $expr)
    {
        if (str_contains($expr, ':')) {
            return trim(str_replace('\\', '', substr($expr, 0, strrpos($expr, ':'))));
        }
        return trim(str_replace('\\', '', $expr));
    }

    /**
     * checks if expression is a placeholder
     * 
     * @param string $expr 
     * @param null|int &$start 
     * @param null|int &$end 
     * @return bool 
     */
    private static function isplaceholder(string $expr, ?int &$start = null, ?int &$end = null)
    {
        return ((false !== ($start = strpos($expr, '['))) && (false !== ($end = strpos($expr, ']')))) || ((false !== ($start = strpos($expr, '{'))) && (false !== ($end = strpos($expr, '}'))));
    }

    /**
     * returns expression based on `type` and `value` parameters
     * 
     * @param string $type 
     * @param string $value 
     * @return string 
     */
    private function getExpression(string $type, string $value)
    {
        switch (strtolower($type)) {
            case 'float':
            case 'decimal':
                return '(float)' . $value;
            case 'int':
                return '(int)' . $value;
            case 'bool':
            case 'boolean':
                return '(bool)' . $value;
            case 'str':
            case 'string':
                return '(string)' . $value;
            case 'upper':
            case 'str::upper':
            case 'string::upper':
                return "\strtoupper((string)" . $value . ')';
            case 'lower':
            case 'str::lower':
            case 'string::lower':
                return "\strtolower((string)" . $value . ')';
            case 'date':
                return 'new \DateTimeImmutable(' . $value . ')';
            default:
                return $value;
        }
    }

    /**
     * get expression based on `type` and `value` parameters
     * 
     * @param string $type 
     * @param string $value 
     * @return string 
     */
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
                return sprintf('(int)%s', $value);
            case 'bool':
            case 'boolean':
                return sprintf('(bool)%s', $value);
            case 'str':
            case 'string':
                return sprintf("'%s'", $value);
            case ':upper':
            case 'str::upper':
            case 'string::upper':
                return sprintf("\strtoupper('%s')", $value);
            case 'lower':
            case 'str::lower':
            case 'string::lower':
                return sprintf("\strtolower('%s')", $value);
            case 'date':
                return sprintf("new \DateTimeImmutable(%s)", $value);
            default:
                return $value;
        }
    }

    /**
     * returns original attribute value of getter
     * 
     * @param string $type 
     * @param string $expr 
     * @return string 
     */
    private function getOriginalExpression(string $type, string $expr)
    {
        $format = "\$model->getOriginal('%s')";
        $expr =  trim(str_replace(':original', '', $expr));
        return $this->getExpression($type, sprintf($format, $expr));
    }

    /**
     * returns primary key attribute value of getter
     * 
     * @param string $type 
     * @param string $expr 
     * @return string 
     */
    private function getPrimaryKeyExpression(string $type, string $expr)
    {
        return $this->getExpression($type, "\$model->getKey()");
    }
}
