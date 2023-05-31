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

namespace Drewlabs\GCli;

use Drewlabs\GCli\Contracts\ScriptWriter;
use Drewlabs\GCli\Contracts\Writable;
use Drewlabs\GCli\IO\Disk;
use InvalidArgumentException;

class ComponentsScriptWriter implements ScriptWriter
{
    /**
     * The base location of the generated scripts.
     *
     * @var Disk
     */
    private $disk;

    /**
     * Creates new class instance
     * 
     * @param string $src 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public function __construct(string $src)
    {
        if (!\is_string($src)) {
            throw new \InvalidArgumentException('$srcPath must be a valid PHP string');
        }
        $this->disk = Disk::new($src);
    }

    public function write(Writable $writable)
    {
        return $this->disk->write($writable->getPath(), $writable->__toString());
    }

    /**
     * Check if source script already exists.
     *
     * @return bool
     */
    public function fileExists(Writable $writable)
    {
        return $this->disk->exists($writable->getPath());
    }
}
