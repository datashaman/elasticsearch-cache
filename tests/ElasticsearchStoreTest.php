<?php

namespace Datashaman\Elasticsearch\Cache\Tests;

use Datashaman\Elasticsearch\Cache\ElasticsearchStore;
use Elasticsearch\ClientBuilder;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class ElasticsearchStoreTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->client = ClientBuilder::create()->build();

        if($this->client->indices()->exists(['index' => 'cache'])) {
            $this->resolveWait($this->client->indices()->delete([
                'index' => 'cache',
            ]));
        }

        $this->resolveWait($this->client->indices()->create([
            'index' => 'cache',
            'body' => [
                'mappings' => [
                    'item' => [
                        '_ttl' => [
                            'enabled' => true,
                        ],
                        'properties' => [
                            'value' => [
                                'type' => 'string',
                                'index' => 'not_analyzed',
                            ],
                        ],
                    ],
                ],
            ],
            'client' => [
                'future' => 'lazy',
            ],
        ]));

        $this->store = new ElasticsearchStore($this->client);
    }

    protected function resolveWait($result)
    {
        while ($result instanceof FutureArray) {
            $result = $result->wait();
        }
        return $result;
    }

    public function testNonExistentGet()
    {
        $this->assertNull($this->store->get('foo'));
    }

    public function testExistentGet()
    {
        $this->store->forever('foo', 'bar');
        $this->assertEquals('bar', $this->store->get('foo'));
    }

    public function testGetMany()
    {
        $this->store->forever('foo', 'bar');
        $this->store->forever('bar', 'baz');
        sleep(1);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $this->store->many(['foo', 'bar']));
    }

    public function testPut()
    {
        $this->store->put('foo', 'bar', 1);
        $this->assertEquals('bar', $this->store->get('foo'));
    }

    public function testPutMany()
    {
        $this->store->putMany([
            'foo' => 'bar',
            'bar' => 'baz',
        ], 5);
        sleep(1);

        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $this->store->many(['foo', 'bar']));
    }

    public function testIncrement()
    {
        $this->store->forever('foo', 2);
        $this->store->increment('foo', 5);
        $this->assertEquals(7, $this->store->get('foo'));
    }

    public function testDecrement()
    {
        $this->store->forever('foo', 7);
        $this->store->decrement('foo', 2);
        $this->assertEquals(5, $this->store->get('foo'));
    }

    public function testForget()
    {
        $this->store->forever('foo', 7);
        $this->store->forget('foo');
        $this->assertNull($this->store->get('foo'));
    }

    public function testFlush()
    {
        $stdClass = new \stdClass;
        $stdClass->id = 1;

        $this->store->forever('foo', 7);
        $this->store->forever('bar', '12');
        $this->store->forever('bat', $stdClass);

        sleep(1);

        $this->store->flush();

        sleep(1);

        $this->assertNull($this->store->get('foo'));
        $this->assertNull($this->store->get('bar'));
        $this->assertNull($this->store->get('bat'));
    }

    public function tearDown()
    {
        // $this->client->indices()->delete(['index' => 'cache']);
        parent::tearDown();
    }
}
