<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset("images/favicon.ico") }}">
    <link rel="stylesheet" href="{{ asset("css/app.css") }}">
</head>
<body>
<div id="app"></div>
<script type="text/javascript">
	var app = {
		name: "{{ config('app.name') }}",
		shop: "https://{{ $user->shop_name }}.myshopify.com",
		base: "{{ config('app.url') }}",
        logo: "https://www.secomapp.com/wp-content/uploads/2017/10/logo.png",
        link: "https://www.secomapp.com",
		owner: {
			fullname: "{{ $user->name }}",
			domain: "{{ $user->shop_name }}"
        }
	}
</script>
<script type="text/javascript" src="{{ asset('js/app.js') }}"></script>
</body>
</html>