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

/**
 * Returns the path to the cache path of the current package.
 *
 * @return string
 */
function drewlabs_component_generator_cache_path()
{
    return __DIR__.'/../.cache/';
}
