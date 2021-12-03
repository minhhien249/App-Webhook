<?php

namespace Secomapp\Providers;


use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('homepage', function () {
            return redirect("/");
        });
        Response::macro('message', function ($message) {
            return redirect("message")->with(['message' => $message]);
        });
    }

}
