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
                 style="background: rgba(249,251,246,0.96); border-color: {{ match($authPopup['type'] ?? 'info') { 'error' => 'rgba(220,107,95,0.45)', 'warning' => 'rgba(197,148,89,0.45)', 'info' => 'rgba(95,123,91,0.45)', default => 'rgba(95,123,91,0.45)' } }}; box-shadow: 0 28px 70px rgba(65, 82, 61, 0.16);">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                         style="background: {{ match($authPopup['type'] ?? 'info') { 'error' => 'linear-gradient(135deg, rgba(220,107,95,0.2), rgba(242,177,164,0.14))', 'warning' => 'linear-gradient(135deg, rgba(197,148,89,0.24), rgba(230,199,154,0.16))', 'info' => 'linear-gradient(135deg, rgba(95,123,91,0.22), rgba(173,189,160,0.14))', default => 'linear-gradient(135deg, rgba(95,123,91,0.22), rgba(173,189,160,0.14))' } }};">
                        <svg class="h-5 w-5" style="color: {{ match($authPopup['type'] ?? 'info') { 'error' => '#b4534a', 'warning' => '#9a6a37', 'info' => '#4e6a4b', default => '#4e6a4b' } }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M10.29 3.86l-7.15 12.4A2 2 0 004.86 19h14.28a2 2 0 001.72-3.01l-7.15-12.4a2 2 0 00-3.46 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-black uppercase tracking-[0.18em]" style="color: #5f7b5b">{{ $authPopup['title'] ?? 'Notice' }}</div>
                        <p class="mt-1 text-sm leading-6" style="color: #4b5563">{{ $authPopup['message'] ?? 'Please try again.' }}</p>
                    </div>
                    <button type="button" @click="open = false" class="rounded-full p-2 transition" style="color: #6f7e67" aria-label="Close notification">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-[2rem] border shadow-[0_30px_100px_rgba(65,82,61,0.14)] animate-fadeInUp lg:grid lg:grid-cols-[1.05fr_0.95fr]"
         style="background: rgba(249, 251, 246, 0.84); border-color: rgba(122, 143, 112, 0.24); backdrop-filter: blur(18px)">
        <div class="relative overflow-hidden px-7 py-8 sm:px-10 sm:py-10 lg:min-h-[43rem]"
             style="background: linear-gradient(165deg, rgba(234,240,227,0.97) 0%, rgba(224,233,216,0.95) 52%, rgba(214,225,207,0.98) 100%)">
            <div class="absolute -right-10 top-10 h-40 w-40 rounded-full"
                 style="background: radial-gradient(circle, rgba(122,143,112,0.24), transparent 72%)"></div>
            <div class="absolute -left-14 bottom-0 h-52 w-52 rounded-full"
                 style="background: radial-gradient(circle, rgba(197,148,89,0.18), transparent 72%)"></div>

            <div class="relative flex h-full flex-col justify-between gap-8">
                <div>
                    <div class="inline-flex items-center gap-3 rounded-full border px-4 py-2 text-xs font-bold uppercase tracking-[0.24em]"
                         style="border-color: rgba(95,123,91,0.18); color: #4e6a4b; background: rgba(249,251,246,0.7)">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full" style="background: #c49459"></span>
                        WrkPlan ERP Login
                    </div>

                    <div class="mt-8 inline-flex h-16 w-16 items-center justify-center rounded-[1.35rem] text-3xl font-black text-white shadow-lg"
                         style="background: linear-gradient(135deg, #5f7b5b, #8aa07f)">W</div>

                    <h1 class="mt-8 max-w-md text-4xl font-black leading-tight" style="color: #253221">Connected ERP access with a cleaner, business-first interface.</h1>
                    <p class="mt-4 max-w-lg text-sm leading-7 sm:text-base" style="color: #566553">
                        Sign in using your ERP identity and move directly into tenant-aware operational workflows with a calmer, more polished experience.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                    <div class="rounded-2xl border px-4 py-4"
                         style="border-color: rgba(95,123,91,0.12); background: rgba(247,250,244,0.76)">
                        <div class="text-xs font-bold uppercase tracking-[0.18em]" style="color: #5f7b5b">ERP Identity</div>
                        <p class="mt-2 text-sm leading-6" style="color: #55625a">Dedicated login path for synchronized ERP user accounts.</p>
                    </div>
                    <div class="rounded-2xl border px-4 py-4"
                         style="border-color: rgba(95,123,91,0.12); background: rgba(247,250,244,0.76)">
                        <div class="text-xs font-bold uppercase tracking-[0.18em]" style="color: #5f7b5b">Fast Context</div>
                        <p class="mt-2 text-sm leading-6" style="color: #55625a">Corp-aware access with clear form hierarchy and reduced noise.</p>
                    </div>
                    <div class="rounded-2xl border px-4 py-4"
                         style="border-color: rgba(95,123,91,0.12); background: rgba(247,250,244,0.76)">
                        <div class="text-xs font-bold uppercase tracking-[0.18em]" style="color: #5f7b5b">Professional Look</div>
                        <p class="mt-2 text-sm leading-6" style="color: #55625a">A softer palette that still feels controlled, premium, and dependable.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white px-7 py-8 sm:px-10 sm:py-10 lg:px-11 lg:py-11">
            <div class="max-w-md mx-auto">
                <div class="mb-8">
                    <div class="text-xs font-bold uppercase tracking-[0.24em]" style="color: #5f7b5b">ERP Access</div>
                    <h2 class="mt-3 text-3xl font-black" style="color: #253221">Sign in with your ERP account</h2>
                    <p class="mt-2 text-sm leading-6" style="color: #6b7280">Use your company ID and ERP login credentials to continue.</p>
                </div>

                <form method="POST" action="{{ route('auth.dotnet.login.post') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="form-label" style="color: #55625a">Corp ID</label>
                        <input type="text" name="corp_id" value="{{ old('corp_id') }}"
                               class="form-input"
                               style="background: #fbfdf8; border-color: rgba(122,143,112,0.34); color: #1f2937"
                               placeholder="e.g. 2">
                        <p class="text-xs mt-1" style="color: #6f7e67">Your company ID in WrkPlan ERP.</p>
                    </div>
                    <div>
                        <label class="form-label" style="color: #55625a">Login ID</label>
                        <input type="text" name="login_id" value="{{ old('login_id') }}" required
                               class="form-input"
                               style="background: #fbfdf8; border-color: {{ $errors->has('login_id') ? 'rgba(220,107,95,0.8)' : 'rgba(122,143,112,0.34)' }}; color: #1f2937"
                               placeholder="wrkplan1">
                    </div>
                    <div>
                        <label class="form-label" style="color: #55625a">Password</label>
                        <div x-data="{ showPassword: false }" class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="password" required
                                   class="form-input pr-12"
                                   style="background: #fbfdf8; border-color: {{ $errors->has('password') ? 'rgba(220,107,95,0.8)' : 'rgba(122,143,112,0.34)' }}; color: #1f2937"
                                   placeholder="••••••••">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg transition"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    style="color: #6f7e67; background: rgba(234,240,227,0.7)">
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
                            <input type="checkbox" name="remember" class="rounded" style="border-color: rgba(122,143,112,0.4)">
                            <span class="text-sm" style="color: #55625a">Keep me signed in</span>
                        </label>
                    </div>
                    <button type="submit" class="btn w-full justify-center py-3 text-base mt-2 font-bold border-0"
                            style="background: linear-gradient(135deg, #5f7b5b, #8aa07f); color: white; box-shadow: 0 18px 35px rgba(95,123,91,0.22)">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Sign In via WrkPlan ERP
                    </button>
                </form>

                <div class="mt-6 pt-6 text-center" style="border-top: 1px solid rgba(122,143,112,0.16)">
                    <a href="{{ route('auth.login') }}" class="text-xs hover:underline" style="color: #6f7e67">
                        Use standard portal login instead
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.auth>
