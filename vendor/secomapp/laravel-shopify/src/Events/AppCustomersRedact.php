<?php
namespace Secomapp\Events;


use Secomapp\Models\Shop;
use stdClass;

class AppCustomersRedact
{
    private $shop;

    /** @var stdClass */
    private $content;

    /**
     * AppRedact constructor.
     * @param Shop $shop
     * @param stdClass $content
     */
    public function __construct($shop, $content)
    {
        $this->shop = $shop;
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * @return stdClass
     */
    public function getContent()
    {
        return $this->content;
    }
}