<?php

namespace Secomapp\Models;

use Carbon\Carbon;
use Secomapp\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class Charge extends Model
{
    use BelongsToShop;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'charge_id',
        'shop_id',
        'name',
        'price',
        'trial_days',
        'type',
        'status',
        'billing_on',
        'created_at',
        'updated_at',
        'trial_ends_on',
        'cancelled_on',
        'test',
        'description',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'billing_on', 'trial_ends_on', 'cancelled_on',
    ];

    public function canceled()
    {
        return !is_null($this->cancelled_on);
    }

    public function cancel()
    {
        $this->cancelled_on = Carbon::now();
        $this->save();
    }

    public static function createFrom($shopId, $chargeType, $appCharge)
    {
        return Charge::create([
            'charge_id'     => $appCharge->id,
            'shop_id'       => $shopId,
            'name'          => $appCharge->name,
            'price'         => $appCharge->price,
            'type'          => $chargeType,
            'status'        => $appCharge->status,
            'created_at'    => strtotime($appCharge->created_at),
            'updated_at'    => strtotime($appCharge->updated_at),
            'test'          => $appCharge->test != null,
            'billing_on'    => isset($appCharge->billing_on) ? strtotime($appCharge->billing_on) : null,
            'trial_ends_on' => isset($appCharge->trial_ends_on) ? strtotime($appCharge->trial_ends_on) : null,
            'cancelled_on'  => isset($appCharge->cancelled_on) ? strtotime($appCharge->cancelled_on) : null,
            'trial_days'    => isset($appCharge->trial_days) ? $appCharge->trial_days : null,
            'description'   => json_encode($appCharge),
        ]);
    }
}
