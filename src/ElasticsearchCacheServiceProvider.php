<?php
namespace Datashaman\Elasticsearch\Cache;

use Elasticsearch\ClientBuilder;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Cache\Console\ClearCommand;

class ElasticsearchCacheServiceProvider extends CacheServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->registerCommands();
    }
}
