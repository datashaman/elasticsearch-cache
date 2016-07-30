<?php

namespace Datashaman\Elasticsearch\Cache\Tests;

use DB;
use Eloquent;
use Mockery as m;
use Schema;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            // 'Illuminate\Cache\CacheServiceProvider',
            'Datashaman\Elasticsearch\Cache\ElasticsearchCacheServiceProvider',
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Cache' => 'Illuminate\Support\Facades\Cache',
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'elasticsearch');
        $app['config']->set('cache.stores.elasticsearch', [
            'driver' => 'elasticsearch',
            'client' => [
            ],
            'index' => 'cache',
        ]);

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function setUp()
    {
        parent::setUp();
        Eloquent::unguard();
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }
}
