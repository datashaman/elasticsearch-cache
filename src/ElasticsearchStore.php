<?php

namespace Datashaman\Elasticsearch\Cache;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\ServerErrorResponseException;
use GuzzleHttp\Ring\Future\FutureArray;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Store;

class ElasticsearchStore extends TaggableStore implements Store
{
    /**
     * The Elasticsearch client instance.
     *
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * The prefix, used in creating the Elasticsearch index name (prefix-cache).
     *
     * @var string
     */
    protected $prefix;

    /**
     * The Elasticsearch document type.
     *
     * @var string
     */
    protected $type = 'cache';

    /**
     * Create a new Elasticsearch store.
     *
     * @param  \Elasticsearch\Client  $client
     * @param  string                 $prefix
     * @return void
     */
    public function __construct(Client $client, $prefix='')
    {
        $this->setPrefix($prefix);
        $this->client = $client;
    }

    protected function unserialize($value)
    {
        // return json_decode($value, true);
        return is_numeric($value) ? $value : unserialize($value);
    }

    protected function serialize($value)
    {
        // return json_encode($value);
        return is_numeric($value) ? $value : serialize($value);
    }

    protected function resolveWait($result)
    {
        while ($result instanceof FutureArray) {
            $result = $result->wait();
        }
        return $result;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        $tries = 0;

        while (true) {
            try {
                $response = $this->client->get([
                    'index' => $this->getIndex(),
                    'type' => $this->type,
                    'id' => $key,
                ]);

                return $this->unserialize($response['_source']['value']);
            } catch (Missing404Exception $e) {
                return null;
            } catch (ServerErrorResponseException $e) {
                // assume it'll clear up, sleep for a bit
                sleep(1);
                $tries++;
            }

            if ($tries > 3) {
                return null;
            }
        }
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        $params = [
            'index' => $this->getIndex(),
            'body' => [
                'query' => [
                    'constant_score' => [
                        'filter' => [
                            'ids' => [
                                'type' => $this->type,
                                'values' => $keys,
                            ],
                        ],
                    ],
                ],
            ],
            'size' => count($keys),
        ];

        $response = $this->client->search($params);

        $return = collect($response['hits']['hits'])
            ->reduce(function ($carry, $hit) {
                $carry[$hit['_id']] = $this->unserialize($hit['_source']['value']);
                return $carry;
            }, collect())
            ->all();

        return $return;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        $value = $this->serialize($value);

        $params = [
            'index' => $this->getIndex(),
            'type' => $this->type,
            'id' => $key,
            'body' => compact('value'),
        ];

        if ($minutes > 0) {
            $params['ttl'] = $minutes.'m';
        }

        $this->resolveWait($this->client->index($params));
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param  array  $values
     * @param  int  $minutes
     * @return void
     */
    public function putMany(array $values, $minutes)
    {
        $meta = [];

        if ($minutes > 0) {
            $meta['_ttl'] = $minutes.'m';
        }

        $rows = collect();

        foreach ($values as $key => $raw) {
            $meta['_id'] = $key;

            $params['index'] = $meta;
            $rows[] = $params;

            $value = $this->serialize($raw);
            $rows[] = compact('value');
        }

        $body = $rows->map('json_encode')
            ->implode("\n") . "\n";

        $params = [
            'index' => $this->getIndex(),
            'type' => $this->type,
            'body' => $body,
        ];

        $this->resolveWait($this->client->bulk($params));
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        $original = $this->get($key);

        $value += $original;

        $doc = compact('value');

        $params = [
            'index' => $this->getIndex(),
            'type' => $this->type,
            'id' => $key,
            'body' => compact('doc'),
            'retry_on_conflict' => 3,
        ];

        $this->resolveWait($this->client->update($params));
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, -$value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        $params = [
            'index' => $this->getIndex(),
            'type' => $this->type,
            'id' => $key,
        ];

        return $this->resolveWait($this->client->delete($params));
    }

    public function scrollAndScan(callable $callback, $scroll = '10s', $size = 50, $query = ['match_all' => []])
    {
        $params = [
            'search_type' => 'scan',    // use search_type=scan
            'scroll' => $scroll,        // the length of time to hold the context consistent (make it short, eg 10s)
            'size' => $size,            // how many results *per shard* you want back
            'index' => $this->getIndex(),
            'type' => $this->type,
            'body' => [
                'query' => $query,
            ]
        ];

        $response = $this->client->search($params);
        $scroll_id = $response['_scroll_id'];

        while (true) {
            $response = $this->client->scroll(compact('scroll_id', 'scroll'));

            if (count($response['hits']['hits']) > 0) {
                call_user_func($callback, $response['hits']['hits']);
                $scroll_id = $response['_scroll_id'];
            } else {
                break;
            }
        }
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        $this->scrollAndScan(function ($hits) {
            collect($hits)->each(function ($hit) {
                $this->forget($hit['_id']);
            });
        });
    }

    /**
     * Get the underlying Elasticsearch client.
     *
     * @return \Elasticsearch\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get the prefix. (used as index)
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the prefix. (used as index)
     *
     * @param  string  $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Get the Elasticsearch index name.
     *
     * @return string
     */
    public function getIndex()
    {
        return $this->prefix.'-cache';
    }
}
