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

final class Expression
{
    /** @var string */
    private $value;

    /**
     * create expression instance.
     *
     * @return void
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * expression class factory constructor.
     *
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
            (strtolower(substr($this->value, 0, $len = \strlen($name))) === strtolower($name))
            && $offset_1 = strpos($this->value, '(')
            && $offset_2 = strpos($this->value, ')')
        ) {
            $offset = $offset_2;
            // Add a PropertyChangeLogical expression and return
            $condition = trim(substr($this->value, $offset_1 + $len, $offset_2 - \strlen(substr($this->value, 0, $offset_1 + $len))));

            $p = array_map(static function ($item) {
                return trim($item);
            }, explode(',', $condition));

            return $p;
        }

        return [];
    }
}
