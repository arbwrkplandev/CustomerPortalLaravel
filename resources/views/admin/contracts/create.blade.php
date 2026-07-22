@extends('layouts.app')
@section('title', 'New Contract Agreement')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
.ql-toolbar.ql-snow { border: 1px solid var(--color-border) !important; border-bottom: none !important; border-radius: 12px 12px 0 0; background: var(--color-surface-2) !important; padding: 10px 12px; }
.ql-container.ql-snow { border: 1px solid var(--color-border) !important; border-radius: 0 0 12px 12px; background: var(--color-surface) !important; font-size: 14px; min-height: 380px; }
.ql-editor { min-height: 380px; padding: 20px 24px; color: var(--color-text-primary); line-height: 1.8; }
.ql-editor.ql-blank::before { color: var(--color-text-secondary); font-style: normal; opacity: 0.6; }
.ql-snow .ql-stroke { stroke: var(--color-text-secondary) !important; }
.ql-snow .ql-fill { fill: var(--color-text-secondary) !important; }
.ql-snow .ql-picker { color: var(--color-text-secondary) !important; }
.ql-snow .ql-picker-options { background: var(--color-surface) !important; border: 1px solid var(--color-border) !important; border-radius: 8px !important; box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important; }
.ql-snow .ql-picker-item { color: var(--color-text-primary) !important; }
.ql-snow.ql-toolbar button:hover .ql-stroke, .ql-snow.ql-toolbar button.ql-active .ql-stroke { stroke: var(--color-brand-primary) !important; }
.ql-snow.ql-toolbar button:hover .ql-fill, .ql-snow.ql-toolbar button.ql-active .ql-fill { fill: var(--color-brand-primary) !important; }
</style>
@endpush

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline">← Back</a>
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">New Contract Agreement</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Create an agreement template in WrkPlan ERP</p>
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

    <form method="POST" action="{{ route('admin.contracts.store') }}" class="space-y-6">
        @csrf

        <div class="card">
            <h2 class="font-bold text-lg mb-5" style="color: var(--color-text-primary)">Agreement Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Document Type <span class="text-red-400">*</span></label>
                    <input type="text" name="agreement_type" value="{{ old('agreement_type') }}" required class="form-input" placeholder="Service Agreement">
                </div>
                <div>
                    <label class="form-label">Document Title <span class="text-red-400">*</span></label>
                    <input type="text" name="document_title" value="{{ old('document_title') }}" required class="form-input" placeholder="Service Agreement 2026">
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="font-bold text-lg mb-2" style="color: var(--color-text-primary)">Document Content</h2>
            <p class="text-sm mb-5" style="color: var(--color-text-secondary)">Write the full agreement content to be stored in ERP.</p>

            <textarea name="document_content" id="document_content_input" class="hidden">{{ old('document_content') }}</textarea>
            <div id="quill-editor"></div>
            <p class="text-xs mt-3" style="color: var(--color-text-secondary)">Use headings, lists, links, and formatting as needed.</p>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="btn btn-primary text-base px-8">Create Agreement</button>
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
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
        placeholder: 'Write your agreement content here...',
        modules: { toolbar: toolbarOptions },
    });

    const stored = document.getElementById('document_content_input').value;
    if (stored && stored.trim()) {
        quill.clipboard.dangerouslyPasteHTML(stored);
    }

    document.querySelector('form[action*="contracts"]').addEventListener('submit', () => {
        document.getElementById('document_content_input').value = quill.root.innerHTML;
    });
});
</script>
@endpush
