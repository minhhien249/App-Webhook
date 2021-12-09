<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Secomapp\Resources\Webhook;
use Secomapp\Events\AppInstalled;


class OrderListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AppInstalled  $event
     * @return void
     */
    public function handle(AppInstalled $event)
    {
        $shop = $event->getShop();
        // đăng ký webhook order create.
        $webHook = app(Webhook::class);
        $url = "https://fa13-2405-4802-28a-f4e0-8cd7-a125-4e54-af6e.ngrok.io/api/save-order?shop_id=".$shop->id;
        $webHooks = $webHook->create("orders/create", $url, $format="json");

    }
}