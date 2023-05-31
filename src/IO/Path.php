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

use Drewlabs\GCli\Exceptions\IOException;

class Path
{

    /**
     * @var string
     */
    private $value;

    /**
     * Creates class instance
     * 
     * @param string $path 
     * @return void 
     */
    public function __construct(string $path)
    {
        $this->value = './' === substr($path, 0, 2) ? substr($path, 2) : $path;
    }

    /**
     * Creates new path instance
     * 
     * @param string $path
     * 
     * @return Path 
     */
    public static function new(string $path = './')
    {
        return new self($path);
    }

    public function __toString()
    {
        return $this->value;
    }

    /**
     * Check if the path is an absolute path or a relative path.
     */
    public function isAbsolute(): bool
    {
        $path = $this->value;
        if (('' === $path) || (null === $path)) {
            return false;
        }

        // Remove scheme
        if (false !== ($schemeCharPos = mb_strpos($path, '://'))) {
            $path = mb_substr($path, $schemeCharPos + 3);
        }

        $first = $path[0];
        if ('/' === $first || '\\' === $first) {
            return true;
        }
        // Windows style root
        if (mb_strlen($path) > 1 && ctype_alpha($first) && ':' === $path[1]) {
            if (2 === mb_strlen($path)) {
                return true;
            }
            if ('/' === $path[2] || '\\' === $path[2]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return boolean value indicating whether the path is a directory
     * or not.
     *
     * @return bool
     */
    public function isDirectory()
    {
        if (null === $this->value) {
            return false;
        }
        return is_dir($this->value);
    }

    /**
     * Tells if path exists or not.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->isDirectory() || ($this->isFile());
    }

    /**
     * Returns the path base name
     * @return string|array 
     * @throws IOException 
     */
    public function basename()
    {
        if (($result = @pathinfo($this->value, \PATHINFO_BASENAME)) === false) {
            throw IOException::metadata($this->value, error_get_last()['message'] ?? '', 'basename');
        }
        return $result;
    }

    /**
     * Extracts the directory name of the path object.
     *
     * @return string[]|string
     */
    public function dirname()
    {
        if (($result = @pathinfo($this->value, \PATHINFO_DIRNAME)) === false) {
            throw IOException::metadata($this->value, error_get_last()['message'] ?? '', 'dirname');
        }
        return $result;
    }

    /**
     * Extracts the extension from the path object.
     *
     * @return string[]|string
     */
    public function extension()
    {
        if (($result = @pathinfo($this->value, \PATHINFO_EXTENSION)) === false) {
            throw IOException::metadata($this->value, error_get_last()['message'] ?? '', 'extension');
        }

        return $result;
    }

    /**
     * Extracts the filename from the path object.
     *
     * @return string[]|string
     */
    public function filename()
    {
        if (($result = @pathinfo($this->value, \PATHINFO_FILENAME)) === false) {
            throw IOException::metadata($this->value, error_get_last()['message'] ?? '', 'filename');
        }

        return $result;
    }

    /**
     * Determine if the given path is a file.
     *
     * @return bool
     */
    public function isFile()
    {
        return is_file($this->value);
    }

    /**
     * Determine if the given path is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return is_writable($this->value);
    }

    /**
     * Determine if the given path is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        return is_readable($this->value);
    }

    /**
     * Find path names matching a given pattern.
     *
     * @param mixed $pattern
     * @param int   $flags
     *
     * @return array|false
     */
    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * Create a symlink to the path. On Windows, a hard link is created if this path is a file.
     *
     * @return void
     */
    public function link(string $link)
    {
        if (!$this->exists()) {
            throw IOException::missing($this->value);
        }
        if ('Windows' === !\PHP_OS_FAMILY) {
            return symlink($this->value, $link);
        }

        $mode = $this->isDirectory() ? 'J' : 'H';

        exec("mklink /{$mode} " . escapeshellarg($link) . ' ' . escapeshellarg($this->value));
    }

    /**
     * Normalizes the given path.
     *
     * During normalization, all backward slashes (\) are replaced by forward slashes ("/").
     *
     * This method is able to deal with both UNIX and Windows paths.
     */
    public function normalize()
    {
        return static::new(str_replace('\\', '/', $this->value));
    }

    /**
     * Returns whether the given path is on the local filesystem.
     */
    public function isLocal(): bool
    {
        return '' !== $this->value && false === mb_strpos($this->value, '://');
    }

    /**
     * Join list of paths to the current path.
     *
     * @param string[] $paths
     *
     * @throws \RuntimeException
     *
     * @return string|Path
     */
    public function join(string ...$paths)
    {
        $output = null;
        $wasScheme = false;

        $paths = array_merge([$this->value], $paths ?? []);

        foreach ($paths as $path) {
            if ('' === $path) {
                continue;
            }

            if (null === $output) {
                // For first part we keep slashes, like '/top', 'C:\' or 'phar://'
                $output = $path;
                $wasScheme = false !== mb_strpos($path, '://');
                continue;
            }

            // Only add slash if previous part didn't end with '/' or '\'
            if (!\in_array(mb_substr($output, -1), ['/', '\\'], true)) {
                $output .= '/';
            }

            // If first part included a scheme like 'phar://' we allow \current part to start with '/', otherwise trim
            $output .= $wasScheme ? $path : ltrim($path, '/');
            $wasScheme = false;
        }

        if (null === $output) {
            return '';
        }

        return static::new($output);
    }
}
