<?php

namespace Secomapp\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class Discount extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'value',
        'usage_limit',
        'trial_days',
        'started_at',
        'expired_at',
        'plan_id'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'started_at', 'expired_at',
    ];

    /**
     * Scope by shop domain.
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param string $domain shop domain
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindByDomain($query, $domain)
    {
        return $query->whereShop($domain);
    }
}
