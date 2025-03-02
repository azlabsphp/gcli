<?php

namespace Drewlabs\PHPSQLC\Utils;

final class Expression
{
    /**
     * checks if the operator is a logical operator
     * 
     * @param string $operator 
     * @return bool 
     */
    public static function isLogicalOperator(string $operator)
    {
        return in_array(Expression::lowercase($operator), array('and', 'or'));
    }

    /**
     * Quotes a keyword for Query Builder
     * 
     * @param string $value
     * 
     * @return string
     */
    public static function quote(string $value): string
    {
        $value = trim($value, '\"');
        $value = trim($value, '\'');
        $value = addslashes($value);

        return "'" . static::normalize($value) . "'";
    }

    /**
     * normalize column by removing ` quote
     * 
     * @param string $value 
     * @return string 
     */
    public static function normalize(string $value)
    {
        return strpos($value, '`') !== false ? str_replace('`', '', $value) : $value;  
    }

    /**
     * return the lower representation of a string value
     * 
     * @param string $value 
     * @return string 
     */
    public static function lowercase(string $value)
    {
        return strtolower(trim($value));
    }

    /**
     * Wrap string into quote
     * 
     * @param string $value 
     * @return string 
     */
    public static function wrap(string $value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        if (static::lowercase((string)$value) == 'null') {
            return 'null';
        }

        return static::quote($value);
    }
}
