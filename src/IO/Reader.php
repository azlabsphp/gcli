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

class Reader
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

    public static function open(string $path, $mode = 'r', $include_path = false, $context = null)
    {
        $fd = @fopen($path, $mode, $include_path, $context);
        clearstatcache(true, $path);
        if (false === $fd) {
            throw new IOException(sprintf('Error opening stream at path: %s. %s', $path, error_get_last()['message'] ?? ''));
        }

        return new static($fd);
    }

    /**
     * Read data from the open file resource.
     *
     * **Note** Method returns false if was unable to read from
     * file resource because the resource was close or a read error
     * occurs
     *
     * @param int|null $offset
     *
     * @return string|false
     */
    public function read(?int $length = null, int $offset = 0, int $operation = \LOCK_EX | \LOCK_NB)
    {
        // Case the read writer is not a resource, we simply return false
        if (!\is_resource($this->handle)) {
            return false;
        }
        if (null === $length) {
            $length = \is_array($stats = @fstat($this->handle)) ? $stats['size'] : 0;
        }

        return 0 === $length ? '' : $this->readBytes($length, $operation, $offset ?? 0);
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

    /**
     * Read a total of bytes from file descriptor.
     *
     * @return string|false
     */
    private function readBytes(int $length, int $operation, ?int $offset = null)
    {
        $contents = false;
        if ($this->handle && @flock($this->handle, $operation)) {
            if ($offset) {
                fseek($this->handle, $offset);
            }
            $contents = @fread($this->handle, $length);
            @flock($this->handle, \LOCK_UN);
        }

        return $contents;
    }
}
