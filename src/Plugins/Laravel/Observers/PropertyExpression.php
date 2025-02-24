<?php

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

use BadMethodCallException;
use InvalidArgumentException;

final class PropertyExpression
{
    /** @var string */
    private $property;

    /** @var string */
    private $value;

    /** @var \Stringable */
    private $condition;

    /**
     * Null property observer expression
     * 
     * @param string $property 
     * @param string $value 
     * @return void 
     */
    public function __construct(string $property, string $value, $condition = null)
    {
        $this->property = $property;
        $this->value = $value;
        $this->condition = $condition;
    }

    public static function create(string $haystack)
    {
        if (!empty($params = Expression::new($haystack)->read('set', $offset))) {
            $next = trim(mb_substr($haystack, $offset + 1));
            if (empty($next)) {
                return new self(...$params);
            }

            if (strtolower($next) === '->null()') {
                $property = $params[0];
                $params[] = new PropertyLogicalExpression($property, 'null');
                return new self(...$params);
            }

            if (!empty($p = Expression::new($next)->read('->onChange'))) {
                $params[] = new PropertyChangedLogicalExpression(...$p);
                return new self(...$params);
            }


            if (!empty($p = Expression::new($next)->read('->if'))) {
                $params[] = new PropertyLogicalExpression(...$p);
                return new self(...$params);
            }
        }

        throw new BadMethodCallException('set expression not correctly formed, supported syntax is set(property, value)->null()');
    }


    public static function new(string $format, $condition = null)
    {
        if ($pos = strpos($format, ',')) {
            return new static(trim(substr($format, 0, $pos)), trim(substr($format, $pos + 1)), $condition);
        }

        throw new InvalidArgumentException('Expect the format to contains , seperator between property name and it default value');
    }

    public function __toString(): string
    {
        $lcvalue = strtolower($this->value);
        if (($lcvalue === 'now' || $lcvalue === 'now()' || $lcvalue === 'datetime' || $lcvalue === 'datetime()')) {
            return $this->createExpression($this->property, "date('Y-m-d H:i:s')");
        }

        if (($lcvalue === 'date' || $lcvalue === 'date()')) {
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
                    $precision = $pos_2 ? intval(empty($result = trim(substr($params, $pos_2 + 1))) ? 2 : $result) : 2;
                    return $this->createExpression($this->property, sprintf("%." . $precision . "f", $p));
                case 'str':
                case 'string':
                    return $this->createExpression($this->property, sprintf("'%s'", $params));
                case 'upper':
                    return $this->createExpression($this->property, sprintf("\strtoupper('%s')", $params));
                case 'lower':
                    return $this->createExpression($this->property, sprintf("\strtolower('%s')", $params));
                case 'date':
                    return $this->createExpression($this->property, sprintf("date('%s')", $params));
                default:
                    $this->createExpression($this->property, $this->value);
            }
        }

        return $this->createExpression($this->property, $this->value);
    }


    /**
     * Create expression that set property value if provided value is null
     * 
     * @param string $property 
     * @param mixed $value 
     * @return string 
     */
    private function createExpression(string $property, $value): string
    {
        $condition = is_null($this->condition) ? sprintf("is_null(\$model->getRawPropertyValue('%s'))", $property) : (string)$this->condition;
        return sprintf("if (%s) {\n    \$model->setRawPropertyValue('%s', %s); \n}\n", $condition, $property, $value);
    }
}
