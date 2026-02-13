<?php

namespace Tests;

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Cache;
use App\Core\TestCase;

class CacheTest extends TestCase
{
    public function testSetAndGet()
    {
        $cache = new Cache(__DIR__ . '/../storage/cache_test');

        $cache->set('foo', 'bar', 10);
        $this->assertEquals('bar', $cache->get('foo'), 'Cache should return the stored value');

        $cache->forget('foo');
        $this->assertTrue($cache->get('foo') === null, 'Cache should return null after forget');
    }
}

$test = new CacheTest();
$test->run();
