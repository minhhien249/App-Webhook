<?php

namespace Secomapp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Secomapp\Models\Shop;
use Secomapp\Traits\ShouldClean;

/**
 * Class AutoRemoveShopJob is a job to help application to remove a shop if this shop after x days uninstalled.
 *
 * @package App\Jobs
 * @author baorv <roanvanbao@gmail.com>
 * @version 0.0.1
 */
class AutoRemoveShopJob implements ShouldQueue
{
    /**
     * Trait uses
     */
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ShouldClean;

    /**
     * Execute the job.
     * Remove defined attributes from shop with given information
     *
     * @return void
     */
    public function handle()
    {
        /** @var Shop[] $shops */
        $shops = Shop::whereNull('access_token')
            ->whereNotNull('uninstalled_at')
            ->with('info')
            ->get();
        foreach ($shops as $shop) {
            if ($this->shouldClean($shop)) {
                $shop->clean()->update();
                if (!is_null($shop->info)) {
                    $shop->info->update(config('shopify.remove_attributes'));
                }
            }
        }
    }
}
