<?php

namespace Acdphp\DataGuard;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class DataGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DataGuard::class, function () {
            return new DataGuard(
                config('dataguard.separator'),
                config('dataguard.splitter'),
                config('dataguard.array_indicator'),
                config('dataguard.mask_with')
            );
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/dataguard.php',
            'dataguard'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/dataguard.php' => config_path('dataguard.php'),
            ], 'dataguard-config');
        }

        $this->initCollectionMacros();
    }

    protected function initCollectionMacros(): void
    {
        Collection::macro('hide', function (
            string $resource,
            $key = null,
            string $operator = null,
            $value = null
        ) {
            return collect(
                app(DataGuard::class)
                    ->setData($this->toArray())
                    ->hide(...func_get_args())
                    ->getResult()
            );
        });

        Collection::macro('mask', function (
            string $resource,
            $key = null,
            string $operator = null,
            $value = null
        ) {
            return collect(
                app(DataGuard::class)
                    ->setData($this->toArray())
                    ->mask(...func_get_args())
                    ->getResult()
            );
        });
    }
}
