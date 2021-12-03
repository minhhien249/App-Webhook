<?php

namespace Secomapp;


use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Secomapp\Contracts\AuthenticateApiContract;
use Secomapp\Contracts\ClientApiContract;
use Secomapp\Services\AuthenticateApi;
use Spatie\Activitylog\ActivitylogServiceProvider;

class ShopifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();

        $this->publishViews();

        $this->publishAssets();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerMiddleware($this->app['router']);

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();

        // register its dependencies
        $this->app->register(Providers\ResponseMacroServiceProvider::class);
        $this->app->register(Providers\ShopSettingServiceProvider::class);
        $this->app->register(Providers\ComposerServiceProvider::class);
        $this->app->register(ActivitylogServiceProvider::class);

        $this->app->bind(ClientApiContract::class, function () {
            return $this->clientApi();
        });

        $this->app->bind(AuthenticateApiContract::class, function () {
            return $this->authenticateApi();
        });
    }

    /**
     * Publish all assets like fonts and images
     */
    public function publishAssets() {
        $this->publishes([
            __DIR__ . '/../public/fonts' => public_path('fonts'),
            __DIR__ . '/../public/images' => public_path('images'),
        ]);
    }

    /**
     * Publish all configs in this package to config path
     */
    public function publishConfig()
    {
        $this->publishes([
            __DIR__.'/../config/shopify.php' => config_path('shopify.php'),
            __DIR__.'/../config/billing.php' => config_path('billing.php'),
            __DIR__.'/../config/setting.php' => config_path('setting.php'),
            __DIR__.'/../config/auth.php' => config_path('auth.php'),
        ]);
    }

    /**
     * Register all configs of this package
     */
    public function registerConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/shopify.php', 'shopify'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../config/billing.php', 'billing'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../config/setting.php', 'setting'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../config/auth.php', 'auth'
        );
    }

    /**
     * Publish views from this package to vendor
     */
    public function publishViews()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-shopify');
        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/laravel-shopify'),
        ]);
    }

    /**
     * Define the routes for the application.
     *
     * @param Router $router
     */
    public function registerMiddleware(Router $router)
    {
        $routeMiddleware = [
            'shopify-request' => Http\Middleware\VerifyShopifyRequest::class,
            'proxy-request'   => Http\Middleware\VerifyProxyRequest::class,
            'webhook-request' => Http\Middleware\VerifyWebhookRequest::class,
            'has-domain'      => Http\Middleware\RequestHasDomain::class,
            'has-code'        => Http\Middleware\RequestHasCode::class,
            'verify-state'    => Http\Middleware\VerifyStateToken::class,
            'has-chargeid'    => Http\Middleware\RequestHasChargeId::class,
            'active-shop'     => Http\Middleware\ActiveShopCheck::class,
            'auth-shop'       => Http\Middleware\AuthenticateShopCheck::class,
            'admin-shop'      => Http\Middleware\AdminShopCheck::class,
            'route-logging'   => Http\Middleware\LoggingRoute::class,
        ];

        foreach ($routeMiddleware as $key => $middleware) {
            $router->aliasMiddleware($key, $middleware);
        }
    }

    /**
     * @return ClientApiContract
     */
    protected function clientApi()
    {
        $clientApi = session('client_api');
        if (!$clientApi) {
            $clientApi = new ClientApi(config('shopify.shared_secret'));
            session(['client_api' => $clientApi]);
        }

        return $clientApi;
    }

    /**
     * @return AuthenticateApiContract
     */
    protected function authenticateApi()
    {
        $authenticateApi = session('authenticate_api');
        if (!$authenticateApi) { // session is expired
            $authenticateApi = new AuthenticateApi();
            session(['authenticate_api' => $authenticateApi]);
        }

        return $authenticateApi;
    }
}
