<?php

namespace Secomapp\Traits;

trait ViewTrait
{

    /**
     * Render view with given data for improving the first time load
     * If you use view `laravel-shopify::app`, it will create new object environment
     * `var app = {
     *      data: {
    key: value
     *      }
     * }`
     * @todo This method can be removed when supporting html-webpack-plugins in the app frontend.
     * @param array $data
     * @param string $view
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function view(array $data = [], string $view = 'laravel-shopify::app')
    {
        return view($view, [
            'data' => $data
        ]);
    }
}
