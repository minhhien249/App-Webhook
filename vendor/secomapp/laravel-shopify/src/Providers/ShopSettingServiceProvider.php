<?php

namespace Secomapp\Providers;


use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Secomapp\Services\ShopSettingApi;

class ShopSettingServiceProvider extends ServiceProvider
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
        App::bind('shop.setting', function () {
            return new ShopSettingApi();
        });
    }
}
