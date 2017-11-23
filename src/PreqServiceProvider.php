<?php

namespace Per3evere\Preq;

use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Support\ServiceProvider;

/**
 * Class PreqServiceProvider
 *
 */
class PreqServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the configuration
     *
     * @return void
     */
    public function boot()
    {
        $source = realpath(__DIR__ . '/../config/preq.php');

        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('preq.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('preq');
        }

        $this->mergeConfigFrom($source, 'preq');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('preq', function ($app) {
            $config = $app->make('config')->get('preq');

            $stateStorage = app(IlluminateStateStorage::class);

            return new CommandFactory(
                $config,
                new CircuitBreakerFactory($stateStorage),
                new CommandMetricsFactory($stateStorage),
                new RequestCache(),
                new RequestLog()
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['preq'];
    }
}
