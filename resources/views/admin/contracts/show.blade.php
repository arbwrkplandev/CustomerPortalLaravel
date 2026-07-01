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
</style>
@endpush

@section('content')
<div class="animate-fadeInUp" x-data="{ activeTab: '{{ $contract->original_pdf_path ? 'pdf' : 'content' }}', pdfLoaded: false, signedPdfLoaded: false }">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline flex-shrink-0">← Back</a>
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-black" style="color: var(--color-text-primary)">{{ $contract->title }}</h1>
                    @if($contract->status === 'signed')
                        <span class="badge badge-success text-sm px-3 py-1">✓ Signed</span>
                    @elseif(in_array($contract->status, ['sent','pending_signature']))
                        <span class="badge badge-warning text-sm px-3 py-1">⏳ Awaiting Signature</span>
                    @elseif($contract->status === 'draft')
                        <span class="badge text-sm px-3 py-1" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Draft</span>
                    @else
                        <span class="badge text-sm px-3 py-1">{{ ucfirst($contract->status) }}</span>
                    @endif
                </div>
                <p class="mt-1 text-sm" style="color: var(--color-text-secondary)">
                    {{ $contract->tenant->company_name }} · {{ $contract->contract_number }}
                </p>
            </div>
        </div>
        <div class="flex gap-2 flex-wrap">
            @if($contract->status === 'draft')
            <form method="POST" action="{{ route('admin.contracts.send', $contract) }}">
                @csrf
                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('Send this contract to the customer for signing?')">
                    📤 Send to Customer
                </button>
            </form>
            @endif
            @if($contract->original_pdf_path)
            <a href="{{ route('admin.contracts.download', $contract) }}" class="btn btn-outline">⬇️ Download Original</a>
            @endif
            @if($contract->signed_pdf_path)
            <a href="{{ route('admin.contracts.download', [$contract, 'signed']) }}" class="btn btn-success">⬇️ Download Signed</a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl animate-fadeInUp" style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3)">
        <p class="text-green-400 font-semibold">✓ {{ session('success') }}</p>
    </div>
    @endif

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

            {{-- Status Timeline --}}
            <div class="card">
                <h3 class="font-bold mb-5" style="color: var(--color-text-primary)">Status Timeline</h3>
                <div class="space-y-4">
                    @php
                        $steps = [
                            ['key' => 'draft', 'label' => 'Draft Created', 'date' => $contract->created_at->format('M d, Y'), 'icon' => '📝'],
                            ['key' => 'pending_signature', 'label' => 'Sent to Customer', 'date' => $contract->sent_at?->format('M d, Y'), 'icon' => '📤'],
                            ['key' => 'signed', 'label' => 'Signed by Customer', 'date' => $contract->signed_at?->format('M d, Y'), 'icon' => '✅'],
                        ];
                        $statusOrder = ['draft' => 0, 'sent' => 1, 'pending_signature' => 1, 'signed' => 2, 'expired' => 3, 'cancelled' => 3];
                        $currentLevel = $statusOrder[$contract->status] ?? 0;
                    @endphp
                    @foreach($steps as $i => $step)
                    <div class="timeline-step">
                        <div class="timeline-dot {{ $i < $currentLevel ? 'dot-done' : ($i === $currentLevel ? 'dot-active' : 'dot-future') }}">
                            {{ $step['icon'] }}
                        </div>
                        <div class="pt-1">
                            <div class="font-semibold text-sm" style="color: var(--color-text-primary)">{{ $step['label'] }}</div>
                            @if($step['date'])
                            <div class="text-xs mt-0.5" style="color: var(--color-text-secondary)">{{ $step['date'] }}</div>
                            @elseif($i <= $currentLevel)
                            <div class="text-xs mt-0.5" style="color: var(--color-text-secondary)">—</div>
                            @else
                            <div class="text-xs mt-0.5" style="color: var(--color-text-secondary)">Pending</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
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
