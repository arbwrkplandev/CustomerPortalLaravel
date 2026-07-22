@extends('layouts.app')
@section('title', 'Agreement Review')
@section('portal-name', 'Customer Portal')

@section('sidebar-nav')
    @include('customer._sidebar')
@endsection

@push('styles')
<style>
.tagged-shell {
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 18px;
    background: linear-gradient(145deg, rgba(37, 99, 235, 0.08), rgba(14, 165, 233, 0.05));
    padding: 1rem;
}
.tagged-doc {
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: var(--color-surface);
    min-height: 420px;
    max-height: 70vh;
    overflow-y: auto;
    padding: 1.25rem;
}
.signature-pad-wrap {
    border: 1px solid rgba(56, 189, 248, 0.35);
    border-radius: 14px;
    background: #ffffff;
    overflow: hidden;
}
.signature-pad {
    width: 100%;
    height: 190px;
    display: block;
    touch-action: none;
    cursor: crosshair;
}
</style>
@endpush

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center justify-between mb-6 gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('customer.contracts') }}" class="btn btn-outline">← Back</a>
            <div>
                <h1 class="text-2xl font-black" style="color: var(--color-text-primary)">{{ $record->agreement_type ?: 'Agreement' }}</h1>
                <p class="text-sm mt-1" style="color: var(--color-text-secondary)">
                    Sent {{ !empty($record->sent_at) ? \Carbon\Carbon::parse($record->sent_at)->format('M d, Y h:i A') : '—' }}
                </p>
            </div>
        </div>
        @if(!empty($record->customer_acknowledged_at))
            <span class="badge badge-success">Acknowledged</span>
        @else
            <span class="badge badge-warning">Awaiting I Agree</span>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 rounded-xl" style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-4 rounded-xl" style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444">
            <ul class="list-disc list-inside space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tagged-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="text-sm" style="color: var(--color-text-secondary)">
                Financial Year: <strong style="color: var(--color-text-primary)">{{ $record->fin_year_code ?: '—' }}</strong>
            </div>
            <div class="text-sm" style="color: var(--color-text-secondary)">
                Remarks: <strong style="color: var(--color-text-primary)">{{ $record->remarks ?: '—' }}</strong>
            </div>
        </div>

        <div class="tagged-doc contract-prose">
            {!! $record->content !!}
        </div>

        <div class="mt-5 flex flex-wrap gap-3 justify-end">
            @if(empty($record->customer_acknowledged_at))
                <form method="POST" action="{{ route('customer.contracts.tagged.agree', $record->id) }}" class="w-full">
                    @csrf
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">
                        <div>
                            <label class="form-label">Signer Name</label>
                            <input type="text" name="signer_name" class="form-input" required value="{{ old('signer_name', auth()->user()->name) }}" placeholder="Your full name">
                        </div>
                        <div class="xl:col-span-2">
                            <label class="form-label">Draw Signature</label>
                            <div class="signature-pad-wrap">
                                <canvas id="signaturePad" class="signature-pad"></canvas>
                            </div>
                            <input type="hidden" name="signature_image" id="signatureImageInput">
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs" style="color: var(--color-text-secondary)">Use mouse or touch to sign inside the box.</span>
                                <button type="button" id="clearSignature" class="text-xs font-semibold" style="color: #0ea5e9">Clear</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        <button type="submit" class="btn btn-primary" id="submitAgreementBtn">I Agree & Sign</button>
                    </div>
                </form>
            @else
                <div class="w-full space-y-3">
                    <div class="text-sm" style="color: var(--color-text-secondary)">
                        Signed by <strong style="color: var(--color-text-primary)">{{ $record->signature_signer_name ?? 'Customer' }}</strong>
                        on <strong style="color: var(--color-text-primary)">{{ !empty($record->signature_signed_at) ? \Carbon\Carbon::parse($record->signature_signed_at)->format('M d, Y h:i A') : '—' }}</strong>
                    </div>
                    <div>
                        <img src="{{ route('customer.contracts.tagged.signature', $record->id) }}" alt="Customer signature" style="max-height: 120px; background: white; border: 1px solid var(--color-border); border-radius: 10px; padding: 8px;">
                    </div>
                </div>
            @endif
        </div>
    </div>

    <p class="text-xs mt-3" style="color: var(--color-text-secondary)">Agreement signature artifact is persisted for this customer agreement record.</p>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const canvas = document.getElementById('signaturePad');
    const hiddenInput = document.getElementById('signatureImageInput');
    const clearButton = document.getElementById('clearSignature');
    const form = hiddenInput?.closest('form');

    if (!canvas || !hiddenInput || !clearButton || !form) {
        return;
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }

    let drawing = false;
    let hasStroke = false;

    const resizeCanvas = () => {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;

        const snapshot = hasStroke ? canvas.toDataURL('image/png') : null;

        canvas.width = Math.floor(width * ratio);
        canvas.height = Math.floor(height * ratio);
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.lineWidth = 2.2;
        ctx.strokeStyle = '#0f172a';

        if (snapshot) {
            const img = new Image();
            img.onload = () => {
                ctx.drawImage(img, 0, 0, width, height);
            };
            img.src = snapshot;
        }
    };

    const pointFromEvent = (event) => {
        const rect = canvas.getBoundingClientRect();
        return {
            x: event.clientX - rect.left,
            y: event.clientY - rect.top,
        };
    };

    const start = (event) => {
        drawing = true;
        const point = pointFromEvent(event);
        ctx.beginPath();
        ctx.moveTo(point.x, point.y);
        event.preventDefault();
    };

    const move = (event) => {
        if (!drawing) {
            return;
        }

        const point = pointFromEvent(event);
        ctx.lineTo(point.x, point.y);
        ctx.stroke();
        hasStroke = true;
        event.preventDefault();
    };

    const end = (event) => {
        if (!drawing) {
            return;
        }

        drawing = false;
        ctx.closePath();
        event.preventDefault();
    };

    clearButton.addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasStroke = false;
        hiddenInput.value = '';
    });

    canvas.addEventListener('pointerdown', start);
    canvas.addEventListener('pointermove', move);
    canvas.addEventListener('pointerup', end);
    canvas.addEventListener('pointerleave', end);

    form.addEventListener('submit', (event) => {
        if (!hasStroke) {
            event.preventDefault();
            alert('Please draw your signature before submitting.');
            return;
        }

        hiddenInput.value = canvas.toDataURL('image/png');
    });

    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();
})();
</script>
@endpush
