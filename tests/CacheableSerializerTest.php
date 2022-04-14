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

namespace Drewlabs\ComponentGenerators\Tests;

use Drewlabs\ComponentGenerators\Cache\CacheableSerializer;
use Drewlabs\ComponentGenerators\Cache\CacheableTables;

use function Drewlabs\Filesystem\Proxy\Path;

class CacheableSerializerTest extends TestCase
{
    public function getPath()
    {
        return Path(
            sprintf(
                '%s%s%s',
                drewlabs_component_generator_cache_path(),
                \DIRECTORY_SEPARATOR.'subdir'.\DIRECTORY_SEPARATOR,
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
                'acl_authorizations',
            ],
            'namespace' => 'App',
            'subNamespace' => 'Auth',
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
        $this->assertTrue('App' === $cacheable->getNamespace());
        $this->assertTrue('Auth' === $cacheable->getSubNamespace());
    }
}
