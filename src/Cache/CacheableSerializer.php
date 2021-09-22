<?php

namespace Drewlabs\ComponentGenerators\Cache;

use Drewlabs\ComponentGenerators\Contracts\Cacheable;
use Drewlabs\Filesystem\Exceptions\ReadFileException;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use Drewlabs\Filesystem\Exceptions\FileNotFoundException;
use Drewlabs\Filesystem\File as FileContract;

use function Drewlabs\Filesystem\Proxy\Directory;
use function Drewlabs\Filesystem\Proxy\File;
use function Drewlabs\Filesystem\Proxy\Path;

class CacheableSerializer
{

    /**
     * 
     * @var FileContract
     */
    private $path_;

    public function __construct(string $path = null)
    {
        $this->path_ = $path ? File($path) : File(
            Path(
                sprintf(
                    "%s%s%s",
                    drewlabs_component_generator_cache_path(),
                    DIRECTORY_SEPARATOR,
                    'components'
                )
            )->canonicalize()->__toString()
        );
    }

    /**
     * 
     * @param object|array $value 
     * @return mixed 
     */
    public function dump(Cacheable $value)
    {
        (Directory(Path((string)$this->path_)->dirname())->createIfNotExists());
        return $this->path_->write($value->serialize());
    }

     /**
      * 
      * @param string|Cacheable $type 
      * @return Cacheable 
      * @throws ReadFileException 
      * @throws UnableToRetrieveMetadataException 
      * @throws FileNotFoundException 
      */
    public function load($type)
    {
        if (!$this->path_->exists()) {
            return null;
        }
        return call_user_func([is_string($type) ? new $type : $type, 'unserialize'], $this->path_->read());
    }
}