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
        parent::register();

        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->registerCommands();
    }

    public function registerCommands()
    {
        parent::registerCommands();

        $this->app->singleton('command.cache.index', function ($app) {
            return new CacheIndexCommand();
        });

        $this->commands('command.cache.index');
    }

    public function provides()
    {
        return parent::provides() + [ 'command.cache.index' ];
    }
}
