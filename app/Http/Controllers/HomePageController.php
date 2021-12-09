<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Secomapp\Models\Shop;
use Secomapp\Resources\Webhook;

class HomePageController extends Controller
{
    public function index() {
        return "OKE";
        $webHook = app(Webhook::class);
//         dd($webHook->all());

    }

}
