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

namespace Drewlabs\ComponentGenerators;

use Drewlabs\ComponentGenerators\Contracts\ScriptWriter;
use Drewlabs\ComponentGenerators\Contracts\Writable;
use function Drewlabs\Filesystem\Proxy\LocalFileSystem;

use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCheckExistence;

class ComponentsScriptWriter implements ScriptWriter
{
    /**
     * The base location of the generated scripts.
     *
     * @var FilesystemAdapter
     */
    private $fileSystemAdapter_;

    public function __construct(string $srcPath)
    {
        if (!\is_string($srcPath)) {
            throw new \InvalidArgumentException('$srcPath must be a valid PHP string');
        }
        $this->fileSystemAdapter_ = LocalFileSystem($srcPath);
    }

    public function write(Writable $writable)
    {
        return $this->fileSystemAdapter_->write(
            $writable->getPath(),
            $writable->__toString(),
            new Config()
        );
    }

    /**
     * Check if source script already exists
     * 
     * @param Writable $writable 
     * @return bool 
     * @throws FilesystemException 
     * @throws UnableToCheckExistence 
     */
    public function fileExists(Writable $writable)
    {
        try {
            return $this->fileSystemAdapter_->fileExists($writable->getPath());
        } catch (UnableToCheckExistence $th) {
            return false;
        }
    }
}
