@extends('layouts.app')
@section('title', $agreement->document_title ?: 'Contract Agreement')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
.word-viewer {
    background: #fff;
    border: 1px solid #d4d7e2;
    border-radius: 12px;
    padding: 36px 44px;
    min-height: 620px;
    max-height: 74vh;
    overflow-y: auto;
    box-shadow: 0 10px 24px rgba(20, 28, 58, 0.08);
}
.word-viewer h1 { font-size: 2em; font-weight: 700; margin: 0.6em 0 0.3em; color: #1f2937; }
.word-viewer h2 { font-size: 1.55em; font-weight: 700; margin: 0.6em 0 0.3em; color: #1f2937; }
.word-viewer h3 { font-size: 1.25em; font-weight: 700; margin: 0.55em 0 0.25em; color: #1f2937; }
.word-viewer p, .word-viewer li { color: #374151; line-height: 1.9; }
.word-viewer ul, .word-viewer ol { padding-left: 1.5em; margin: 0.5em 0; }
.word-viewer table { width: 100%; border-collapse: collapse; margin: 0.7em 0; }
.word-viewer table td, .word-viewer table th { border: 1px solid #d1d5db; padding: 8px; }
.word-viewer blockquote { border-left: 3px solid #6366f1; padding-left: 16px; margin: 12px 0; color: #6b7280; }
.word-viewer .ql-align-center { text-align: center; }
.word-viewer .ql-align-right { text-align: right; }
.word-viewer .ql-align-justify { text-align: justify; }

.editor-shell { border: 1px solid var(--color-border); border-radius: 12px; overflow: hidden; }
.ql-toolbar.ql-snow { border: none !important; border-bottom: 1px solid var(--color-border) !important; background: var(--color-surface-2) !important; }
.ql-container.ql-snow { border: none !important; min-height: 360px; background: var(--color-surface); }
.ql-editor { min-height: 360px; color: var(--color-text-primary); }

@media print {
    body * { visibility: hidden !important; }
    #printable-word, #printable-word * { visibility: visible !important; }
    #printable-word {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-height: none;
        border: 0;
        box-shadow: none;
        padding: 0;
    }
}
</style>
@endpush

@section('content')
<div class="animate-fadeInUp" x-data="agreementPage()">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline flex-shrink-0">← Back</a>
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-black" style="color: var(--color-text-primary)">{{ $agreement->document_title ?: 'Untitled Agreement' }}</h1>
                </div>
                <p class="mt-1 text-sm" style="color: var(--color-text-secondary)">
                    Agreement #{{ $agreement->id }} · {{ $agreement->agreement_type ?: '—' }}
                </p>
            </div>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.contracts.download-word', $agreement->id) }}" class="btn btn-outline">Download Word</a>
            <button type="button" class="btn btn-outline" @click="printDocument()">Print</button>
            <button type="button" class="btn btn-primary" @click="toggleEdit()" x-text="isEditing ? 'View Mode' : 'Edit Document'"></button>
            <form method="POST" action="{{ route('admin.contracts.delete', $agreement->id) }}" onsubmit="return confirm('Delete this contract agreement?')">
                @csrf
                <button type="submit" class="btn btn-danger">Delete Agreement</button>
            </form>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3)">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li class="text-sm text-red-400">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-4">
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold" style="color: var(--color-text-primary)">Agreement Document</h3>
                    <span class="text-xs" style="color: var(--color-text-secondary)" x-text="isEditing ? 'Editing Mode' : 'View Mode'"></span>
                </div>

                <div x-show="!isEditing" x-transition>
                    <div id="printable-word" class="word-viewer">
                        <div>{!! $agreement->document_content !!}</div>
                    </div>
                </div>

                <div x-show="isEditing" x-transition>
                    <form method="POST" action="{{ route('admin.contracts.update', $agreement->id) }}" class="space-y-4" id="agreement-update-form">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Document Type <span class="text-red-400">*</span></label>
                                <input type="text" name="agreement_type" value="{{ old('agreement_type', $agreement->agreement_type) }}" required class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Document Title <span class="text-red-400">*</span></label>
                                <input type="text" name="document_title" value="{{ old('document_title', $agreement->document_title) }}" required class="form-input">
                            </div>
                        </div>

                        <textarea name="document_content" id="document_content_input" class="hidden">{{ old('document_content', $agreement->document_content) }}</textarea>
                        <div class="editor-shell">
                            <div id="quill-editor"></div>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="{{ route('admin.contracts.show', $agreement->id) }}" class="btn btn-outline">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Details</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Agreement ID</span>
                        <span class="font-semibold" style="color: var(--color-text-primary)">#{{ $agreement->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Company ID</span>
                        <span style="color: var(--color-text-primary)">{{ $agreement->id_company }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Type</span>
                        <span style="color: var(--color-text-primary)">{{ $agreement->agreement_type ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Compressed</span>
                        <span style="color: var(--color-text-primary)">{{ $agreement->is_compressed ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Created At</span>
                        <span style="color: var(--color-text-primary)">{{ $agreement->created_at ? $agreement->created_at->format('M d, Y') : '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Created By</span>
                        <span style="color: var(--color-text-primary)">{{ $agreement->created_by ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Updated At</span>
                        <span style="color: var(--color-text-primary)">{{ $agreement->updated_at ? $agreement->updated_at->format('M d, Y') : '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Updated By</span>
                        <span style="color: var(--color-text-primary)">{{ $agreement->updated_by ?: '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
function agreementPage() {
    return {
        isEditing: false,

        toggleEdit() {
            this.isEditing = !this.isEditing;
        },

        printDocument() {
            window.print();
        },
    };
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('agreement-update-form');
    const editorMount = document.getElementById('quill-editor');
    const hiddenInput = document.getElementById('document_content_input');

    if (!form || !editorMount || !hiddenInput || typeof Quill === 'undefined') {
        return;
    }

    const toolbarOptions = [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ align: [] }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'link'],
        ['clean'],
    ];

    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Write agreement content...',
        modules: { toolbar: toolbarOptions },
    });

    const stored = hiddenInput.value;
    if (stored && stored.trim()) {
        quill.clipboard.dangerouslyPasteHTML(stored);
    }

    form.addEventListener('submit', () => {
        hiddenInput.value = quill.root.innerHTML;
    });
});
</script>
@endpush
