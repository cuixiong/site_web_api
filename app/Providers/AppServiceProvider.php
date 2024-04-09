<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Common;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //

        Common::$siteUploadPathPrefix = '/site/' . env('APP_NAME');
    }
}
