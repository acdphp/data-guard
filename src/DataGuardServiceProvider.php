<?php

namespace Cdinopol\DataGuard;

use Illuminate\Support\ServiceProvider;

class DataGuardServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('dataGuard', DataGuard::class);

        $this->mergeConfigFrom(
            __DIR__.'/../config/dataguard.php',
            'dataguard'
        );
    }
}
