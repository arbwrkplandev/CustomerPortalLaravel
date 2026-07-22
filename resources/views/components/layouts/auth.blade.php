<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} | WrkPlan</title>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased flex items-center justify-center min-h-screen px-4 py-10 lg:px-8"
     style="background: radial-gradient(circle at top left, rgba(227, 190, 141, 0.28), transparent 32%), radial-gradient(circle at bottom right, rgba(157, 177, 142, 0.24), transparent 30%), linear-gradient(145deg, #f7f1e7 0%, #fdfaf4 48%, #f2ebdf 100%)">

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
       <div class="absolute inset-0 opacity-60"
           style="background-image: linear-gradient(rgba(122, 92, 62, 0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(122, 92, 62, 0.06) 1px, transparent 1px); background-size: 72px 72px; mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.3), transparent 85%);"></div>
       <div class="absolute h-[26rem] w-[26rem] rounded-full opacity-35 animate-pulse-soft"
           style="background: radial-gradient(circle, rgba(196, 148, 89, 0.4), transparent 68%); top: -120px; left: -100px; filter: blur(40px)"></div>
       <div class="absolute h-[28rem] w-[28rem] rounded-full opacity-30 animate-pulse-soft"
           style="background: radial-gradient(circle, rgba(130, 157, 116, 0.35), transparent 68%); bottom: -140px; right: -120px; filter: blur(48px); animation-delay: 1s"></div>
       <div class="absolute h-72 w-72 rounded-full opacity-20 animate-pulse-soft"
           style="background: radial-gradient(circle, rgba(204, 166, 124, 0.3), transparent 65%); top: 18%; right: 16%; filter: blur(34px); animation-delay: 0.5s"></div>
    </div>

    <div class="relative z-10 w-full max-w-6xl">
        {{ $slot }}
    </div>
</body>
</html>
