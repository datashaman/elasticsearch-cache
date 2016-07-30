# datashaman/elasticsearch-cache

Laravel-oriented implementation of an Elasticsearch-based cache store.

[![Build Status](https://travis-ci.org/datashaman/elasticsearch-cache.svg?branch=master)](https://travis-ci.org/datashaman/elasticsearch-cache)
[![StyleCI](https://styleci.io/repos/61363628/shield?style=flat)](https://styleci.io/repos/61363628)
[![Code Climate](https://codeclimate.com/github/datashaman/elasticsearch-cache/badges/gpa.svg)](https://codeclimate.com/github/datashaman/elasticsearch-cache)
[![Test Coverage](https://codeclimate.com/github/datashaman/elasticsearch-cache/badges/coverage.svg)](https://codeclimate.com/github/datashaman/elasticsearch-cache/coverage)

## Installation

Install the package from packagist.org by editing composer.json to include the following (only published on github for now):

    {
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/datashaman/elasticsearch-cache"
            }
        ],
        "require": {
            "datashaman/elasticsearch-cache": "dev-master"
        }
    }

Run `composer update` to install the latest package.

*NB* This is currently *ALPHA* quality software. Not for production use yet.

## Usage

Replace the `CacheServiceProvider` in `config/app.php`:

    
    'providers' => [
        ...
        'Illuminate\Broadcasting\BroadcastServiceProvider',
        //'Illuminate\Cache\CacheServiceProvider',
        'Datashaman\Elasticsearch\Cache\ElasticsearchCacheServiceProvider',
        'Illuminate\Foundation\Providers\ConsoleSupportServiceProvider',
        ...
    ]

Create an index to store the cache:

    php artisan cache:index [index]

Index name is *laravel-cache* by default. Ensure that the laravel `prefix` matches `config/cache.php`.

In `.env` file, set `CACHE_DRIVER` to *elasticsearch*, and add the following to `config/cache.php`:

    
	'stores' => [
        ...
		'elasticsearch' => [
            'driver' => 'elasticsearch',
            'client' => [                   # Optional Elasticsearch client config
            ],
        ],
        ...
    ]

Then use the `Cache` facade as you would normally.

## License

This package is licensed under the Apache2 license, summarized below:

Copyright (c) 2016 datashaman <marlinf@datashaman.com>

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
