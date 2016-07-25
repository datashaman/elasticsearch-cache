<?php

namespace Datashaman\Library;

use DB;
use Eloquent;
use Mockery as m;
use Schema;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetup($app)
    {
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
