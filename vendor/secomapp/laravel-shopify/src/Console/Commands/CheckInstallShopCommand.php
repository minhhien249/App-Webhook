<?php

namespace Secomapp\Console\Commands;

use Secomapp\ClientApi;
use Secomapp\Contracts\ClientApiContract;
use Illuminate\Console\Command;
use Secomapp\Models\Shop;

class CheckInstallShopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-install {--shop=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Installed shop to detect closed, frozen shop';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shopName = $this->option('shop');
        $all = empty($this->option('all')) ? false : true;
        if (!$all && empty($shopName)) {
            $this->line('You should provide --all or --shop= options');

            return;
        }
        if (isValidDomain($shopName)) {
            $shopName = shopNameFromDomain($shopName);
        }

        /**
         * @var ClientApiContract
         */
        $clientApi = new ClientApi();

        $count = 0;
        if ($all) {
            $shops = Shop::whereNotNull('access_token')->get(['id', 'shop', 'access_token', 'shop_inactive']);
            foreach ($shops as $shop) {
                $this->check($shop, $clientApi);
                $count++;
            }
        } else {
            $shop = Shop::where('shop', domainFromShopName($shopName))
                ->whereNotNull('access_token')->first(['id', 'shop', 'access_token', 'shop_inactive']);
            if (!$shop) {
                $this->line("shop {$shopName} not eligible to check");

                return;
            }

            $this->check($shop, $clientApi);
            $count++;
        }

        $this->line("Check {$count} shops done.");
    }

    /**
     * @param Shop              $shop
     * @param ClientApiContract $clientApi
     */
    protected function check($shop, $clientApi)
    {
        $shopName = shopNameFromDomain($shop->shop);

        $this->line("Check install for shop {$shopName}");
        $clientApi->setShopName($shopName);
        $clientApi->setAccessToken($shop->access_token);

        $shopApi = new \Secomapp\Resources\Shop($clientApi);

        try {
            $shopApi->get();
        } catch (\Exception $e) {
            $this->line("shop {$shopName} unavailable: {$e->getTraceAsString()}");

            if (!$shop->shop_inactive) {
                $shop->shop_inactive = true;
                $shop->save();
            }
            return;
        }

        if ($shop->shop_inactive) {
            $this->line("shop {$shopName} go back");
            $shop->shop_inactive = false;
            $shop->save();
        }
    }
}
