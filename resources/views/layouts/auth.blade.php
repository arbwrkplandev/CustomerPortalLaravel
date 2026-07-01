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
<body class="antialiased flex items-center justify-center min-h-screen" 
      style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%)">
    
    <!-- Animated background blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute w-96 h-96 rounded-full opacity-20 animate-pulse-soft"
             style="background: radial-gradient(circle, #6366f1, transparent); top: -100px; left: -100px; filter: blur(60px)"></div>
        <div class="absolute w-96 h-96 rounded-full opacity-15 animate-pulse-soft"
             style="background: radial-gradient(circle, #8b5cf6, transparent); bottom: -100px; right: -100px; filter: blur(60px); animation-delay: 1s"></div>
        <div class="absolute w-64 h-64 rounded-full opacity-10 animate-pulse-soft"
             style="background: radial-gradient(circle, #06b6d4, transparent); top: 50%; right: 20%; filter: blur(40px); animation-delay: 0.5s"></div>
    </div>

    <div class="relative z-10 w-full max-w-md mx-4">
        {{ $slot }}
    </div>
</body>
</html>
