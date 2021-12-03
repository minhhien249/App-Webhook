<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset("images/favicon.ico") }}">
    <link rel="stylesheet" href="{{ asset("css/app.css") }}">
    <title>Message | {{ config('app.name') }}</title>
</head>
<body class="Polaris-Body--MessagePage">
<section class="Polaris-EmptyLayout">
    <div class="Polaris-MessagePage">
        <div class="Polaris-Banner Polaris-Banner--statusWarning Polaris-Banner--withinPage">
    <div class="Polaris-Banner__Ribbon">
        <span class="Polaris-Icon Polaris-Icon--colorYellowDark Polaris-Icon--isColored Polaris-Icon--hasBackdrop">
            <svg class="Polaris-Icon__Svg">
                <g fill-rule="evenodd">
                    <circle fill="currentColor" cx="10" cy="10" r="9"></circle>
                    <path d="M10 0C4.486 0 0 4.486 0 10s4.486 10 10 10 10-4.486 10-10S15.514 0 10 0m0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8m0-13a1 1 0 0 0-1 1v4a1 1 0 1 0 2 0V6a1 1 0 0 0-1-1m0 8a1 1 0 1 0 0 2 1 1 0 0 0 0-2"></path>
                </g>
            </svg>
        </span>
    </div>
    <div>
        <div class="Polaris-Banner__Heading">
            <p class="Polaris-Heading">Message for your session</p>
        </div>
        <div class="Polaris-Banner__Content" id="Banner28Content">
            <ul class="Polaris-List Polaris-List--typeBullet">
                <li class="Polaris-List__Item">
                    {{ session()->has('message') ? session('message') : 'Your expiration is expired. Please login again from shopify apps to continue.' }}
                </li>
            </ul>
        </div>
    </div>
</div>
    </div>
</body>
</html>