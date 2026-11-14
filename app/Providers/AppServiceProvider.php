<?php

declare(strict_types=1);

namespace App\Providers;

use App\Socialite\HiveProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Socialite::extend('hive', function (Application $app): HiveProvider {
            $config = $app['config']['services.hive'];
            $verify = $config['verify'];
            $caBundle = $config['ca_bundle'];

            if ($verify && is_string($caBundle) && trim($caBundle) !== '') {
                $caBundle = trim($caBundle);

                if (! is_file($caBundle) || ! is_readable($caBundle)) {
                    throw new InvalidArgumentException('HIVE_CA_BUNDLE must point to a readable CA certificate file.');
                }

                $verify = $caBundle;
            }

            return new HiveProvider(
                $app['request'],
                $config['client_id'],
                $config['client_secret'],
                $config['redirect'],
                ['verify' => $verify],
            );
        });
    }
}
