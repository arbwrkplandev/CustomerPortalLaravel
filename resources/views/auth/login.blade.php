<x-layouts.auth>
    <div class="card animate-fadeInUp" style="background: rgba(30,27,75,0.8); backdrop-filter: blur(20px); border: 1px solid rgba(99,102,241,0.3)">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4 text-white font-black text-3xl shadow-lg"
                 style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">W</div>
            <h1 class="text-3xl font-black text-white">WrkPlan</h1>
            <p class="text-sm mt-1" style="color: rgba(199,210,254,0.7)">Customer & Admin Platform</p>
        </div>

        <!-- Error -->
        @if($errors->any())
            <div class="mb-4 p-3 rounded-xl" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3)">
                <p class="text-red-400 text-sm">{{ $errors->first() }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('auth.login.post') }}" class="space-y-5">
            @csrf
            <div>
                <label class="form-label" style="color: rgba(199,210,254,0.7)">Corp ID</label>
                <input type="text" name="corp_id" value="{{ old('corp_id') }}"
                       class="form-input" style="background: rgba(255,255,255,0.05); border-color: rgba(99,102,241,0.4); color: white"
                       placeholder="ACME-IND">
                <p class="text-xs mt-1" style="color: rgba(199,210,254,0.5)">For customer login. Admin can leave this blank.</p>
            </div>
            <div>
                <label class="form-label" style="color: rgba(199,210,254,0.7)">Username or Email</label>
                <input type="text" name="username_or_email" value="{{ old('username_or_email') }}" required
                       class="form-input" style="background: rgba(255,255,255,0.05); border-color: rgba(99,102,241,0.4); color: white"
                       placeholder="john.smith or you@company.com">
            </div>
            <div>
                <label class="form-label" style="color: rgba(199,210,254,0.7)">Password</label>
                <input type="password" name="password" required
                       class="form-input" style="background: rgba(255,255,255,0.05); border-color: rgba(99,102,241,0.4); color: white"
                       placeholder="••••••••">
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded">
                    <span class="text-sm" style="color: rgba(199,210,254,0.7)">Remember me</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary w-full justify-center py-3 text-base mt-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Sign In to WrkPlan
            </button>
        </form>

        <div class="mt-6 pt-6 text-center" style="border-top: 1px solid rgba(99,102,241,0.2)">
            <p class="text-xs" style="color: rgba(199,210,254,0.5)">
                Secure platform by WrkPlan &bull; Multi-tenant cloud solution
            </p>
        </div>
    </div>
</x-layouts.auth>
