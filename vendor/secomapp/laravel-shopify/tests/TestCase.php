<?php

namespace Secomapp\Tests;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Orchestra\Testbench\TestCase as Testbench;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

class TestCase extends Testbench
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        // Run package migrations
        $this->artisan('migrate', [
            '--database' => 'testbench',
            '--realpath' => realpath(__DIR__ . '/../database/migrations'),
        ]);

    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->registerEloquentFactory($app);

        // set up database configuration
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Get LaraPlans package service provider.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    public function getPackageProviders($app)
    {
        return ['Secomapp\ShopifyServiceProvider'];
    }

    /**
     * Register the Eloquent factory instance in the container.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function registerEloquentFactory($app)
    {
        $app->singleton(FakerGenerator::class, function () {
            return FakerFactory::create();
        });

        $app->singleton(EloquentFactory::class, function ($app) {
            $faker = $app->make(FakerGenerator::class);

            return EloquentFactory::construct($faker, __DIR__ . '/../database/factories');
        });
    }
}