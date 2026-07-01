@props(['title' => 'Error'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title }} | {{ config('brand.name', 'New Life Campus') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/new-life-campus-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/new-life-campus-logo.png') }}">

    @vite(['resources/css/app.css'])
</head>

<body class="min-h-full bg-white text-gray-900 antialiased">
    {{ $slot }}
</body>

</html>
