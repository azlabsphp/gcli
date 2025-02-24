<?php

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

final class LogicalExpression
{

    /** @var string */
    private $lvalue;

    /** @var string */
    private $value;

    /** @var string */
    private $operator = '===';

    /**
     * creates new logical expression instance
     * @param string $lvalue 
     * @param string|null $value 
     * @param string $operator 
     * @return void 
     */
    public function __construct(string $lvalue, ?string $value = null, $operator = '===')
    {
        $this->lvalue = $lvalue;
        $this->value = $value;
        $this->operator = $operator ?? '===';
    }

    public function __toString(): string
    {
        if (is_null($this->value)) {
            return sprintf("!is_null(\$model->getRawPropertyValue('%s'))", $this->lvalue);
        }

        if (strtolower($this->value) === 'null') {
            return sprintf("is_null(\$model->getRawPropertyValue('%s'))", $this->lvalue);
        }

        $op = $this->operator === '=' || $this->operator === '==' ? '===' : $this->operator;
        return sprintf("%s %s %s", $this->createLValueExpression($this->value, sprintf("\$model->getRawPropertyValue('%s')", $this->lvalue)), $op, $this->createRValueExpression($this->value));
    }

    /**
     * creates an expression at the left of the operator
     * 
     * @param string $value 
     * @return string 
     */
    private function createLValueExpression(string $value, string $expression)
    {
        if ($pos = strpos($value, ':')) {
            $op = trim(substr($value, 0, $pos));
            $params = trim(substr($value, $pos + 1));
            switch (strtolower($op)) {
                case 'float':
                case 'decimal':
                    return "floatval(" . $expression . ")";
                case 'int':
                    return "intval(" . $expression . ")";
                case 'str':
                case 'string':
                    return "strval(" . $expression . ")";
                case 'upper':
                    return "\strtoupper(" . $expression . ")";
                case 'lower':
                    return "\strtolower(" . $expression . ")";
                case 'date':
                    return "strtotime(" . $expression . ")";
                default:
                    return $value;
            }
        }

        return $expression;
    }

    /**
     * creates an expression at the right of the operator
     * 
     * @param string $value 
     * @return string 
     */
    private function createRValueExpression(string $value)
    {
        if ($pos = strpos($value, ':')) {
            $op = trim(substr($value, 0, $pos));
            $params = trim(substr($value, $pos + 1));
            switch (strtolower($op)) {
                case 'float':
                case 'decimal':
                    $pos_2 = strpos($params, ':');
                    $p = $pos_2 ? trim(substr($params, 0, $pos_2)) : $params;
                    $precision = $pos_2 ? intval(empty($result = trim(substr($params, $pos_2 + 1))) ? 2 : $result) : 2;
                    return sprintf("%." . $precision . "f", $p);
                case 'int':
                    return sprintf("%s", intval($params));
                case 'str':
                case 'string':
                    return sprintf("'%s'", $params);
                case 'upper':
                    return sprintf("\strtoupper('%s')", $params);
                case 'lower':
                    return sprintf("\strtolower('%s')", $params);
                case 'date':
                    return sprintf("strtotime(date('%s'))", $params);
                default:
                    return $value;
            }
        }
        return $value;
    }
}
