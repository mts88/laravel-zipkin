<?php

namespace Mts88\LaravelZipkin\Providers;

use Mts88\LaravelZipkin\Services\ZipkinService;
use \Illuminate\Support\ServiceProvider;

class LaravelZipkinServiceProvider extends ServiceProvider
{

    const CONFIG_URI = '/../../config/zipkin.php';

    /**
     * Publishes configuration file.
     *
     * @return  void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . self::CONFIG_URI => config_path('zipkin.php'),
        ]);
    }
    /**
     * Make config publishment optional by merging the config from the package.
     *
     * @return  void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . self::CONFIG_URI,
            'zipkin'
        );

        $instance = new ZipkinService();

        $this->app->singleton(ZipkinService::class, function ($app) use ($instance) {
            return $instance;
        });

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [ZipkinService::class];
    }

}
