<?php

namespace Secomapp\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Secomapp\Contracts\ClientApiContract;
use Secomapp\Exceptions\ShopifyApiException;
use Secomapp\Models\Shop;
use Secomapp\Resources\Webhook;
use Secomapp\ClientApi;

class CheckWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-webhooks {--shop=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check missing webhooks';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle()
    {
        $shopName = $this->option('shop');
        $all = empty($this->option('all')) ? false : true;

        if (isValidDomain($shopName)) {
            $shopName = shopNameFromDomain($shopName);
        }
        if ($all == false && empty($shopName) == true) {
            $this->line('You should provide --all or --shop options');
            return;
        }

        /**
         * @var ClientApiContract
         */
        $clientApi = new ClientApi(config('shopify.shared_secret'), config('shopify.api_version'));

        if($all) {
            $shops = Shop::whereNotNull('access_token')->get([
                'id',
                'access_token',
                'shop'
            ]);
            foreach ($shops as $shop) {
                try {
                    $this->checkWebhooks($shop, $clientApi, false);
                } catch (ShopifyApiException $e) {
                    $this->line("shop {$shopName} {$e->getMessage()}");
                }
            }
        } else {
            /** @var Shop $shop */
            $shop = Shop::where('shop', domainFromShopName($shopName))->first();

            if ($shop->uninstalled()) {
                $this->line("shop {$shopName} uninstalled");
                return;
            }

            $this->checkWebhooks($shop, $clientApi, true);
        }
    }

    /**
     * @param  Shop  $shop
     * @param  ClientApiContract  $clientApi
     * @param  bool  $detail
     *
     * @throws ShopifyApiException
     */
    private function checkWebhooks($shop, $clientApi, $detail)
    {
        $shopName = shopNameFromDomain($shop->shop);

        $this->line("Check webhooks for shop {$shopName}");
        $clientApi->setShopName($shopName);
        $clientApi->setAccessToken($shop->access_token);

        $webhookApi = new Webhook($clientApi);

        $webhooks = collect($webhookApi->all())->keyBy('topic')->all();

        if ($detail) {
            $this->line(json_encode($webhooks, JSON_PRETTY_PRINT));
        }

        if (!isset($webhooks['app/uninstalled'])) {
            $this->line("{$shopName}: no uninstalled hook");
            logger("{$shopName}: no uninstalled hook");
            $webhookApi->create('app/uninstalled', route('uninstalled_webhook'));
        }
    }
}
