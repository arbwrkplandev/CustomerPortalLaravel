@extends('layouts.app')
@section('title', $agreement->document_title ?: 'Contract Agreement')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
.content-viewer { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; padding: 24px; font-size: 14px; line-height: 1.8; max-height: 420px; overflow-y: auto; }
.content-viewer h1 { font-size: 1.75em; font-weight: 800; margin: 0.7em 0 0.35em; color: var(--color-text-primary); }
.content-viewer h2 { font-size: 1.35em; font-weight: 700; margin: 0.65em 0 0.3em; color: var(--color-text-primary); }
.content-viewer p { margin: 0.5em 0; color: var(--color-text-secondary); }
.ql-toolbar.ql-snow { border: 1px solid var(--color-border) !important; border-bottom: none !important; border-radius: 12px 12px 0 0; background: var(--color-surface-2) !important; }
.ql-container.ql-snow { border: 1px solid var(--color-border) !important; border-radius: 0 0 12px 12px; background: var(--color-surface) !important; min-height: 320px; }
.ql-editor { min-height: 320px; color: var(--color-text-primary); }
</style>
@endpush

@section('content')
<div class="animate-fadeInUp">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline flex-shrink-0">← Back</a>
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-black" style="color: var(--color-text-primary)">{{ $agreement->document_title ?: 'Untitled Agreement' }}</h1>
                    @if($agreement->is_tagged)
                        <span class="badge badge-warning">Tagged</span>
                    @else
                        <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Not Tagged</span>
                    @endif
                </div>
                <p class="mt-1 text-sm" style="color: var(--color-text-secondary)">
                    Agreement #{{ $agreement->id }} · {{ $agreement->document_type ?: '—' }}
                </p>
            </div>
        </div>

        @if(!$agreement->is_tagged)
        <form method="POST" action="{{ route('admin.contracts.delete', $agreement->id) }}" onsubmit="return confirm('Delete this contract agreement?')">
            @csrf
            <button type="submit" class="btn btn-danger">Delete Agreement</button>
        </form>
        @endif
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
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Current Content</h3>
                <div class="content-viewer">{!! $agreement->document_content !!}</div>
            </div>

            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Update Agreement</h3>
                <form method="POST" action="{{ route('admin.contracts.update', $agreement->id) }}" class="space-y-4" id="agreement-update-form">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Document Type <span class="text-red-400">*</span></label>
                            <input type="text" name="document_type" value="{{ old('document_type', $agreement->document_type) }}" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Document Title <span class="text-red-400">*</span></label>
                            <input type="text" name="document_title" value="{{ old('document_title', $agreement->document_title) }}" required class="form-input">
                        </div>
                    </div>

                    <textarea name="document_content" id="document_content_input" class="hidden">{{ old('document_content', $agreement->document_content) }}</textarea>
                    <div id="quill-editor"></div>

                    <div class="flex gap-3">
                        @if(!$agreement->is_tagged)
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        @endif
                        <a href="{{ route('admin.contracts.show', $agreement->id) }}" class="btn btn-outline">Reset</a>
                    </div>
                </form>
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
                        <span style="color: var(--color-text-primary)">{{ $agreement->document_type ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Title</span>
                        <span style="color: var(--color-text-primary)">{{ $agreement->document_title ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Content Length</span>
                        <span style="color: var(--color-text-primary)">{{ number_format((int) $agreement->content_length) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Tagged</span>
                        <span style="color: var(--color-text-primary)">{{ $agreement->is_tagged ? 'Yes' : 'No' }}</span>
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
        placeholder: 'Write agreement content...',
        modules: { toolbar: toolbarOptions },
    });

    const stored = document.getElementById('document_content_input').value;
    if (stored && stored.trim()) {
        quill.clipboard.dangerouslyPasteHTML(stored);
    }

    document.getElementById('agreement-update-form').addEventListener('submit', () => {
        document.getElementById('document_content_input').value = quill.root.innerHTML;
    });
});
</script>
@endpush
