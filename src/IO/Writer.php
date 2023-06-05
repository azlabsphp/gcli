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

class Writer
{
    /**
     * @var int|resource
     */
    private $handle;

    /**
     * Creates a read writer instance.
     *
     * @param mixed $descriptor
     *
     * @return void
     */
    private function __construct($descriptor)
    {
        $this->handle = $descriptor;
    }

    public function __destruct()
    {
        $this->close();
    }

    public static function open(string $path, $mode = 'w', $include = false, $context = null)
    {
        $fd = @fopen($path, $mode, $include, $context);
        clearstatcache(true, $path);
        if (false === $fd) {
            throw new IOException(sprintf('Error opening stream at path: %s. %s', $path, error_get_last()['message'] ?? ''));
        }

        return new static($fd);
    }

    /**
     * Write a total bytes length to the opened file resource.
     *
     * **Note** Method returns false if was unable to write to
     * file resource because the resource was close or a write error
     * occurs
     *
     * @param int $operation
     *
     * @return int|false
     */
    public function write(string $data, ?int $length = null, $operation = \LOCK_EX | \LOCK_NB)
    {
        // Case the read writer is not a resource, we simply return false
        if (!\is_resource($this->handle)) {
            return false;
        }
        $bytes = false;
        if ($this->handle && @flock($this->handle, $operation)) {
            $bytes = @fwrite($this->handle, $data, $length);
            @flock($this->handle, \LOCK_UN);
        }

        return $bytes;
    }

    /**
     * Closes the readable resource.
     *
     * @return void
     */
    public function close()
    {
        if (null !== $this->handle && \is_resource($this->handle)) {
            fclose($this->handle);
            $this->handle = null;
        }
    }
}
