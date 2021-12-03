<?php

namespace Secomapp\Traits;


use Secomapp\Models\Plan;

trait BelongsToPlan
{
    /**
     * Get plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope by plan id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  int $planId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    function scopeByPlan($query, $planId)
    {
        return $query->where('plan_id', $planId);
    }
}