<?php
namespace Secomapp\Events;


use Secomapp\Models\Shop;

class AppShopRedact
{
    private $shop;

    /**
     * AppRedact constructor.
     * @param Shop $shop
     */
    public function __construct($shop)
    {
        $this->shop = $shop;
    }

    /**
     * @return mixed
     */
    public function getShop()
    {
        return $this->shop;
    }
}