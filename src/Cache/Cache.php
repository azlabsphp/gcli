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

namespace Drewlabs\GCli\Cache;

use Drewlabs\GCli\Contracts\Cacheable;
use Drewlabs\GCli\IO\Directory;
use Drewlabs\GCli\IO\Path;
use Drewlabs\GCli\IO\Reader;
use Drewlabs\GCli\IO\Writer;

class Cache
{
    /**
     * @var Path
     */
    private $path;

    /**
     * Creates class instance.
     */
    public function __construct(?string $path = null)
    {
        $this->path = $path ? Path::new($path) : Path::new(sprintf('%s%s%s', drewlabs_component_generator_cache_path(), \DIRECTORY_SEPARATOR, 'components'));
    }

    /**
     * Creates new class instance.
     *
     * @return Cache
     */
    public static function new(?string $path = null)
    {
        return new self($path);
    }

    /**
     * @param object|array $value
     *
     * @return mixed
     */
    public function dump(Cacheable $value)
    {
        Directory::new($this->path->dirname())->create();

        return Writer::open($this->path->__toString())->write($value->serialize());
    }

    /**
     * @param string|Cacheable $type
     *
     * @throws \Exception
     *
     * @return Cacheable
     */
    public function load($type)
    {
        if (!$this->path->exists()) {
            return null;
        }

        return \call_user_func([\is_string($type) ? new $type() : $type, 'unserialize'], Reader::open($this->path->__toString())->read());
    }
}
