@extends('layouts.app')
@section('title', $contract->title)
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@push('styles')
<style>
.timeline-step { display: flex; align-items: flex-start; gap: 12px; position: relative; }
.timeline-step:not(:last-child)::after { content: ''; position: absolute; left: 15px; top: 32px; width: 2px; height: calc(100% + 4px); background: var(--color-border); }
.timeline-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px; z-index: 1; }
.dot-done { background: #dcfce7; color: #15803d; border: 2px solid #86efac; }
.dot-active { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; box-shadow: 0 0 0 4px rgba(99,102,241,0.2); animation: pulse-glow 2s infinite; }
.dot-future { background: var(--color-surface-2); color: var(--color-text-secondary); border: 2px solid var(--color-border); }
@keyframes pulse-glow { 0%,100% { box-shadow: 0 0 0 4px rgba(99,102,241,0.15); } 50% { box-shadow: 0 0 0 8px rgba(99,102,241,0.05); } }
.pdf-wrapper { position: relative; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.15); }
.pdf-skeleton { background: linear-gradient(90deg, var(--color-surface-2) 25%, var(--color-border) 50%, var(--color-surface-2) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 16px; }
@keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
.content-viewer { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; padding: 32px; font-size: 14px; line-height: 1.9; max-height: 600px; overflow-y: auto; }
.content-viewer h1 { font-size: 1.75em; font-weight: 800; margin: 0.7em 0 0.35em; color: var(--color-text-primary); }
.content-viewer h2 { font-size: 1.35em; font-weight: 700; margin: 0.65em 0 0.3em; color: var(--color-text-primary); border-bottom: 1px solid var(--color-border); padding-bottom: 6px; }
.content-viewer h3 { font-size: 1.1em; font-weight: 700; margin: 0.6em 0 0.25em; color: var(--color-text-primary); }
.content-viewer p { margin: 0.5em 0; color: var(--color-text-secondary); }
.content-viewer strong, .content-viewer b { font-weight: 700; color: var(--color-text-primary); }
.content-viewer em, .content-viewer i { font-style: italic; }
.content-viewer ul { list-style: disc; padding-left: 1.6em; margin: 0.5em 0; color: var(--color-text-secondary); }
.content-viewer ol { list-style: decimal; padding-left: 1.6em; margin: 0.5em 0; color: var(--color-text-secondary); }
.content-viewer li { margin: 0.2em 0; }
.content-viewer blockquote { border-left: 3px solid var(--color-brand-primary); padding-left: 16px; color: var(--color-text-secondary); margin: 12px 0; font-style: italic; }
.content-viewer a { color: var(--color-brand-primary); text-decoration: underline; }
.content-viewer .ql-align-center { text-align: center; }
.content-viewer .ql-align-right { text-align: right; }
.content-viewer .ql-align-justify { text-align: justify; }
.sig-pill { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid; }

/* Action toast */
.action-toast {
    position: fixed; top: 24px; left: 50%; transform: translateX(-50%);
    z-index: 9999; min-width: 320px; max-width: 480px;
    padding: 14px 20px; border-radius: 16px;
    display: flex; align-items: center; gap: 12px;
    font-weight: 600; font-size: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25), 0 8px 20px rgba(0,0,0,0.15);
    backdrop-filter: blur(12px);
}
.action-toast.toast-success { background: linear-gradient(135deg, rgba(16,185,129,0.97), rgba(5,150,105,0.97)); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
.action-toast.toast-warning { background: linear-gradient(135deg, rgba(245,158,11,0.97), rgba(217,119,6,0.97)); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
.action-toast.toast-error { background: linear-gradient(135deg, rgba(239,68,68,0.97), rgba(185,28,28,0.97)); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
.toast-icon { width: 32px; height: 32px; border-radius: 10px; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px; }

/* Revoke modal */
.revoke-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9998; display: flex; align-items: center; justify-content: center; padding: 16px; }
.revoke-modal { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 20px; padding: 32px; max-width: 420px; width: 100%; box-shadow: 0 40px 80px rgba(0,0,0,0.3); }

/* btn spin */
@keyframes spin { to { transform: rotate(360deg); } }
.btn-spin::before { content:''; display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; margin-right:6px; vertical-align:middle; }
</style>
@endpush

@section('content')
<div class="animate-fadeInUp" x-data="contractManager()" x-init="init()"
     x-bind:class="''"
>
    {{-- ════════ ANIMATED TOAST ════════ --}}
    <div class="action-toast"
         :class="toast.type ? 'toast-' + toast.type : ''"
         x-show="toastVisible"
         x-transition:enter="transition ease-out duration-400"
         x-transition:enter-start="opacity-0 -translate-y-8 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-6 scale-95"
         style="display:none">
        <div class="toast-icon" x-text="toastIcon"></div>
        <div class="flex-1">
            <div class="font-bold" x-text="toastTitle"></div>
            <div class="text-xs mt-0.5 opacity-80" x-text="toastSubtitle" x-show="toastSubtitle"></div>
        </div>
        <button @click="hideToast()" class="opacity-60 hover:opacity-100 text-2xl leading-none ml-2 flex-shrink-0" style="line-height:1">&times;</button>
    </div>

    {{-- ════════ REVOKE CONFIRM MODAL ════════ --}}
    <div class="revoke-modal-overlay" x-show="showRevokeModal" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showRevokeModal = false">
        <div class="revoke-modal"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0"
                     style="background: rgba(245,158,11,0.15)">⚠️</div>
                <div>
                    <div class="font-black text-lg" style="color: var(--color-text-primary)">Revoke Contract?</div>
                    <div class="text-sm" style="color: var(--color-text-secondary)">This will reset the contract back to Draft</div>
                </div>
            </div>
            <p class="text-sm mb-6 leading-relaxed" style="color: var(--color-text-secondary)">
                The customer will no longer be able to sign this contract. You can re-send it at any time once you've made updates.
                <strong style="color: var(--color-text-primary)">This action cannot be undone if the customer has already signed.</strong>
            </p>
            <div class="flex gap-3">
                <button @click="showRevokeModal = false" class="btn btn-outline flex-1">Cancel</button>
                <button @click="confirmRevoke()" :disabled="busy"
                        :class="busy ? 'btn-spin opacity-70' : ''"
                        class="btn flex-1 font-semibold"
                        style="background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff; border: none">
                    <span x-text="busy ? 'Revoking…' : '🔙 Yes, Revoke'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ════════ HEADER ════════ --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline flex-shrink-0">← Back</a>
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-black" style="color: var(--color-text-primary)">{{ $contract->title }}</h1>
                    {{-- Live status badge updates via Alpine --}}
                    <span x-show="status === 'signed'"
                          class="badge badge-success text-sm px-3 py-1"
                          x-transition>✓ Signed</span>
                    <span x-show="status === 'pending_signature' || status === 'sent'"
                          class="badge badge-warning text-sm px-3 py-1"
                          x-transition>⏳ Awaiting Signature</span>
                    <span x-show="status === 'draft'"
                          class="badge text-sm px-3 py-1"
                          style="background: var(--color-surface-2); color: var(--color-text-secondary)"
                          x-transition>Draft</span>
                    <span x-show="status === 'expired' || status === 'cancelled'"
                          class="badge badge-danger text-sm px-3 py-1"
                          x-transition x-text="status.charAt(0).toUpperCase() + status.slice(1)"></span>
                </div>
                <p class="mt-1 text-sm" style="color: var(--color-text-secondary)">
                    {{ $contract->tenant->company_name }} · {{ $contract->contract_number }}
                </p>
            </div>
        </div>
        <div class="flex gap-2 flex-wrap items-center">

            {{-- SEND button — only when draft --}}
            <button x-show="status === 'draft'"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    @click="sendContract()"
                    :disabled="busy"
                    :class="busy ? 'btn-spin opacity-70' : ''"
                    class="btn btn-primary font-semibold">
                <span x-text="busy ? 'Sending…' : '📤 Send to Customer'"></span>
            </button>

            {{-- REVOKE button — only when awaiting signature (not signed) --}}
            <button x-show="status === 'pending_signature' || status === 'sent'"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    @click="showRevokeModal = true"
                    :disabled="busy"
                    class="btn font-semibold"
                    style="background: rgba(245,158,11,0.12); color: #d97706; border: 1.5px solid rgba(245,158,11,0.35)">
                🔙 Revoke
            </button>

            @if($contract->original_pdf_path)
            <a href="{{ route('admin.contracts.download', $contract) }}" class="btn btn-outline">⬇️ Download Original</a>
            @endif
            @if($contract->signed_pdf_path)
            <a href="{{ route('admin.contracts.download', [$contract, 'signed']) }}" class="btn btn-success">⬇️ Download Signed</a>
            @endif
        </div>
    </div>

    {{-- Success flash is rendered by layouts.app --}}

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Left: Content Viewer --}}
        <div class="xl:col-span-2 space-y-4">

            {{-- Signed PDF (if signed) --}}
            @if($contract->status === 'signed' && $contract->signed_pdf_path)
            <div class="card" style="border: 1px solid rgba(16,185,129,0.3); background: linear-gradient(135deg, rgba(16,185,129,0.05), transparent)">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background: rgba(16,185,129,0.15)">✅</div>
                    <div>
                        <div class="font-bold" style="color: var(--color-text-primary)">Signed Contract</div>
                        <div class="text-xs" style="color: var(--color-text-secondary)">
                            Signed by {{ $contract->signer_name ?? $contract->tenant->contact_name }}
                            @if($contract->signed_at) on {{ $contract->signed_at->format('M d, Y') }} @endif
                        </div>
                    </div>
                </div>
                {{-- Signed PDF Viewer --}}
                <div class="pdf-wrapper" x-data="{ loaded: false }">
                    <div class="pdf-skeleton w-full" x-show="!loaded" style="height: 600px"></div>
                    <iframe
                        src="{{ route('admin.contracts.stream', [$contract, 'signed']) }}"
                        @load="loaded = true"
                        x-show="loaded"
                        x-transition:enter="transition-all duration-700 ease-out"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="w-full rounded-2xl border-0"
                        style="height: 600px">
                    </iframe>
                </div>
            </div>
            @endif

            {{-- Original Content Viewer --}}
            <div class="card">
                {{-- Tab selector if both PDF and text exist --}}
                @if($contract->original_pdf_path && $contract->html_content)
                <div class="flex gap-2 p-1 rounded-xl mb-4 w-fit" style="background: var(--color-surface-2)">
                    <button type="button" @click="activeTab = 'pdf'"
                            :class="activeTab === 'pdf' ? 'bg-indigo-500 text-white' : ''"
                            class="px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                            style="color: var(--color-text-secondary)">📄 PDF</button>
                    <button type="button" @click="activeTab = 'content'"
                            :class="activeTab === 'content' ? 'bg-indigo-500 text-white' : ''"
                            class="px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                            style="color: var(--color-text-secondary)">📝 Content</button>
                </div>
                @endif

                {{-- PDF Viewer --}}
                @if($contract->original_pdf_path)
                <div x-show="activeTab === 'pdf'" x-transition>
                    <div class="pdf-wrapper" x-data="{ loaded: false }">
                        <div class="pdf-skeleton w-full" x-show="!loaded" style="height: 650px"></div>
                        <iframe
                            src="{{ route('admin.contracts.stream', $contract) }}"
                            @load="loaded = true"
                            x-show="loaded"
                            x-transition:enter="transition-all duration-700 ease-out"
                            x-transition:enter-start="opacity-0 scale-[0.97]"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="w-full rounded-2xl border-0"
                            style="height: 650px">
                        </iframe>
                    </div>
                </div>
                @endif

                {{-- Text Content --}}
                @if($contract->html_content)
                <div x-show="activeTab === 'content'" x-transition>
                    <div class="content-viewer">{!! $contract->html_content !!}</div>
                </div>
                @endif

                {{-- No content --}}
                @if(!$contract->original_pdf_path && !$contract->html_content)
                <div class="text-center py-16">
                    <div class="text-5xl mb-3">📄</div>
                    <p class="font-semibold" style="color: var(--color-text-primary)">No content added</p>
                    <p class="text-sm mt-1" style="color: var(--color-text-secondary)">This contract has no PDF or text content yet.</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Right: Sidebar --}}
        <div class="space-y-5">

            {{-- Status Timeline — reactive to status changes --}}
            <div class="card">
                <h3 class="font-bold mb-5" style="color: var(--color-text-primary)">Status Timeline</h3>
                <div class="space-y-4">

                    {{-- Step 0: Draft Created (static) --}}
                    <div class="timeline-step">
                        <div class="timeline-dot" :class="{ 'dot-done': statusLevel > 0, 'dot-active': statusLevel === 0, 'dot-future': statusLevel < 0 }">📝</div>
                        <div class="pt-1">
                            <div class="font-semibold text-sm" style="color: var(--color-text-primary)">Draft Created</div>
                            <div class="text-xs mt-0.5" style="color: var(--color-text-secondary)">{{ $contract->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>

                    {{-- Step 1: Sent to Customer (reactive date) --}}
                    <div class="timeline-step">
                        <div class="timeline-dot" :class="{ 'dot-done': statusLevel > 1, 'dot-active': statusLevel === 1, 'dot-future': statusLevel < 1 }">📤</div>
                        <div class="pt-1">
                            <div class="font-semibold text-sm" style="color: var(--color-text-primary)">Sent to Customer</div>
                            <div class="text-xs mt-0.5" style="color: var(--color-text-secondary)"
                                 x-text="sentAtDisplay ? sentAtDisplay : (statusLevel >= 1 ? '—' : 'Pending')"></div>
                        </div>
                    </div>

                    {{-- Step 2: Signed by Customer (static) --}}
                    <div class="timeline-step">
                        <div class="timeline-dot" :class="{ 'dot-done': statusLevel > 2, 'dot-active': statusLevel === 2, 'dot-future': statusLevel < 2 }">✅</div>
                        <div class="pt-1">
                            <div class="font-semibold text-sm" style="color: var(--color-text-primary)">Signed by Customer</div>
                            <div class="text-xs mt-0.5" style="color: var(--color-text-secondary)">
                                @if($contract->signed_at){{ $contract->signed_at->format('M d, Y') }}@else
                                <span x-text="statusLevel >= 2 ? '—' : 'Pending'"></span>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Contract Details --}}
            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Details</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Number</span>
                        <span class="font-mono font-semibold text-xs" style="color: var(--color-brand-primary)">{{ $contract->contract_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Type</span>
                        <span style="color: var(--color-text-primary)">{{ ucfirst($contract->type) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Customer</span>
                        <span style="color: var(--color-text-primary)">{{ $contract->tenant->company_name }}</span>
                    </div>
                    @if($contract->start_date)
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Valid From</span>
                        <span style="color: var(--color-text-primary)">{{ $contract->start_date->format('M d, Y') }}</span>
                    </div>
                    @endif
                    @if($contract->end_date)
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Valid Until</span>
                        <span style="color: var(--color-text-primary)">{{ $contract->end_date->format('M d, Y') }}</span>
                    </div>
                    @endif
                    @if($contract->signer_name)
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Signed By</span>
                        <span style="color: var(--color-text-primary)">{{ $contract->signer_name }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Sign Fields --}}
            @if($contract->signFields->isNotEmpty())
            <div class="card">
                <h3 class="font-bold mb-3" style="color: var(--color-text-primary)">Signature Fields ({{ $contract->signFields->count() }})</h3>
                <div class="space-y-2">
                    @foreach($contract->signFields as $field)
                    <div class="flex items-center gap-3 p-3 rounded-xl" style="background: var(--color-surface-2)">
                        <span class="text-lg">
                            @if($field->field_type === 'signature') ✍️
                            @elseif($field->field_type === 'initials') 🖊
                            @elseif($field->field_type === 'date') 📅
                            @elseif($field->field_type === 'checkbox') ☑️
                            @else 📝
                            @endif
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold truncate" style="color: var(--color-text-primary)">{{ $field->label }}</div>
                            <div class="text-xs" style="color: var(--color-text-secondary)">{{ ucfirst($field->field_type) }} · {{ $field->required ? 'Required' : 'Optional' }}</div>
                        </div>
                        @if($field->value)
                        <span class="badge badge-success text-xs">Filled</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function contractManager() {
    return {
        status: '{{ $contract->status }}',
        sentAtDisplay: '{{ $contract->sent_at?->format("M d, Y") ?? "" }}',
        activeTab: '{{ $contract->original_pdf_path ? "pdf" : "content" }}',
        busy: false,
        showRevokeModal: false,
        toastVisible: false,
        toastType: '',
        toastIcon: '',
        toastTitle: '',
        toastSubtitle: '',
        _toastTimer: null,

        get statusLevel() {
            const map = { draft: 0, sent: 1, pending_signature: 1, signed: 2, expired: 2, cancelled: 0 };
            return map[this.status] ?? 0;
        },

        init() {},

        hideToast() {
            clearTimeout(this._toastTimer);
            this.toastVisible = false;
        },

        showToast(type, icon, title, subtitle = '', duration = 4500) {
            clearTimeout(this._toastTimer);
            this.toastVisible = false;
            this.toastType = type;
            this.toastIcon = icon;
            this.toastTitle = title;
            this.toastSubtitle = subtitle;
            this.$nextTick(() => {
                this.toastVisible = true;
                this._toastTimer = setTimeout(() => { this.toastVisible = false; }, duration);
            });
        },

        async sendContract() {
            if (this.busy) return;
            this.busy = true;
            try {
                const resp = await fetch('{{ route('admin.contracts.send', $contract) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json' },
                });
                const json = await resp.json().catch(() => null);
                if (resp.ok && json?.success !== false) {
                    this.status = 'pending_signature';
                    const today = new Date().toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
                    this.sentAtDisplay = today;
                    this.showToast('success', '📤', 'Contract sent!', 'Customer can now review & sign', 5000);
                } else {
                    const msg = json?.message || 'Failed to send contract.';
                    this.showToast('error', '❌', 'Error', msg);
                }
            } catch (e) {
                this.showToast('error', '❌', 'Network error', 'Please try again.');
            } finally {
                this.busy = false;
            }
        },

        async confirmRevoke() {
            if (this.busy) return;
            this.busy = true;
            this.showRevokeModal = false;
            try {
                const resp = await fetch('{{ route('admin.contracts.revoke', $contract) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json' },
                });
                const json = await resp.json().catch(() => null);
                if (resp.ok && json?.success !== false) {
                    this.status = 'draft';
                    this.sentAtDisplay = '';
                    this.showToast('warning', '🔙', 'Contract revoked', 'Status reset to Draft — you can re-send anytime', 5000);
                } else {
                    const msg = json?.message || 'Could not revoke this contract.';
                    this.showToast('error', '🔒', msg === 'Cannot revoke a contract that has already been signed by the customer.' ? 'Already signed' : 'Error', msg);
                }
            } catch (e) {
                this.showToast('error', '❌', 'Network error', 'Please try again.');
            } finally {
                this.busy = false;
            }
        },
    }
}
</script>
@endpush
