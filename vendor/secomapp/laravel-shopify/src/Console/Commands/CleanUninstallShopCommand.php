<?php

namespace Secomapp\Console\Commands;

use Carbon\Carbon;
use Secomapp\Traits\ShopRedactTrait;
use Illuminate\Console\Command;
use Secomapp\Models\Shop;

class CleanUninstallShopCommand extends Command
{
    use ShopRedactTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean {--shop=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean uninstalled shop: remove customers, orders';

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

        $lastDay = Carbon::today()->subDays(30);
        $count = 0;
        if ($all) {
            $shops = Shop::whereNull('access_token')
                ->where('uninstalled_at', '<', $lastDay)->where(function ($query) {
                    $query->whereNull('cleaned_at')->orWhere('cleaned_at', '<', 'installed_at');
                })->get(['id', 'shop']);
            foreach ($shops as $shop) {
                $this->clean($shop);
                $count++;
            }
        } else {
            $shop = Shop::where('shop', domainFromShopName($shopName))
                ->whereNull('access_token')->where('uninstalled_at', '<', $lastDay)->where(function ($query) {
                    $query->whereNull('cleaned_at')->orWhere('cleaned_at', '<', 'installed_at');
                })->first(['id', 'shop']);
            if (!$shop) {
                $this->line("shop {$shopName} not eligible to clean");

                return;
            }

            $this->clean($shop);
            $count++;
        }

        $this->line("Clean {$count} shops done.");
    }

    /**
     * @param Shop $shop
     */
    protected function clean($shop)
    {
        $this->line("Cleaning shop {$shop->shop}");

        $this->redact($shop);
    }
}
