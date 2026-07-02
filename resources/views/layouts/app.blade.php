<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WrkPlan') | WrkPlan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>[x-cloak] { display: none !important; }</style></head>
<body class="antialiased" x-data>
    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar" :class="{ 'open': $store.sidebar.open }" id="sidebar">
            <!-- Logo -->
            <div class="flex items-center gap-3 px-6 py-6 border-b border-white/10">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-lg" 
                     style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">W</div>
                <div>
                    <div class="text-white font-bold text-lg leading-none">WrkPlan</div>
                    <div class="text-xs mt-0.5" style="color: var(--color-sidebar-text); opacity: 0.6">
                        @yield('portal-name', 'Platform')
                    </div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="pt-6 pb-4 flex-1">
                @yield('sidebar-nav')
            </nav>

            <!-- User Card -->
            <div class="mx-4 mb-4 p-4 rounded-xl" style="background: rgba(255,255,255,0.08)">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold"
                         style="background: var(--color-brand-primary)">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-white text-sm font-semibold truncate">{{ auth()->user()->name }}</div>
                        <div class="text-xs truncate" style="color: var(--color-sidebar-text); opacity:0.6">
                            {{ ucfirst(auth()->user()->role) }}
                        </div>
                    </div>
                    <a href="{{ route('auth.logout') }}" 
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="text-white/50 hover:text-white transition-colors" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </a>
                    <form id="logout-form" action="{{ route('auth.logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content flex-1">
            <!-- Top Bar -->
            <header class="flex items-center justify-between mb-8 -mt-2">
                <div class="flex items-center gap-4">
                    <!-- Mobile menu toggle -->
                    <button @click="$store.sidebar.open = !$store.sidebar.open" class="md:hidden btn-icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                        <p class="page-subtitle">@yield('page-subtitle', '')</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Theme Toggle -->
                    <button @click="$store.theme.toggle()" 
                            class="w-10 h-10 rounded-full flex items-center justify-center transition-all hover:scale-110"
                            style="background: var(--color-surface); border: 1px solid var(--color-border)">
                        <svg x-show="$store.theme.current === 'light'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="$store.theme.current === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>

                    <!-- Color Picker -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="w-10 h-10 rounded-full flex items-center justify-center transition-all hover:scale-110"
                                style="background: var(--color-surface); border: 1px solid var(--color-border)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open=false" 
                             class="absolute right-0 top-12 p-4 rounded-2xl shadow-2xl z-50"
                             style="background: var(--color-surface); border: 1px solid var(--color-border); min-width: 200px">
                            <p class="text-xs font-bold mb-3" style="color: var(--color-text-muted)">CUSTOM THEME COLOR</p>
                            <input type="color" value="#6366f1" 
                                   @change="ThemeManager.apply('custom', $event.target.value); open=false"
                                   class="w-full h-10 rounded-lg cursor-pointer border-0">
                            <div class="flex flex-wrap gap-2 mt-3">
                                @foreach(['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899'] as $color)
                                <button @click="ThemeManager.apply('custom', '{{ $color }}'); open=false"
                                        class="w-7 h-7 rounded-full hover:scale-125 transition-transform border-2 border-white shadow-md"
                                        style="background: {{ $color }}"></button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl flex items-center gap-3 animate-fadeInUp"
                     style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3)">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium" style="color: #059669">{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl flex items-center gap-3 animate-fadeInUp"
                     style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3)">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium" style="color: #dc2626">{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>
</html>
