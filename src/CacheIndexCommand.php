<?php

namespace Datashaman\Elasticsearch\Cache;

use Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;

class CacheIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:index {index=laravel-cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an index for the Elasticsearch cache (DESTRUCTIVE, overwrites existing cache index).';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $client = ClientBuilder::create()->build();
        $index = $this->argument('index', 'laravel-cache');

        $client->indices()->delete([
            'index' => $index,
        ]);

        $client->indices()->create([
            'index' => $index,
            'body' => [
                'mappings' => [
                    'cache' => [
                        'properties' => [
                            'value' => [
                                'type' => 'string',
                                'index' => 'not_analyzed',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
