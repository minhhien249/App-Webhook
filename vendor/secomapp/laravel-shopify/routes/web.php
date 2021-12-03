<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->namespace('Secomapp\Http\Controllers')->group(function () {

    /*
     * --------------------------------------------------------------------------
     * Installation
     * --------------------------------------------------------------------------
     */
    Route::get('add_app', 'AppController@getAddApp')
        ->name('add_app');

    Route::post('add_app', 'AppController@postAddApp')
        ->middleware(['has-domain'])
        ->name('add_app_post');

    Route::get('install_app', 'AppController@postAddApp')
        ->middleware(['has-domain'])
        ->name('install_app');

    Route::get('authorize', 'AppController@authorizeApp')
        ->middleware(['has-domain', 'shopify-request', 'has-code'])
        ->name('authorize');

    Route::get('shopify', 'AppController@loginShopify')
        ->middleware(['has-domain', 'shopify-request'])
        ->name('shopify');

    Route::get('logout', 'AppController@logout')
        ->name('logout');

    /*
     * --------------------------------------------------------------------------
     * Billing
     * --------------------------------------------------------------------------
    */

    Route::get('activate_charge', 'BillingController@activateCharge')
        ->middleware(['has-domain', 'has-chargeid'])
        ->name('activate_charge');

    Route::get('pricing', 'BillingController@getPricing')
        ->name('pricing')
        ->middleware(['auth-shop', 'active-shop']);

    Route::post('pricing', 'BillingController@postPricing')
        ->name('post_pricing')
        ->middleware(['auth-shop', 'active-shop']);

    Route::get('upgrade_plan', 'BillingController@postSwitchToPaid')
        ->name('post_upgrade_plan')
        ->middleware(['auth-shop', 'active-shop']);

    Route::get('discount/{coupon_code}', 'BillingController@getDiscount')
        ->name('offer_discount')
        ->middleware(['auth-shop', 'active-shop']);

    Route::post('discount/{coupon_code}', 'BillingController@postDiscount')
        ->name('confirm_discount')
        ->middleware(['auth-shop', 'active-shop']);

    Route::get('subscription', 'SubscriptionController@index')
        ->name('subscription')
        ->middleware(['auth-shop', 'active-shop']);

    Route::get('message', 'AppController@getMessage')
        ->name('message');
});
//
//Route::middleware(['web', 'auth-shop', 'admin-shop'])->prefix('admin')->namespace('Secomapp\Http\Controllers')->group(function () {
//    Route::get('login_as', 'AdminController@getLoginAs')
//        ->name('login_as');
//
//    Route::post('login_as', 'AdminController@postLoginAs')
//        ->name('login_as_post');
//});

Route::namespace('Secomapp\Http\Controllers')->group(function () {
    Route::post('uninstalled', 'AppController@uninstalledWebhook')
        ->middleware(['webhook-request'])
        ->name('uninstalled_webhook');
    Route::post('shop_redact', 'WebhookGdprController@getShopRedact')
        ->middleware(['webhook-request'])
        ->name('shop_redact_webhook');
    Route::post('customers_redact', 'WebhookGdprController@getCustomersRedact')
        ->name('customers_redact_webhook');
});