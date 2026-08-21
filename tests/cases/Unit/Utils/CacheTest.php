<?php

namespace foonoo\tests\Unit\Utils;

use foonoo\tests\BaseTestCase;
use foonoo\utils\Cache;
use foonoo\utils\CacheFactory;
use ntentan\kaikai\backends\FileCache;

class CacheTest extends BaseTestCase
{
    public function testCacheWriteAndRead(): void
    {
        $cacheDir = $this->createVfsDirectory('cache');
        $factory = new CacheFactory();
        $cache = $factory->create($cacheDir);

        $calls = 0;
        $producer = function () use (&$calls) {
            $calls++;
            return ['rendered' => '<p>Rendered Markdown Content</p>'];
        };

        // First call should run the factory producer
        $result1 = $cache->get('page_cache_key', $producer);
        $this->assertSame(1, $calls);
        $this->assertSame(['rendered' => '<p>Rendered Markdown Content</p>'], $result1);

        // Second call should fetch from cache and NOT run producer
        $result2 = $cache->get('page_cache_key', $producer);
        $this->assertSame(1, $calls);
        $this->assertSame($result1, $result2);
    }

    public function testCacheInvalidationByTime(): void
    {
        $cacheDir = $this->createVfsDirectory('cache_time');
        $factory = new CacheFactory();
        $cache = $factory->create($cacheDir);

        $calls = 0;
        $producer = function () use (&$calls) {
            $calls++;
            return "Content version " . $calls;
        };

        // Store first version
        $res1 = $cache->get('time_key', $producer, time() - 100);
        $this->assertSame(1, $calls);
        $this->assertSame("Content version 1", $res1);

        // Access with future modification time forces cache regeneration
        $futureTime = time() + 100;
        $res2 = $cache->get('time_key', $producer, $futureTime);
        $this->assertSame(2, $calls);
        $this->assertSame("Content version 2", $res2);
    }
}
