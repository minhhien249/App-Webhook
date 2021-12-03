<?php

namespace Secomapp\Models;

use Secomapp\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class Setting extends Model
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
        'shop_id',
        'key',
        'value',
    ];
}
