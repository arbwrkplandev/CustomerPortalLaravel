@extends('layouts.app')
@section('title', $contract->title)
@section('portal-name', 'Customer Portal')

@section('sidebar-nav')
    @include('customer._sidebar')
@endsection

@push('styles')
<style>
/* Contract HTML prose styles */
.contract-prose { font-size: 14px; line-height: 1.9; }
.contract-prose h1 { font-size: 1.7em; font-weight: 800; margin: 0.7em 0 0.35em; color: var(--color-text-primary); }
.contract-prose h2 { font-size: 1.3em; font-weight: 700; margin: 0.65em 0 0.3em; color: var(--color-text-primary); border-bottom: 1px solid var(--color-border); padding-bottom: 5px; }
.contract-prose h3 { font-size: 1.1em; font-weight: 700; margin: 0.55em 0 0.25em; color: var(--color-text-primary); }
.contract-prose p { margin: 0.5em 0; color: var(--color-text-secondary); }
.contract-prose strong, .contract-prose b { font-weight: 700; color: var(--color-text-primary); }
.contract-prose em, .contract-prose i { font-style: italic; }
.contract-prose ul { list-style: disc; padding-left: 1.6em; margin: 0.5em 0; color: var(--color-text-secondary); }
.contract-prose ol { list-style: decimal; padding-left: 1.6em; margin: 0.5em 0; color: var(--color-text-secondary); }
.contract-prose li { margin: 0.2em 0; }
.contract-prose blockquote { border-left: 3px solid var(--color-brand-primary); padding-left: 16px; color: var(--color-text-secondary); margin: 12px 0; font-style: italic; }
.contract-prose a { color: var(--color-brand-primary); text-decoration: underline; }
.contract-prose .ql-align-center { text-align: center; }
.contract-prose .ql-align-right { text-align: right; }
.contract-prose .ql-align-justify { text-align: justify; }
.sign-wizard { --step-active: var(--color-brand-primary); }
.step-indicator { display: flex; align-items: center; gap: 0; }
.step-dot { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; transition: all 0.4s; }
.step-dot.done { background: #dcfce7; color: #15803d; }
.step-dot.active { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; box-shadow: 0 0 0 6px rgba(99,102,241,0.15); }
.step-dot.pending { background: var(--color-surface-2); color: var(--color-text-secondary); border: 2px solid var(--color-border); }
.step-line { flex: 1; height: 2px; background: var(--color-border); transition: background 0.4s; }
.step-line.done { background: #86efac; }
.sig-canvas-wrap { position: relative; border: 2px solid var(--color-brand-primary); border-radius: 14px; overflow: hidden; background: white; cursor: crosshair; transition: border-color 0.2s; }
.sig-canvas-wrap canvas { display: block; width: 100%; touch-action: none; }
.color-swatch { width: 28px; height: 28px; border-radius: 50%; cursor: pointer; border: 3px solid transparent; transition: all 0.2s; flex-shrink: 0; }
.color-swatch:hover { transform: scale(1.15); }
.color-swatch.selected { border-color: var(--color-brand-primary); box-shadow: 0 0 0 2px var(--color-surface), 0 0 0 4px var(--color-brand-primary); }
.pdf-wrapper { position: relative; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.12); }
.pdf-skeleton { background: linear-gradient(90deg, var(--color-surface-2) 25%, var(--color-border) 50%, var(--color-surface-2) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
@keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
.tab-pill { padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.25s; }
.tab-pill.active { background: var(--color-brand-primary); color: white; box-shadow: 0 4px 12px rgba(99,102,241,0.35); }
.signed-banner { background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(5,150,105,0.05)); border: 1px solid rgba(16,185,129,0.3); border-radius: 16px; }
.confetti-pop { animation: confettiPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes confettiPop { from { transform: scale(0.5) translateY(20px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
</style>
@endpush

@section('content')
<div class="animate-fadeInUp">
    {{-- Success Banner --}}
    @if(session('signed') || session('success'))
    <div class="mb-6 p-5 rounded-2xl confetti-pop signed-banner flex items-center gap-4">
        <div class="text-4xl">🎉</div>
        <div>
            <div class="font-bold text-lg" style="color: #065f46">Contract Signed Successfully!</div>
            <div class="text-sm mt-0.5" style="color: #047857">
                {{ session('success') ?: 'Your signed copy has been generated. You can download it below.' }}
            </div>
        </div>
    </div>
    @endif

    {{-- Header --}}
    <div class="flex items-start justify-between mb-6 gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.contracts') }}" class="btn btn-outline flex-shrink-0">← Back</a>
            <div>
                <h1 class="text-2xl font-black" style="color: var(--color-text-primary)">{{ $contract->title }}</h1>
                <div class="flex items-center gap-3 mt-1 flex-wrap">
                    @if($contract->status === 'signed')
                        <span class="badge badge-success">✓ Signed</span>
                    @elseif(in_array($contract->status, ['sent','pending_signature']))
                        <span class="badge badge-warning">⏳ Awaiting Your Signature</span>
                    @elseif($contract->status === 'draft')
                        <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Draft</span>
                    @endif
                    @if($contract->sent_at)
                    <span class="text-xs" style="color: var(--color-text-secondary)">Sent {{ $contract->sent_at->format('M d, Y') }}</span>
                    @endif
                    @if($contract->signed_at)
                    <span class="text-xs" style="color: var(--color-text-secondary)">Signed {{ $contract->signed_at->format('M d, Y') }}</span>
                    @endif
                </div>
            </div>
        </div>
        @if($contract->signed_pdf_path)
        <a href="{{ route('customer.contracts.download', [$contract, 'signed']) }}" class="btn btn-success flex-shrink-0">
            ⬇️ Download Signed Copy
        </a>
        @elseif($contract->original_pdf_path)
        <a href="{{ route('customer.contracts.download', $contract) }}" class="btn btn-outline flex-shrink-0">
            ⬇️ Download Contract
        </a>
        @endif
    </div>

    @if($contract->status === 'signed')
        {{-- ═══════════ SIGNED VIEW ═══════════ --}}
        <div class="space-y-6">
            @if($contract->signed_pdf_path)
            <div class="card signed-banner">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background: rgba(16,185,129,0.15)">📄</div>
                    <div>
                        <div class="font-bold" style="color: var(--color-text-primary)">Signed Contract Document</div>
                        <div class="text-xs" style="color: var(--color-text-secondary)">Signed by {{ $contract->signer_name ?? auth()->user()->name }} on {{ $contract->signed_at?->format('F d, Y') }}</div>
                    </div>
                </div>
                <div class="pdf-wrapper" x-data="{ loaded: false }">
                    <div class="pdf-skeleton w-full rounded-2xl" x-show="!loaded" style="height: 700px"></div>
                    <iframe
                        src="{{ route('customer.contracts.stream', [$contract, 'signed']) }}"
                        @load="loaded = true"
                        x-show="loaded"
                        x-transition:enter="transition-all duration-700 ease-out"
                        x-transition:enter-start="opacity-0 scale-[0.97]"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="w-full rounded-2xl border-0"
                        style="height: 700px">
                    </iframe>
                </div>
            </div>
            @endif

            @if($contract->original_pdf_path && $contract->signed_pdf_path)
            <div class="card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-base" style="background: var(--color-surface-2)">📎</div>
                    <div class="font-bold text-sm" style="color: var(--color-text-primary)">Original Contract PDF</div>
                </div>
                <div class="pdf-wrapper" x-data="{ loaded: false }">
                    <div class="pdf-skeleton w-full rounded-2xl" x-show="!loaded" style="height: 500px"></div>
                    <iframe
                        src="{{ route('customer.contracts.stream', $contract) }}"
                        @load="loaded = true"
                        x-show="loaded"
                        x-transition:enter="transition-all duration-600"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="w-full rounded-2xl border-0"
                        style="height: 500px">
                    </iframe>
                </div>
            </div>
            @endif
        </div>

    @elseif(in_array($contract->status, ['sent', 'pending_signature']))
        {{-- ═══════════ SIGN FLOW ═══════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6" x-data="contractSigner()" x-init="init()">

            {{-- Contract Viewer (left 3 cols) --}}
            <div class="xl:col-span-3 space-y-4">
                {{-- Tab selector --}}
                <div class="flex gap-2">
                    <button @click="activeView = 'contract'" :class="activeView === 'contract' ? 'active' : ''"
                            class="tab-pill" style="color: var(--color-text-secondary)">📄 View Contract</button>
                    <button @click="activeView = 'upload'" :class="activeView === 'upload' ? 'active' : ''"
                            class="tab-pill" style="color: var(--color-text-secondary)">📤 Upload Signed</button>
                </div>

                {{-- Contract view --}}
                <div x-show="activeView === 'contract'" x-transition>
                    @if($contract->original_pdf_path)
                    <div class="pdf-wrapper" x-data="{ loaded: false }">
                        <div class="pdf-skeleton w-full rounded-2xl" x-show="!loaded" style="height: 650px"></div>
                        <iframe
                            src="{{ route('customer.contracts.stream', $contract) }}"
                            @load="loaded = true"
                            x-show="loaded"
                            x-transition:enter="transition-all duration-700 ease-out"
                            x-transition:enter-start="opacity-0 scale-[0.97]"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="w-full rounded-2xl border-0"
                            style="height: 650px">
                        </iframe>
                    </div>
                    @elseif($contract->html_content)
                    <div class="card" style="max-height: 650px; overflow-y: auto">
                        <div class="contract-prose">{!! $contract->html_content !!}</div>
                    </div>
                    @else
                    <div class="card text-center py-16">
                        <div class="text-4xl mb-3">📄</div>
                        <p style="color: var(--color-text-secondary)">No contract content to preview.</p>
                    </div>
                    @endif
                </div>

                {{-- Upload signed copy --}}
                <div x-show="activeView === 'upload'" x-transition>
                    <div class="card">
                        <h3 class="font-bold mb-2" style="color: var(--color-text-primary)">Upload Your Signed Copy</h3>
                        <p class="text-sm mb-6" style="color: var(--color-text-secondary)">
                            Download the contract, sign it manually, then upload the signed PDF here.
                        </p>
                        @if($errors->has('signed_file'))
                        <div class="mb-4 p-3 rounded-xl" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3)">
                            <p class="text-red-400 text-sm">{{ $errors->first('signed_file') }}</p>
                        </div>
                        @endif
                        <form method="POST" action="{{ route('customer.contracts.upload-signed', $contract) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div class="border-2 border-dashed rounded-2xl p-10 text-center cursor-pointer transition-all hover:border-indigo-400"
                                 style="border-color: var(--color-border)"
                                 @click="$refs.uploadInput.click()"
                                 x-data="{ file: null }"
                                 @dragover.prevent
                                 @drop.prevent="file = $event.dataTransfer.files[0]; $refs.uploadInput.files = $event.dataTransfer.files">
                                <input type="file" name="signed_file" accept=".pdf" x-ref="uploadInput" class="hidden"
                                       @change="file = $event.target.files[0]">
                                <div x-show="!file">
                                    <div class="text-5xl mb-3">📎</div>
                                    <p class="font-semibold" style="color: var(--color-text-primary)">Drag & drop signed PDF</p>
                                    <p class="text-sm mt-1" style="color: var(--color-text-secondary)">or click to browse · PDF only · Max 20MB</p>
                                </div>
                                <div x-show="file" class="flex items-center justify-center gap-3">
                                    <div class="text-4xl">✅</div>
                                    <div>
                                        <div class="font-semibold" style="color: var(--color-text-primary)" x-text="file?.name"></div>
                                        <div class="text-sm" style="color: var(--color-text-secondary)" x-text="file ? (file.size / 1024).toFixed(1) + ' KB' : ''"></div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-full justify-center">Submit Signed Copy</button>
                        </form>
                        @if($contract->original_pdf_path)
                        <div class="mt-4 text-center">
                            <a href="{{ route('customer.contracts.download', $contract) }}" class="text-sm" style="color: var(--color-brand-primary)">
                                ⬇️ Download original to sign manually
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- E-Sign Wizard (right 2 cols) --}}
            <div class="xl:col-span-2">
                <div class="card sticky top-6 sign-wizard">
                    <h3 class="font-bold text-lg mb-5" style="color: var(--color-text-primary)">E-Sign Contract</h3>

                    {{-- Step Indicators --}}
                    <div class="step-indicator mb-6">
                        <div class="step-dot" :class="step >= 1 ? (step > 1 ? 'done' : 'active') : 'pending'">
                            <span x-show="step <= 1">1</span>
                            <span x-show="step > 1">✓</span>
                        </div>
                        <div class="step-line" :class="step > 1 ? 'done' : ''"></div>
                        <div class="step-dot" :class="step >= 2 ? (step > 2 ? 'done' : 'active') : 'pending'">
                            <span x-show="step <= 2">2</span>
                            <span x-show="step > 2">✓</span>
                        </div>
                        <div class="step-line" :class="step > 2 ? 'done' : ''"></div>
                        <div class="step-dot" :class="step >= 3 ? 'active' : 'pending'">3</div>
                    </div>

                    {{-- ── Step 1: Agree ── --}}
                    <div x-show="step === 1" x-transition:enter="transition-all duration-400" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="mb-4 p-4 rounded-xl" style="background: var(--color-surface-2)">
                            <div class="text-2xl mb-2">📋</div>
                            <div class="font-semibold mb-1" style="color: var(--color-text-primary)">Step 1: Review & Agree</div>
                            <p class="text-sm" style="color: var(--color-text-secondary)">Read the contract carefully before signing. Your e-signature is legally binding.</p>
                        </div>
                        <label class="flex items-start gap-3 cursor-pointer p-4 rounded-xl transition-all" style="background: var(--color-surface-2)"
                               :style="agreed ? 'border: 1px solid rgba(99,102,241,0.4); background: rgba(99,102,241,0.06)' : ''">
                            <input type="checkbox" x-model="agreed" class="mt-0.5 rounded w-4 h-4" style="accent-color: #6366f1">
                            <span class="text-sm" style="color: var(--color-text-secondary)">
                                I have read and understand the full contract, and I agree to be legally bound by its terms.
                            </span>
                        </label>
                        <button @click="step = 2" :disabled="!agreed"
                                class="btn btn-primary w-full justify-center mt-4 text-base"
                                :class="!agreed ? 'opacity-50 cursor-not-allowed' : ''">
                            Continue to Sign →
                        </button>
                    </div>

                    {{-- ── Step 2: Fill Fields ── --}}
                    <div x-show="step === 2" x-transition:enter="transition-all duration-400" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="mb-4">
                            <div class="font-semibold mb-1" style="color: var(--color-text-primary)">Step 2: Sign Fields</div>
                            <p class="text-sm" style="color: var(--color-text-secondary)">Complete all required signature fields below.</p>
                        </div>

                        {{-- Signer Name --}}
                        <div class="mb-4">
                            <label class="form-label text-xs">Your Full Name <span class="text-red-400">*</span></label>
                            <input type="text" x-model="signerName" required class="form-input"
                                   placeholder="John Smith" style="background: var(--color-surface)">
                        </div>

                        {{-- Signature Pad --}}
                        <div class="mb-5">
                            <label class="form-label text-xs">Signature <span class="text-red-400">*</span></label>

                            {{-- Color swatches --}}
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs" style="color: var(--color-text-secondary)">Ink color:</span>
                                <template x-for="c in sigColors" :key="c">
                                    <button type="button"
                                        class="color-swatch"
                                        :class="penColor === c ? 'selected' : ''"
                                        :style="'background:' + c"
                                        :title="c === '#000000' ? 'Black (recommended)' : c"
                                        @click="setColor(c)">
                                    </button>
                                </template>
                                <span x-show="penColor === '#000000'" class="text-xs font-semibold" style="color: var(--color-text-secondary)">Recommended</span>
                            </div>

                            <div class="sig-canvas-wrap" :style="'border-color:' + penColor">
                                <canvas id="sig-canvas" height="160"></canvas>
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none" x-show="!hasSig" style="opacity: 0.35">
                                    <span class="text-sm select-none" style="color: #94a3b8">✍️ Draw your signature here…</span>
                                </div>
                            </div>
                            <div class="flex justify-between mt-2">
                                <span class="text-xs" style="color: var(--color-text-secondary)">Use mouse or touch to draw</span>
                                <button type="button" @click="clearSig()" class="text-xs font-semibold" style="color: var(--color-brand-primary)">Clear</button>
                            </div>
                        </div>

                        {{-- Extra sign fields --}}
                        @foreach($contract->signFields as $field)
                        @if(!in_array($field->field_type, ['signature', 'initials']))
                        <div class="mb-4">
                            <label class="form-label text-xs">
                                {{ $field->label }}
                                @if($field->required) <span class="text-red-400">*</span> @endif
                            </label>
                            @if($field->field_type === 'date')
                            <input type="date" name="field_{{ $field->id }}" x-model="fieldValues['{{ $field->id }}']"
                                   value="{{ now()->format('Y-m-d') }}" class="form-input" style="background: var(--color-surface)">
                            @elseif($field->field_type === 'checkbox')
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="field_{{ $field->id }}" class="rounded" style="accent-color: #6366f1">
                                <span class="text-sm" style="color: var(--color-text-secondary)">{{ $field->label }}</span>
                            </label>
                            @else
                            <input type="text" name="field_{{ $field->id }}" x-model="fieldValues['{{ $field->id }}']"
                                   class="form-input" style="background: var(--color-surface)" placeholder="{{ $field->label }}">
                            @endif
                        </div>
                        @endif
                        @endforeach

                        <div class="flex gap-2 mt-4">
                            <button @click="step = 1" class="btn btn-outline flex-1 justify-center">← Back</button>
                            <button @click="proceedToConfirm()" class="btn btn-primary flex-1 justify-center">Review →</button>
                        </div>
                    </div>

                    {{-- ── Step 3: Confirm & Submit ── --}}
                    <div x-show="step === 3" x-transition:enter="transition-all duration-400" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="font-semibold mb-4" style="color: var(--color-text-primary)">Step 3: Confirm & Submit</div>

                        {{-- Signature preview --}}
                        <div class="mb-4 p-3 rounded-xl" style="background: var(--color-surface-2)">
                            <div class="text-xs mb-2 font-semibold" style="color: var(--color-text-secondary)">YOUR SIGNATURE</div>
                            <img :src="signatureData" class="max-w-full rounded-lg border" style="max-height: 80px; border-color: var(--color-border); background: white" x-show="signatureData" alt="Signature preview">
                        </div>

                        {{-- Signer info --}}
                        <div class="mb-4 p-3 rounded-xl" style="background: var(--color-surface-2)">
                            <div class="text-xs mb-1 font-semibold" style="color: var(--color-text-secondary)">SIGNING AS</div>
                            <div class="font-semibold text-sm" style="color: var(--color-text-primary)" x-text="signerName"></div>
                            <div class="text-xs mt-0.5" style="color: var(--color-text-secondary)">{{ auth()->user()->email }}</div>
                        </div>

                        {{-- Legal notice --}}
                        <div class="p-3 rounded-xl text-xs mb-5" style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); color: var(--color-text-secondary)">
                            By clicking <strong>Submit Signature</strong>, you are electronically signing this contract. This action is legally binding.
                        </div>

                        {{-- Hidden form --}}
                        <form id="sign-form" method="POST" action="{{ route('customer.contracts.sign', $contract) }}">
                            @csrf
                            <input type="hidden" name="signature_image" id="sig_input">
                            <input type="hidden" name="signer_name" :value="signerName">
                            @foreach($contract->signFields as $field)
                            @if(!in_array($field->field_type, ['signature', 'initials']))
                            <input type="hidden" name="field_{{ $field->id }}" :value="fieldValues['{{ $field->id }}'] || ''">
                            @endif
                            @endforeach
                        </form>

                        <div class="flex gap-2">
                            <button @click="step = 2" class="btn btn-outline flex-1 justify-center">← Back</button>
                            <button @click="submitSign()" :disabled="submitting"
                                    class="btn btn-success flex-1 justify-center"
                                    :class="submitting ? 'opacity-70' : ''">
                                <span x-show="!submitting">✅ Submit Signature</span>
                                <span x-show="submitting">Submitting…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- ═══════════ DRAFT / OTHER STATUS VIEW ═══════════ --}}
        <div class="card">
            <div class="text-center py-8">
                <div class="text-5xl mb-3">⏳</div>
                <h3 class="text-lg font-semibold mb-2" style="color: var(--color-text-primary)">Contract Not Yet Available for Signing</h3>
                <p style="color: var(--color-text-secondary)">This contract is in <strong>{{ ucwords(str_replace('_', ' ', $contract->status)) }}</strong> status. You'll be notified when it's ready for your signature.</p>
            </div>
            @if($contract->original_pdf_path)
            <div class="mt-6">
                <h4 class="font-semibold mb-3" style="color: var(--color-text-primary)">Preview</h4>
                <div class="pdf-wrapper" x-data="{ loaded: false }">
                    <div class="pdf-skeleton w-full rounded-2xl" x-show="!loaded" style="height: 500px"></div>
                    <iframe src="{{ route('customer.contracts.stream', $contract) }}"
                            @load="loaded = true" x-show="loaded"
                            x-transition:enter="transition-all duration-700"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            class="w-full rounded-2xl border-0" style="height: 500px">
                    </iframe>
                </div>
            </div>
            @elseif($contract->html_content)
            <div class="mt-6 rounded-xl p-6 contract-prose" style="background: var(--color-surface-2); max-height: 500px; overflow-y: auto">{!! $contract->html_content !!}</div>
            @endif
        </div>
    @endif

</div>

@push('scripts')
<script>
function contractSigner() {
    return {
        step: 1,
        agreed: false,
        submitting: false,
        signerName: '{{ auth()->user()->name }}',
        signaturePad: null,
        signatureData: null,
        hasSig: false,
        activeView: 'contract',
        fieldValues: {},
        penColor: '#000000',
        sigColors: ['#000000', '#1e3a8a', '#14532d', '#7f1d1d', '#4c1d95'],

        init() {
            // Wait until step 2 is actually rendered before sizing the canvas
            this.$watch('step', (val) => {
                if (val === 2) {
                    this.$nextTick(() => this.initPad());
                }
            });
            window.addEventListener('resize', () => {
                if (this.step === 2) this.resizePad();
            });
        },

        initPad() {
            const canvas = document.getElementById('sig-canvas');
            if (!canvas || !window.SignaturePad) return;
            // Destroy previous instance if re-entering step 2
            if (this.signaturePad) {
                this.signaturePad.off();
                this.signaturePad = null;
            }
            this.resizePad();
            this.signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255,255,255)',
                penColor: this.penColor,
                minWidth: 1.2,
                maxWidth: 3.5,
                velocityFilterWeight: 0.7,
            });
            this.signaturePad.addEventListener('endStroke', () => {
                this.hasSig = !this.signaturePad.isEmpty();
            });
        },

        resizePad() {
            const canvas = document.getElementById('sig-canvas');
            if (!canvas) return;
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            // Use parent width — canvas must be visible at this point
            const w = canvas.parentElement ? canvas.parentElement.clientWidth : 400;
            canvas.width  = w * ratio;
            canvas.height = 160 * ratio;
            canvas.style.height = '160px';
            canvas.getContext('2d').scale(ratio, ratio);
        },

        setColor(color) {
            this.penColor = color;
            if (this.signaturePad) {
                this.signaturePad.penColor = color;
            }
        },

        clearSig() {
            this.signaturePad?.clear();
            this.hasSig = false;
            this.signatureData = null;
        },

        proceedToConfirm() {
            if (!this.signerName.trim()) {
                alert('Please enter your full name.');
                return;
            }
            if (!this.signaturePad || this.signaturePad.isEmpty()) {
                alert('Please draw your signature.');
                return;
            }
            this.signatureData = this.signaturePad.toDataURL('image/png');
            this.step = 3;
        },

        submitSign() {
            if (!this.signatureData) {
                this.proceedToConfirm();
                if (!this.signatureData) return;
            }
            document.getElementById('sig_input').value = this.signatureData;
            this.submitting = true;
            document.getElementById('sign-form').submit();
        }
    };
}
</script>
@endpush
@endsection
