<?php

namespace Secomapp\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class Plan extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'trial_days',
        'type',
        'status',
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
     * Boot function for using with User Events.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (!$model->type)
                $model->type = 'recurring';
        });
    }

    /**
     * Get plan features.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function features()
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * Get plan subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(PlanSubscription::class);
    }

    /**
     * Check if plan is free.
     *
     * @return boolean
     */
    public function isFree()
    {
        return ($this->price == 0.00 OR $this->price < 0.00);
    }

    /**
     * Check if plan has trial.
     *
     * @return boolean
     */
    public function hasTrial()
    {
        return (is_numeric($this->trial_days) AND $this->trial_days > 0);
    }
}
