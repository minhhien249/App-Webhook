<?php

namespace Secomapp\Traits;

use Carbon\Carbon;
use Secomapp\Models\Shop;

trait ShouldClean
{
    /**
     * Check a shop should be cleaned
     *
     * @param \Secomapp\Models\Shop $shop
     * @return boolean
     */
    protected function shouldClean($shop)
    {
        return (
            is_null($shop->access_token)
            && in_array($shop->info->country_code, Shop::EU_COUNTRIES)
            && ($shop->cleaned_at < $shop->installed_at || is_null($shop->cleaned_at))
            && !is_null($shop->uninstalled_at)
            && $shop->uninstalled_at->diffInDays(Carbon::now()) > config('shopify.remove_days')
        );
    }
}