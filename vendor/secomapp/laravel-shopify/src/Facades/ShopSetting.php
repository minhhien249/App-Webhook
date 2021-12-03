<?php

namespace Secomapp\Facades;

use Illuminate\Support\Facades\Facade;
use Secomapp\Services\ShopSettingApi;

class ShopSetting extends Facade
{

    /**
     * Get the registered name of the component.
     * @see ShopSettingApi
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'shop.setting';
    }
}