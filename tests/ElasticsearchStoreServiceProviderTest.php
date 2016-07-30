<?php

namespace Datashaman\Elasticsearch\Cache\Tests;

use Cache;
use Datashaman\Elasticsearch\Cache\CacheManager;
use Datashaman\Elasticsearch\Cache\ClearCommand;
use Datashaman\Elasticsearch\Cache\ElasticsearchStore;

class ElasticsearchCacheServiceProviderTest extends TestCase
{
    public function testCacheGet()
    {
        $this->assertInstanceOf(CacheManager::class, $this->app['cache']);
        $this->assertInstanceOf(ElasticsearchStore::class, $this->app['cache.store']->getStore());
    }
}
