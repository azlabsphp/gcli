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

namespace Drewlabs\ComponentGenerators\Exceptions;

use Exception;

class IOException extends Exception
{
    /**
     * Create a not readable I/O exception instance
     * 
     * @param string $path 
     * @return static 
     */
    public static function readable(string $path)
    {
        $message = sprintf('Resource at path %s is not readable', $path);
        return new static($message);
    }

    /**
     * Create an I/O exception instance for missing error
     * 
     * @param string $path 
     * @return static 
     */
    public static function missing(string $path)
    {
        $message = sprintf('Disk resource at path %s is missing', $path);
        return new static($message);
    }

}