<?php

namespace Secomapp\Models;

use Secomapp\Scopes\ShopScope;
use Secomapp\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class ShopInfo extends Model
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
        'id',
        'shop_id',
        'shopify_id',
        'address1',
        'address2',
        'city',
        'country',
        'country_code',
        'country_name',
        'created_at',
        'updated_at',
        'customer_email',
        'currency',
        'domain',
        'email',
        'google_apps_domain',
        'google_apps_login_enabled',
        'latitude',
        'longitude',
        'money_format',
        'money_in_emails_format', //
        'money_with_currency_format',
        'money_with_currency_in_emails_format', //
        'myshopify_domain',
        'eligible_for_payments',
        'name',
        'plan_name',
        'plan_display_name',
        'password_enabled',
        'phone',
        'primary_locale',
        'primary_location_id', //
        'province',
        'province_code',
        'shop_owner',
        'source',
        'force_ssl',
        'tax_shipping',
        'taxes_included',
        'county_taxes',
        'requires_extra_payments_agreement',
        'timezone',
        'iana_timezone',
        'zip',
        'has_storefront',
        'setup_required',
        'has_discounts',
        'has_gift_cards',
        'eligible_for_card_reader_giveaway',
        'finances',
        'weight_unit',
        'description',
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
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ShopScope);
    }
}
