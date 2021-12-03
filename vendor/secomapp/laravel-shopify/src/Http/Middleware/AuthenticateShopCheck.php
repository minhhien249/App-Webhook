<?php

namespace Secomapp\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Secomapp\Traits\InstalledShop;

class AuthenticateShopCheck
{
    use InstalledShop;

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!auth()->check()) {
            Log::warning('unauthenticated request: ' . json_encode($request->all()));

            if ($request->ajax() || $request->wantsJson()) {
                return response('You have not logged in or your session was expired, please login from your app admin', 419);
            } else {
                if (!$request->get('shop')) {
                    return response()->message('You have not logged in or your session was expired, please login from your app admin');
                }

                return redirect()->route('add_app_post')->withInput();
            }
        }

        return $next($request);
    }
}
