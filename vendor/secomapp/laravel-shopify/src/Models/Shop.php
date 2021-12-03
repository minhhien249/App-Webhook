<?php

namespace Secomapp\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Secomapp\Traits\PlanSubscriber;

/**
 * Class Shop represents table "shops" in database
 *
 * @mixin \Eloquent
 * @property int $id
 * @property string $shop
 * @property string $access_token
 * @property \Carbon\Carbon $installed_at
 * @property \Carbon\Carbon $activated_at
 * @property \Carbon\Carbon $cleaned_at
 * @property \Carbon\Carbon $uninstalled_at
 * @property int $used_days
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property ShopInfo $info
 */
class Shop extends Model
{
    use PlanSubscriber;

    /**
     * List of Europe countries
     */
    const EU_COUNTRIES = [
        'AT', 'BE', 'HR', 'BG', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE',
        'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop',
        'access_token',
        'installed_at',
        'activated_at', // the time that shop start using app free or paid
        'uninstalled_at',
        'cleaned_at', // the time we clean personal data for shop
        'used_days',
        'shop_inactive',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
        'installed_at', 'activated_at', 'uninstalled_at',
        'clean_at'
    ];

    /**
     * Check if shop is uninstalled
     *
     * @return bool
     */
    public function uninstalled()
    {
        return $this->access_token == null;
    }

    /**
     * Check if shop is active
     *
     * @return bool
     */
    public function active()
    {
        return !isNullTimestamp($this->activated_at) && $this->activated_at >= $this->installed_at;
    }

    /**
     * Make this shop installed
     *
     * @param $accessToken
     *
     * @return Shop
     */
    public function install($accessToken)
    {
        $this->access_token = $accessToken;
        $this->installed_at = new Carbon();

        return $this;
    }

    /**
     * Make this shop as uninstalled
     *
     * @return $this
     */
    public function uninstall()
    {
        $this->access_token = null;
        $this->uninstalled_at = Carbon::now();

        return $this;
    }

    /**
     * Make this shop as uninstalled
     *
     * @return $this
     */
    public function clean()
    {

        $this->cleaned_at = Carbon::now();

        return $this;
    }

    /**
     * Check if shop is cleaned
     *
     * @return bool
     */
    public function cleaned()
    {
        return $this->uninstalled() && $this->cleaned_at > $this->uninstalled_at;
    }

    /**
     * Activate shop
     *
     * @param $activated_at
     *
     * @return Shop
     */
    public function activate($activated_at)
    {
        $this->activated_at = $activated_at;

        return $this;
    }

    /**
     * Deactivate shop
     *
     * @return $this
     */
    public function deactivate()
    {
        $this->activated_at = null;

        // cancel subscription
        $subscription = $this->subscription();
        if ($subscription && !$subscription->canceled()) {
            $subscription->cancel()->save();

            // check if subscription is on grace period then save to settings
            if ($subscription->onGracePeriod()) {
                shopSetting($this->id, 'subscription_ends_at', $subscription->ends_at);
            }

            // update used days from the time subscription is created (not activated time) because
            // we allow to use without charge
            $usedDays = Carbon::now()->diffInDays(Carbon::instance($subscription->starts_at));
            $this->used_days += $usedDays;
        }

        return $this;
    }

    /**
     * Scope a query to only domain
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param string $domain
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindByDomain($query, $domain)
    {
        return $query->where('shop', $domain);
    }

    /**
     * Get shop info of a shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|ShopInfo
     */
    public function info()
    {
        return $this->hasOne(ShopInfo::class, 'shop_id', 'id');
    }
}
