<?php


namespace Secomapp\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class Coupon extends Model
{
    protected $fillable = [
        'id',
        'code',
        'discount_id',
        'shop',
        'times_used',
        'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * @param string $shopName shop name
     * @param string $planId
     * @return boolean
     */
    public function isCouponValid($shopName, $planId)
    {
        if (!$this->status) {
            return false;
        }
        if ($this->shop && $this->shop !== $shopName && $this->shop !== "{$shopName}.myshopify.com"){
            return false;
        }
        if ($this->discount->plan_id && $this->discount->plan_id !== $planId) {
            return false;
        }
        if ($this->discount->usage_limit && $this->times_used && $this->times_used >= $this->discount->usage_limit) {
            return false;
        }
        if ($this->discount->started_at && Carbon::parse($this->discount->started_at)->isFuture()) {
            return false;
        }
        if ($this->discount->expired_at && Carbon::parse($this->discount->expired_at)->isPast()) {
            return false;
        }

        return true;
    }
}