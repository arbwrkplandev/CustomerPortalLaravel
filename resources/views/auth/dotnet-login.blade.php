<x-layouts.auth>
    @php
        $authPopup = session('auth_popup');
        if (!$authPopup && $errors->any()) {
            $authPopup = [
                'type' => 'error',
                'title' => 'Sign-in failed',
                'message' => $errors->first(),
            ];
        }
    @endphp

    @if($authPopup)
        <div
            x-data="{ open: true }"
            x-init="setTimeout(() => open = false, 5200)"
            x-show="open"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 -translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
            class="fixed top-6 left-1/2 z-50 w-[min(92vw,30rem)] -translate-x-1/2"
            x-cloak
        >
            <div class="rounded-2xl border px-5 py-4 shadow-2xl backdrop-blur-xl"
                 style="background: rgba(15,23,42,0.88); border-color: {{ match($authPopup['type'] ?? 'info') { 'error' => 'rgba(248,113,113,0.45)', 'warning' => 'rgba(251,191,36,0.45)', 'info' => 'rgba(96,165,250,0.45)', default => 'rgba(96,165,250,0.45)' } }}; box-shadow: 0 30px 80px rgba(15,23,42,0.45);">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                         style="background: {{ match($authPopup['type'] ?? 'info') { 'error' => 'linear-gradient(135deg, rgba(239,68,68,0.28), rgba(248,113,113,0.18))', 'warning' => 'linear-gradient(135deg, rgba(245,158,11,0.28), rgba(251,191,36,0.18))', 'info' => 'linear-gradient(135deg, rgba(59,130,246,0.28), rgba(96,165,250,0.18))', default => 'linear-gradient(135deg, rgba(59,130,246,0.28), rgba(96,165,250,0.18))' } }};">
                        <svg class="h-5 w-5" style="color: {{ match($authPopup['type'] ?? 'info') { 'error' => '#fca5a5', 'warning' => '#fcd34d', 'info' => '#93c5fd', default => '#93c5fd' } }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M10.29 3.86l-7.15 12.4A2 2 0 004.86 19h14.28a2 2 0 001.72-3.01l-7.15-12.4a2 2 0 00-3.46 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-black uppercase tracking-[0.18em]" style="color: rgba(199,210,254,0.72)">{{ $authPopup['title'] ?? 'Notice' }}</div>
                        <p class="mt-1 text-sm leading-6 text-white">{{ $authPopup['message'] ?? 'Please try again.' }}</p>
                    </div>
                    <button type="button" @click="open = false" class="rounded-full p-2 text-slate-300 transition hover:bg-white/10 hover:text-white" aria-label="Close notification">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="card animate-fadeInUp" style="background: rgba(30,27,75,0.8); backdrop-filter: blur(20px); border: 1px solid rgba(16,185,129,0.3)">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4 text-white font-black text-3xl shadow-lg"
                 style="background: linear-gradient(135deg, #059669, #10b981)">W</div>
            <h1 class="text-3xl font-black text-white">WrkPlan ERP</h1>
            <p class="text-sm mt-1" style="color: rgba(167,243,208,0.8)">Sign in with your ERP account</p>
        </div>

        <form method="POST" action="{{ route('auth.dotnet.login.post') }}" class="space-y-5">
            @csrf
            <div>
                <label class="form-label" style="color: rgba(167,243,208,0.8)">Corp ID</label>
                <input type="text" name="corp_id" value="{{ old('corp_id') }}"
                       class="form-input"
                       style="background: rgba(255,255,255,0.05); border-color: rgba(16,185,129,0.4); color: white"
                       placeholder="e.g. 2">
                <p class="text-xs mt-1" style="color: rgba(167,243,208,0.5)">Your company ID in WrkPlan ERP.</p>
            </div>
            <div>
                <label class="form-label" style="color: rgba(167,243,208,0.8)">Login ID</label>
                <input type="text" name="login_id" value="{{ old('login_id') }}" required
                       class="form-input"
                       style="background: rgba(255,255,255,0.05); border-color: {{ $errors->has('login_id') ? 'rgba(248,113,113,0.8)' : 'rgba(16,185,129,0.4)' }}; color: white"
                       placeholder="wrkplan1">
            </div>
            <div>
                <label class="form-label" style="color: rgba(167,243,208,0.8)">Password</label>
                <div x-data="{ showPassword: false }" class="relative">
                    <input :type="showPassword ? 'text' : 'password'" name="password" required
                           class="form-input pr-12"
                           style="background: rgba(255,255,255,0.05); border-color: {{ $errors->has('password') ? 'rgba(248,113,113,0.8)' : 'rgba(16,185,129,0.4)' }}; color: white"
                           placeholder="••••••••">
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg transition hover:bg-white/10"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            style="color: rgba(167,243,208,0.72)">
                        <svg x-show="!showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 012.104-3.368m2.19-1.997A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a2.99 2.99 0 00-2.12.879M12 9l-8 8m8-8l8 8"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded">
                    <span class="text-sm" style="color: rgba(167,243,208,0.7)">Keep me signed in</span>
                </label>
            </div>
            <button type="submit" class="btn w-full justify-center py-3 text-base mt-2 font-bold"
                    style="background: linear-gradient(135deg, #059669, #10b981); color: white; border: none;">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Sign In via WrkPlan ERP
            </button>
        </form>

        <div class="mt-6 pt-6 text-center" style="border-top: 1px solid rgba(16,185,129,0.2)">
            <a href="{{ route('auth.login') }}" class="text-xs hover:underline" style="color: rgba(167,243,208,0.55)">
                Use standard portal login instead
            </a>
        </div>
    </div>
</x-layouts.auth>
