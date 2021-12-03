<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset("images/favicon.ico") }}">
    <link rel="stylesheet" href="{{ asset("css/app.css") }}">
    <title>Installation | {{ config('app.name') }}</title>
</head>
<body>
<section class="Polaris-EmptyLayout">
    <div class="Polaris-InstallPage">
        <div class="Polaris-InstallPage--HeadingImage">
            <img src="{{ asset('images/logo_colored.png') }}" alt="Logo">
        </div>
        @if ($errors->any())
            <br>
            <div class="Polaris-Banner Polaris-Banner--statusCritical Polaris-Banner--withinPage">
                <div class="Polaris-Banner__Ribbon">
                    <span class="Polaris-Icon Polaris-Icon--colorRedDark Polaris-Icon--isColored Polaris-Icon--hasBackdrop">
                        <svg class="Polaris-Icon__Svg" viewBox="0 0 20 20" focusable="false" aria-hidden="true"><g fill-rule="evenodd"><circle fill="currentColor" cx="10" cy="10" r="9"></circle><path d="M2 10c0-1.846.635-3.543 1.688-4.897l11.209 11.209A7.954 7.954 0 0 1 10 18c-4.411 0-8-3.589-8-8m14.312 4.897L5.103 3.688A7.954 7.954 0 0 1 10 2c4.411 0 8 3.589 8 8a7.952 7.952 0 0 1-1.688 4.897M0 10c0 5.514 4.486 10 10 10s10-4.486 10-10S15.514 0 10 0 0 4.486 0 10"></path></g></svg>
                    </span>
                </div>
                <div>
                    <div class="Polaris-Banner__Heading" id="Banner14Heading">
                        <p class="Polaris-Heading">There are some errors with this information:</p>
                    </div>
                    <div class="Polaris-Banner__Content" id="Banner14Content">
                        <ul class="Polaris-List Polaris-List--typeBullet">
                            @foreach ($errors->all() as $error)
                                <li class="Polaris-List__Item">
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <br>
        @endif
        <div class="Polaris-Card">
            <div class="Polaris-Element--Align-Center Polaris-Element--PaddingBottom-2">
                <h2 class="Polaris-DisplayText Polaris-DisplayText--sizeMedium">
                    <span class="Polaris-TextStyle--variationStrong">Install application</span>
                </h2>
                <span class="Polaris-TextStyle--variationSubdued">Enter your shop to login to application</span>
            </div>
            <form action="{{ route('add_app_post') }}" method="POST">
                @csrf
                <div class="Polaris-FormLayout">
                    <div class="Polaris-FormLayout__Item">
                        <div class="">
                            <div class="Polaris-TextField">
                                <input name="shop" placeholder="Eg. yourdomain.myshopify.com" class="Polaris-TextField__Input" autofocus value="{{$shop or ''}}" required>
                                <div class="Polaris-TextField__Backdrop"></div>
                            </div>
                        </div>
                    </div>
                    <div class="Polaris-FormLayout__Item">
                        <div class="">
                            <div class="Polaris-TextField">
                                <input name="coupon_code" placeholder="Eg. DISCOUNT_HERE" class="Polaris-TextField__Input" value="{{$couponCode or ''}}">
                                <div class="Polaris-TextField__Backdrop"></div>
                            </div>
                        </div>
                    </div>
                    <div class="Polaris-FormLayout__Item">
                        <button type="submit" class="Polaris-Button Polaris-Button--primary Polaris-Button--fullWidth">
                            <span class="Polaris-Button__Content">
                                <span>Install</span>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <p class="Polaris-InstallPage--Links">
            You don't have accounts? <a href="https://www.shopify.com/" class="Polaris-Link" target="_blank">Create account</a>
        </p>
    </div>
</section>
</body>
</html>