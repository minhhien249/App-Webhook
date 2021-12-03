<?php

namespace Secomapp\Jobs;

use Secomapp\Traits\ShopRedactTrait;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Secomapp\Events\AppCustomersRedact;
use Secomapp\Models\Shop;

class WebhookGdprJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, ShopRedactTrait;

    /** @var  string */
    private $shopName;

    /** @var  string */
    private $action;

    /**
     * @var Object
     */
    private $content;

    /**
     * Create a new job instance.
     *
     * @param string $shopName
     * @param string $action
     * @param Object $content
     */
    public function __construct($shopName, $action, $content)
    {
        $this->shopName = $shopName;
        $this->action = $action;
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->action) {
            case 'customersReact' :
                $this->customersReactWebhook();
                break;
            case 'shopRedact' :
                $this->shopReactWebhook();
                break;
        }
    }

    public function customersReactWebhook()
    {
        $shopName = $this->shopName;
        try {
            $shop = $this->getShop($shopName);
            if (!$shop) {
                return;
            }

            event(new AppCustomersRedact($shop, $this->content));

            Log::info("{$shopName}: customers redact webhook done");
        } catch (Exception $e) {
            Log::error("{$shopName}: customers redact webhook failed: {$e->getMessage()} {$e->getTraceAsString()}");
            Log::error("{$shopName}: request " . json_encode($this->content));
        }
    }

    public function shopReactWebhook()
    {
        $shopName = $this->shopName;
        try {
            $shop = $this->getShop($shopName);
            if (!$shop) {
                return;
            }
            if (!$shop->uninstalled()) {
                Log::info("{$shopName}: shop already reinstalled");

                return;
            }
            if ($shop->cleaned()) {
                Log::info("{$shopName}: shop already cleaned");

                return;
            }

            $this->redact($shop);

            Log::info("{$shopName}: shop react webhook done");
        } catch (Exception $e) {
            Log::error("{$shopName}: shop react webhook failed: {$e->getMessage()} {$e->getTraceAsString()}");
            Log::error("{$shopName}: request " . json_encode($this->content));
        }
    }

    /**
     * @param string $shopName
     * @return Shop | bool
     */
    private function getShop($shopName)
    {
        $domain = domainFromShopName($shopName);
        $shop = Shop::where('shop', $domain)->first(['id', 'shop', 'access_token', 'uninstalled_at', 'cleaned_at']);

        if (!$shop) {
            Log::warning("webhook can not find shop {$domain} content " . json_encode($this->content));

            return false;
        }

        return $shop;
    }

    public function tags()
    {
        return ['webhook-gdpr', $this->action];
    }
}
