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

/**
 * Returns the path to the cache path of the current package.
 *
 * @return string
 */
function drewlabs_component_generator_cache_path()
{
    return __DIR__.'/.cache';
}

if (!function_exists('drewlabs_code_generator_is_running_lumen_app')) {
    /**
     * Return the default value of the given value.
     *
     * @return mixed
     */
    function drewlabs_code_generator_is_running_lumen_app($callback)
    {
        return ("Laravel\Lumen\Application" === $callback::class) && preg_match('/(5\.[5-8]\..*)|(6\..*)|(7\..*)|(8\..*)|(9\..*)/', $callback->version());
    }
}

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string|null $abstract
     *
     * @return mixed|Illuminate\Contracts\Foundation\Application
     */
    function app($abstract = null, array $parameters = [])
    {
        if (null === $abstract) {
            return Illuminate\Container\Container::getInstance();
        }

        return Illuminate\Container\Container::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param array|string|null $key
     * @param mixed             $default
     *
     * @return mixed|Illuminate\Config\Repository
     */
    function config($key = null, $default = null)
    {
        if (null === $key) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
    }
}


if (!function_exists('str_starts_with')) {

    /** The function returns `true` if the passed $haystack starts from the $needle string or `false` otherwise */
    function str_starts_with(string $haystack, string $needle): bool
    {
        return 0 === strncmp($haystack, $needle, \strlen($needle));
    }
}


if (!function_exists('str_ends_with')) {
    /** The function returns true if the passed $haystack ends with the $needle string or false otherwise */
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ('' === $needle || $needle === $haystack) {
            return true;
        }

        if ('' === $haystack) {
            return false;
        }

        $len = \strlen($needle);

        return $len <= \strlen($haystack) && 0 === substr_compare($haystack, $needle, -$len);
    }
}