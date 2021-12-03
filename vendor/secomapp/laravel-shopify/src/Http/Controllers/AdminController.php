<?php

namespace Secomapp\Http\Controllers;


use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Secomapp\Http\Requests\AddAppRequest;
use Secomapp\Traits\Authenticator;

class AdminController extends Controller
{
    use Authenticator;

    /**
     * Show login as page.
     * GET /login_as
     *
     * @return View
     */
    public function getLoginAs()
    {
        return view('laravel-shopify::admin/login_as');
    }

    /**
     * Login As with Shopify.
     * POST /login_as
     *
     * @param $request
     * @return Response
     */
    public function postLoginAs(AddAppRequest $request)
    {
        Log::info('login as');
        $domain = $request->input('shop');

        return $this->login($domain, true);
    }
}