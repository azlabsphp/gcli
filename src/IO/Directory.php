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

namespace Drewlabs\GCli\IO;

class Directory
{
    /**
     * 
     * @var string
     */
    private $path;


    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Creates new directory instance
     * 
     * @param string $path 
     * @return Directory 
     */
    public static function new(string $path)
    {
        return new self($path);
    }


    /**
     * Creates directory for the given path. To override the 
     * 
     * @param bool $override 
     * @return bool 
     */
    public function create(bool $override = false)
    {
        if ($override) {
            // Case we are overriding, we remove the exisiting directory before creating new
            if (is_dir($this->path) && @rmdir($this->path)) {
                return @mkdir($this->path, 0777, true);
            }
        }
        // Create the path directory if not exists
        if (!is_dir($this->path)) {
            return mkdir($this->path, 0777, true);
        }
        return true;
    }
}
