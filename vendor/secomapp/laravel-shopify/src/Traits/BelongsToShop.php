<?php

namespace Secomapp\Traits;


use Secomapp\Models\Shop;

trait BelongsToShop
{
    /**
     * Get shop.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Scope by plan id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  int $shopId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}