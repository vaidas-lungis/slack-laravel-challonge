<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class ChallongeHttpClientProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('ChallongeHttpClient', function ($app) {
            return new Client([
                'base_uri' => 'https://api.challonge.com/v1/',
                'auth' => [env('CHALLONGE_USERNAME'), env('CHALLONGE_API_KEY')]]
            );
        });
    }
}
