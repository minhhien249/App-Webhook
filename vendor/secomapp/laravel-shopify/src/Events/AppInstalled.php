<?php

namespace Secomapp\Events;

use Secomapp\Models\Shop;

class AppInstalled
{
    /** @var Shop $shop */
    private $shop;

    /**
     * AppCharged constructor.
     * @param Shop $shop
     */
    public function __construct(Shop $shop)
    {
        $this->shop = $shop;
    }

    /**
     * Get shop from event
     *
     * @return Shop
     */
    public function getShop()
    {
        return $this->shop;
    }
}