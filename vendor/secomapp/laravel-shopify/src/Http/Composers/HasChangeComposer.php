<?php

namespace Secomapp\Http\Composers;

use Illuminate\View\View;

class HasChangeComposer
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
            $changes = setting('changes', 0);
            $view->withChanges($changes);
        }
    }
}