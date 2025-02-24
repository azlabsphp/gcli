<?php

namespace Drewlabs\GCli\Plugins\Laravel\Observers;

final class Expression
{
    /** @var string */
    private $value;

    /**
     * create expression instance
     * 
     * @param string $name 
     * @param string $value 
     * @return void 
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * expression class factory constructor
     * 
     * @param string $value 
     * @return static 
     */
    public static function new(string $value)
    {
        return new static($value);
    }

    public function read(string $name, ?int &$offset = null)
    {
        if (
            // We do a case insensitive check because case sensisitivity should not fail the rules
            (strtolower(mb_substr($this->value, 0, $len = strlen($name))) === strtolower($name)) &&
            $offset_1 = mb_strpos($this->value, '(') &&
            $offset_2 = mb_strpos($this->value, ')')
        ) {
            $offset = $offset_2;
            /** Swaps value of specificied indexes of the provided array */
            $swap_values = function (array &$array, $index_1, $index_2) {
                $tmp = $array[$index_1];
                $array[$index_1] = $array[$index_2];
                $array[$index_2] = $tmp;
            };

            // Add a PropertyChangeLogical expression and return
            $condition = trim(mb_substr($this->value, $offset_1 + $len, $offset_2 - (strlen(mb_substr($this->value, 0, $offset_1 + $len)))));

            $p = array_map(function ($item) {
                return trim($item);
            }, explode(',', $condition));

            if (($count = count($p)) < 1) {
                return [];
            }

            if ($count >= 3) {
                $swap_values($p, 1, 2);
            }

            return $p;
        }
        return [];
    }
}
