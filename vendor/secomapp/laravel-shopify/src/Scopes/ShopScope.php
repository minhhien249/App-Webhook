<?php

namespace Secomapp\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Secomapp\Traits\InstalledShop;

class ShopScope implements Scope
{
    use InstalledShop;

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder $builder
     * @param  Model   $model
     * @return Builder
     */
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check()) {
            return $builder->where('shop_id', '=', $this->shopId());
        } else {
            return $builder;
        }
    }
}