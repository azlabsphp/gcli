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

namespace Drewlabs\GCli\Tests;

use function Drewlabs\Filesystem\Proxy\Path;

use Drewlabs\GCli\Cache\Cache;

use Drewlabs\GCli\Cache\CacheableTables;

class CacheableSerializerTest extends TestCase
{
    public function getPath()
    {
        return realpath(__DIR__ . '/../.cache') . (DIRECTORY_SEPARATOR . 'dump');
    }

    public function testDumpMethod()
    {
        $serializer = new Cache($this->getPath());
        $serializer->dump(new CacheableTables(
            ['auth_users', 'auth_user_details', 'acl_authorizations'],
            'App',
            'Auth'
        ));
        $this->assertTrue(true);
    }

    public function testLoadMethod()
    {
        $serializer = new Cache($this->getPath());
        /**
         * @var CacheableTables
         */
        $cacheable = $serializer->load(CacheableTables::class);
        $this->assertTrue('App' === $cacheable->getNamespace());
        $this->assertTrue('Auth' === $cacheable->getSubNamespace());
    }
}
