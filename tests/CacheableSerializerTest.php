<?php

namespace Drewlabs\ComponentGenerators\Tests;

use Drewlabs\ComponentGenerators\Extensions\CacheableTables;
use Drewlabs\ComponentGenerators\Extensions\CacheableSerializer;

use function Drewlabs\Filesystem\Proxy\Path;

class TestCacheableSerializer extends TestCase
{

    public function getPath()
    {
        return Path(
            sprintf(
                "%s%s%s",
                drewlabs_component_generator_cache_path(),
                DIRECTORY_SEPARATOR . 'subdir' . DIRECTORY_SEPARATOR,
                'dump'
            )
        )->canonicalize()->__toString();
    }

    public function testDumpMethod()
    {
        $serializer = new CacheableSerializer($this->getPath());
        $serializer->dump(new CacheableTables([
            'tables' => [
                'auth_users',
                'auth_user_details',
                'acl_authorizations'
            ],
            'namespace' => 'App',
            'subNamespace' => 'Auth'
        ]));
        $this->assertTrue(true);
    }

    public function testLoadMethod()
    {
        $serializer = new CacheableSerializer($this->getPath());
        /**
         * @var CacheableTables
         */
        $cacheable = $serializer->load(CacheableTables::class);
        $this->assertTrue($cacheable->getNamespace() === 'App');
        $this->assertTrue($cacheable->getSubNamespace() === 'Auth');
    }
}
