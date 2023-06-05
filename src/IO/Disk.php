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

namespace Drewlabs\GCli\IO;

use Drewlabs\GCli\Exceptions\IOException;

class Disk
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * Creates new class instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Creates new class instance.
     *
     * @return Disk
     */
    public static function new(string $base)
    {
        $object = new self();

        // Set the base path
        $object->setBasePath(self::normalizeBasePath($base));

        // Return the constructed object
        return $object;
    }

    /**
     * Set the filesystem base path.
     *
     * @return Disk
     */
    public function setBasePath(string $base)
    {
        $this->basePath = $base;

        return $this;
    }

    /**
     * Read from resource at path.
     *
     * @throws IOException
     *
     * @return string|false
     */
    public function read(string $path, ?int $length = null, int $offset = 0, int $mode = \LOCK_EX | \LOCK_NB)
    {
        return Reader::open($this->resolvePath($path))->read($length, $offset, $mode);
    }

    /**
     * Write to the resource at path.
     *
     * @param int $mode
     *
     * @throws IOException
     *
     * @return int|false
     */
    public function write(string $path, string $data, ?int $length = null, $mode = \LOCK_EX | \LOCK_NB)
    {
        $path = $this->resolvePath($path);
        $this->ensureDirectoryExists(Path::new($path)->dirname());

        return Writer::open($path)->write($data, $length, $mode);
    }

    /**
     * Checks if path exists.
     *
     * @return bool
     */
    public function exists(string $path)
    {
        return Path::new($this->resolvePath($path))->exists();
    }

    /**
     * Resolve path to parent directory resource.
     *
     * @return string
     */
    private function parentDirectoryPath(string $path, string $base)
    {
        $substr = substr($path, 0, 3);
        // Handle relative path
        if (('../' === $substr) || ('..\\' === $substr)) {
            $directory = $base;
            $sub = substr($path, 0, 3);
            while (('../' === $sub) || ('..\\' === $sub)) {
                $directory = \dirname($directory);
                $path = substr($path, 3);
                $sub = substr($path, 0, 3);
            }
            $path = $directory.\DIRECTORY_SEPARATOR.$path;
        }

        return $path;
    }

    /**
     * Resolve path to current directory resource.
     *
     * @return string
     */
    private function relativePath(string $path, string $base)
    {
        $substr = substr($path, 0, 2);
        // Handle relative path
        if (('./' === $substr) || ('.\\' === $substr)) {
            $path = $base.\DIRECTORY_SEPARATOR.substr($path, 2);
        }

        return $path;
    }

    /**
     * Resolve the path to the resource.
     *
     * @return string
     */
    private function resolvePath(string $path)
    {
        $substr = substr($path, 0, 3);
        $path = ('../' === $substr) || ('..\\' === $substr) ? $this->parentDirectoryPath($path, $this->basePath) : (($subsustr = substr($substr, 0, 2)) && (('./' === $subsustr) || ('.\\' === $subsustr)) ? $this->relativePath($path, $this->basePath) : $path);
        // If the path does not starts with '/' we append the current
        if ('/' !== substr($path, 0, 1)) {
            $path = $this->basePath.\DIRECTORY_SEPARATOR.$path;
        }

        return $path;
    }

    /**
     * Create directory if it does not exists.
     *
     * @return bool
     */
    private function ensureDirectoryExists(string $dirname)
    {
        return Directory::new($dirname)->create();
    }

    /**
     * Normalize base path variable.
     *
     * @return string
     */
    private static function normalizeBasePath(string $path)
    {
        return rtrim(Path::new($path)->normalize()->__toString(), \DIRECTORY_SEPARATOR);
    }
}
