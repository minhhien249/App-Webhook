# secomapp/laravel-shopify

## Version constraints

This package uses [semver versioning](https://semver.org/). You should choose the right version for your application.

|   version   | laravel | notes                                            |
|:-----------:|:-------:|--------------------------------------------------|
|    1.0.*    |   5.3   |                                                  |
|    2.0.*    |   5.5   |                                                  |
|    3.0.*    |   5.6   |                                                  |
| develop-dev | lastest | It contains all lastest features for the library |

## Requirements

Here are the requirements to run this Laravel package.

| Package | Version | Note |
| ----- | ----- | ----- |
| php | 7.0 | |
| mysql | 5.7 | |
| Laravel | 5.5.* | |

## Installation
### Partner app config

Set App URL to 
https://ca.secomapp.com/shopify
And Whitelist urls to
https://ca.secomapp.com/authorize

### Update composer.json

The package is in private bitbucket, not available via Packagist so we need add VCS repository to composer.js

```yaml
"require": {
    "secomapp/laravel-shopify": "~3.0",
},
"repositories": [
   {
       "type": "git",
       "url": "git@gitlab.com:secomapp/development/laravel-shopify.git"
   }
],
"minimum-stability": "dev",
"prefer-stable": true
```

### Update package

Run composer update to download package secomapp/laravel-shopify

```bash
composer update secomapp/laravel-shopify
```

### Providers

Add provider to config/app.php in section “providers”

```php
Secomapp\ShopifyServiceProvider::class,
```

### Assets

Publish theme assets from package using command

```bash
php artisan vendor:publish --provider="Secomapp\ShopifyServiceProvider" --force
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="migrations"
```


### Authentication

Delete existing built in auth model, migrations, Controller, seeed

app/User.php
database/migrations/2014_10_12_000000_create_users_table.php
database/migrations/2014_10_12_100000_create_password_resets_table.php
Http/Controllers/Auth
And comment/delete User in database/factories/ModelFactory.php

Update config/auth.php to use `Secomapp\Models\User::class`

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => Secomapp\Models\User::class,
    ],
]
```

### Migration

```bash
php artisan migrate
```


### Middleware
Add to `app/Http/Kernel.php` section `$middlewareGroups`

```php
\Secomapp\Http\Middleware\LoggingRoute::class
```
## Usage

#### Setting for working store

Setting is key-value store for each shop, it is persistent across session.

To get value of a given key:

```php
$value = setting('key');
```

If you provide array of key/value, it will store in the store:

```php
setting(['key' => 'value']);
```
### Subscription
### TODO
## Common Errors


### On Windows
You may have trouble in running 
```bash
composer update secomapp/laravel-shopify
```
with  `Host key verification failed. fatal: Could not read from remote repository` error.

Here's a solution.


Secondly, Add your public SSH key to GitLab. read [the instruction](https://docs.gitlab.com/ee/gitlab-basics/create-your-ssh-keys.html).

Then, Open the Terminal and redirect to the folder that contains your project folder, then use command 
```bash
git clone git@gitlab.com:secomapp/development/laravel-shopify.git
```

### Route  

Should add two middleware `auth-shop` and `active-shop` for any non-anonymous route. 
And add catch all routes bellow all routes.

>>>
You should add namespace for your routes
>>>

```php
<?php

Route::namespace('\App\Http\Controllers')->group(function() {
    Route::get('welcome', 'AppController@welcome')
        ->middleware(['auth-shop', 'active-shop'])
        ->name('welcome');    
});
Route::get('/{any}', function () {
    return view('laravel-shopify::app'); 
})->where('any', '.*')->middleware(['auth-shop', 'active-shop'])
    ->namespace('\Secomapp\Http\Controllers');
```

### View and traits

The package provides a trait and skeleton view

```php
<?php

use Illuminate\Routing\Controller;
use Secomapp\Traits\ViewTrait;

class AppController extends Controller {
    
    /** Trait uses */
    use ViewTrait;
    
    /**
     * Do stuff
     */
    public function index() {
        return $this->view([
            'key' => 'value'
        ]);
    }
}

```

### Config App

Add the Code in the file .env 

```dotenv
SHOPIFY_API_KEY=
SHOPIFY_SHARED_SECRET=
SHOPIFY_REDIRECT_URL=http://{{ url }}/authorize
SHOPIFY_PERMISSIONS=read_script_tags,write_script_tags,read_themes,write_themes
```

*Note : When editing in the .env file, clear the cache by command

```bash
php artisan cache:clear
```

```bash
php artisan config:clear
```

### Subscription

TODO
