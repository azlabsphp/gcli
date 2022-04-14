<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Container\Container;

if (!function_exists('drewlabs_code_generator_is_running_lumen_app')) {
    /**
     * Return the default value of the given value.
     *
     * @return mixed
     */
    function drewlabs_code_generator_is_running_lumen_app($callback)
    {
        return ("Laravel\Lumen\Application" === get_class($callback)) && preg_match('/(5\.[5-8]\..*)|(6\..*)|(7\..*)|(8\..*)|(9\..*)/', $callback->version());
    }
}

if (!function_exists('config')) {
    /**
     * Reads a given entry from the configuration manager.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        return Container::getInstance()->make('config')->get($key, $default);
    }
}
