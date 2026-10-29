<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Mallow Store — Record customer orders against the product catalog and keep stock in sync.">
    <title>{{ isset($title) ? $title . ' | Mallow Store' : 'Mallow Store' }}</title>

    @vite(['resources/css/app.css', 'resources/css/billing.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    {{ $slot }}

    @livewireScripts
</body>
</html>
