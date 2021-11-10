<?php

use Illuminate\Container\Container;

if (!function_exists('drewlabs_code_generator_is_running_lumen_app')) {
    /**
     * Return the default value of the given value.
     *
     * @param  \stdClass  $value
     * @return mixed
     */
    function drewlabs_code_generator_is_running_lumen_app($callback)
    {
        return (get_class($callback) === "Laravel\Lumen\Application") && preg_match('/(5\.[5-8]\..*)|(6\..*)|(7\..*)|(8\..*)/', $callback->version());
    }
}

if (!function_exists('config')) {
    /**
     * Reads a given entry from the configuration manager
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        return Container::getInstance()->make('config')->get($key, $default);
    }
}