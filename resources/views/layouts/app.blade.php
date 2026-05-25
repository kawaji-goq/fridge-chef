<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#fff1f2">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'fridge-chef' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh bg-rose-50 text-gray-900 antialiased">
    <main class="pb-16">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
