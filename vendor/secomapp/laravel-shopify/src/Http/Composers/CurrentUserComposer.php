<?php

namespace Secomapp\Http\Composers;

use Illuminate\View\View;

class CurrentUserComposer
{

    /**
     * Bind data to the view.
     *
     * @param  View $view
     * @return void
     */
    public function compose(View $view)
    {
        if (auth()->check()) {
            $view->withUser(auth()->user());
        }
    }
}