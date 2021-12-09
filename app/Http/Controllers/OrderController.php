<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;


class OrderController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createOrder(Request $request)
    {
        $result_order = $request->all();
        $order = new Order();
        $order->order_id = $result_order["id"];
        $order->shop_id = $result_order["shop_id"];
        $order->line_item = json_encode($result_order["line_items"]);
        $order->order_total = $result_order["total_price"];
        $order->save();
        logger("Save successfully");
    }
}
