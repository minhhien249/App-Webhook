<?php

namespace Secomapp\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function boot()
    {
        View::composer(
            '*', 'Secomapp\Http\Composers\CurrentUserComposer'
        );
        if (config('setting.view_composer')) {
            View::composer(
                '*', 'Secomapp\Http\Composers\HasChangeComposer'
            );
        }
        // Using class based composers...
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}