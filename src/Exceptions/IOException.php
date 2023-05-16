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
     * 
     * @var string
     */
    private $path;

    /**
     * Create a not readable I/O exception instance
     * 
     * @param string $path 
     * @return static 
     */
    public static function readable(string $path)
    {
        $message = sprintf('Resource at path %s is not readable', $path);
        $object =  new static($message);
        $object->setPath($path);
        return $object;
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


    /**
     * IOException instance for metadata attribute
     * 
     * @param string $path 
     * @param string $error 
     * @param string $attribute 
     * @return static 
     */
    public static function metadata(string $path, string $error, string $attribute)
    {
        $message = sprintf('Cannot retrieve %s informations at "%s". %s', $attribute, $path, $error);
        $object =  new static($message);
        $object->setPath($path);
        return $object;
    }

    /**
     * Set the path attribute for the exception
     * 
     * @param string $path 
     * @return self 
     */
    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Returns the path instance
     * 
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }
}