<?php

namespace Datashaman\Elasticsearch\Cache;

use Elasticsearch\ClientBuilder;

class CacheManager extends \Illuminate\Cache\CacheManager
{
    /**
     * Create an instance of the Elasticsearch cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\ApcStore
     */
    protected function createElasticsearchDriver(array $config)
    {
        $prefix = $this->getPrefix($config);

        $client = ClientBuilder::fromConfig($config['client']);

        return $this->repository(new ElasticsearchStore($client, $prefix, $config['index']));
    }
}
