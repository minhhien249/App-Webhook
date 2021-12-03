<?php

namespace Secomapp\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Secomapp\Traits\BelongsToShop;

class User extends Authenticatable
{
    use Notifiable, BelongsToShop;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'shop_id', 'shop_name', 'plan_name', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
    ];

    /**
     * Update User using latest ShopInfo
     *
     * @param ShopInfo $shopInfo
     * @return User
     */
    public function updateFromShopInfo($shopInfo)
    {
        $this->name = $shopInfo->shop_owner;
        $this->email = $shopInfo->email;
        $this->plan_name = $shopInfo->plan_name;

        return $this;
    }

    /**
     * Scope a query to only shop
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $shopId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindByShopId($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
