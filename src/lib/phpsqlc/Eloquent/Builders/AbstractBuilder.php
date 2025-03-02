<?php

namespace Drewlabs\PHPSQLC\Eloquent\Builders;

use Drewlabs\PHPSQLC\Utils\Expression;

/**
 * This class provides common functionality for all Builder classes.
 * Builder classes are classes which help to construct Query Builder methods.
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
abstract class AbstractBuilder
{

    /**
     * Removes surrounding spaces of a string and turns it to lowercase
     * @param $val
     * @return string
     */
    function getValue($val): string
    {
        return strtolower(trim($val));
    }

    /**
     *
     * Wraps a value, which involves quoting
     * 
     * @param $str
     * @return int|string
     */
    function wrapValue($str): mixed
    {
        if (is_numeric($str)) {
            return $str;
        }

        if ($this->getValue($str) == 'null') {
            return 'null';
        }

        return Expression::quote($str);
    }

    /**
     *
     * Tries to convert given parts of WHERE method to php array
     *
     * @param $parts
     * @return false|string
     */
    protected function arrayify($parts): bool|string
    {
        $disposable = ['=' => ''];
        $keys = array_keys($disposable);

        $all = [];
        foreach ($parts['fields'] as $k => $field) {
            $operator = $parts['operators'][$k];
            if (in_array($this->getValue($operator), $keys)) {
                $operator = $disposable[$this->getValue($operator)];
            }

            $value = $parts['values'][$k];
            if (!empty($operator)) {
                $all[] = '[' . Expression::quote($field) . ', ' . Expression::quote(strtoupper($operator)) . ', ' . $this->wrapValue($value) . ']';
            } else {
                $all[] = '[' . Expression::quote($field) . ', ' . $this->wrapValue($value) . ']';
            }
        }

        if (!empty($all)) {
            return "[" . implode(', ', $all) . ']';
        } else {
            return false;
        }
    }

    /**
     * Concatenates tokens by capitalizing the first char of each token starting from the second
     * Creates a method name compatible to Query Builder
     *
     * @param $tokens
     * @return string
     */
    protected function fnMerger($tokens): string
    {
        $separator = '';
        for ($i = 0; $i < count($tokens); $i++) {
            if ($i > 0) {
                $tokens[$i] = ucfirst($tokens[$i]);
            }
        }

        return implode($separator, $tokens);
    }

    /**
     * Removes surrounding brackets of a given value
     * @param $value
     * @return string
     */
    protected function unBracket($value): string
    {
        if ($value[0] == '(') {
            $value = substr($value, 1);
        }

        if ($value[strlen($value) - 1] == ')') {
            $value = substr($value, 0, strlen($value) - 1);
        }

        $value = preg_replace('/,(\w|\W)/', ', $1', $value);

        return $value;
    }
}
