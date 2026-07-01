@extends('layouts.app')
@section('title', 'New Contract')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
.drag-zone { border: 2px dashed var(--color-brand-primary); border-radius: 16px; transition: all 0.3s; cursor: pointer; }
.drag-zone:hover, .drag-zone.dragging { background: rgba(99,102,241,0.06); border-color: #8b5cf6; transform: scale(1.01); }
.sign-field-card { border: 1px solid var(--color-border); border-radius: 12px; padding: 16px; transition: all 0.3s; animation: slideInUp 0.3s ease; }
.sign-field-card:hover { border-color: var(--color-brand-primary); }
@keyframes slideInUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
.field-type-btn { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--color-border); cursor: pointer; font-size: 13px; transition: all 0.2s; white-space: nowrap; }
.field-type-btn:hover { border-color: var(--color-brand-primary); background: rgba(99,102,241,0.08); }
.tab-btn { padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.tab-active { background: var(--color-brand-primary) !important; color: white !important; }
/* Quill toolbar theming */
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
.ql-snow.ql-toolbar .ql-active .ql-picker-label { color: var(--color-brand-primary) !important; }
/* Quill heading styles inside editor */
.ql-editor h1 { font-size: 1.9em; font-weight: 800; margin: 0.6em 0 0.3em; color: var(--color-text-primary); }
.ql-editor h2 { font-size: 1.45em; font-weight: 700; margin: 0.6em 0 0.3em; color: var(--color-text-primary); }
.ql-editor h3 { font-size: 1.15em; font-weight: 700; margin: 0.5em 0 0.25em; color: var(--color-text-primary); }
.ql-editor p { margin: 0.4em 0; }
.ql-editor ul, .ql-editor ol { padding-left: 1.6em; margin: 0.5em 0; }
.ql-editor blockquote { border-left: 3px solid var(--color-brand-primary); padding-left: 16px; color: var(--color-text-secondary); margin: 12px 0; font-style: italic; }
.ql-editor a { color: var(--color-brand-primary); text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="animate-fadeInUp" x-data="contractCreator()">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline">← Back</a>
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">New Contract</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Create, upload and assign signature fields</p>
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

    <form method="POST" action="{{ route('admin.contracts.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Basic Info --}}
        <div class="card">
            <h2 class="font-bold text-lg mb-5" style="color: var(--color-text-primary)">Contract Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="form-label">Customer <span class="text-red-400">*</span></label>
                    <select name="tenant_id" required class="form-input">
                        <option value="">Select customer…</option>
                        @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>{{ $tenant->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Contract Title <span class="text-red-400">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="form-input" placeholder="Service Agreement 2025">
                </div>
                <div>
                    <label class="form-label">Contract Type</label>
                    <select name="type" class="form-input">
                        @foreach(['service' => 'Service Agreement', 'nda' => 'NDA', 'sla' => 'SLA', 'custom' => 'Custom'] as $val => $label)
                        <option value="{{ $val }}" {{ old('type', 'service') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Valid From</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Valid Until</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-input">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-input" placeholder="Brief summary of this contract">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Content Section --}}
        <div class="card">
            <h2 class="font-bold text-lg mb-2" style="color: var(--color-text-primary)">Contract Content</h2>
            <p class="text-sm mb-5" style="color: var(--color-text-secondary)">Upload a PDF or write the contract text. You can do both.</p>

            {{-- Tab Toggle --}}
            <div class="flex gap-2 p-1 rounded-xl mb-5 w-fit" style="background: var(--color-surface-2)">
                <button type="button" @click="contentTab = 'pdf'"
                    :class="contentTab === 'pdf' ? 'tab-active' : ''"
                    class="tab-btn" style="color: var(--color-text-secondary)">
                    📎 Upload PDF
                </button>
                <button type="button" @click="contentTab = 'text'"
                    :class="contentTab === 'text' ? 'tab-active' : ''"
                    class="tab-btn" style="color: var(--color-text-secondary)">
                    ✏️ Write Content
                </button>
            </div>

            {{-- PDF Upload --}}
            <div x-show="contentTab === 'pdf'" x-transition>
                <div class="drag-zone p-10 text-center"
                     :class="{ dragging: dragging }"
                     @dragover.prevent="dragging = true"
                     @dragleave="dragging = false"
                     @drop.prevent="handleDrop($event)"
                     @click="$refs.pdfInput.click()">
                    <input type="file" name="pdf_file" accept=".pdf" x-ref="pdfInput" class="hidden"
                           @change="handleFileSelect($event)">
                    <div x-show="!pdfFile">
                        <div class="text-5xl mb-3">📄</div>
                        <p class="font-semibold mb-1" style="color: var(--color-text-primary)">Drag & drop PDF here</p>
                        <p class="text-sm" style="color: var(--color-text-secondary)">or click to browse · Max 20MB</p>
                    </div>
                    <div x-show="pdfFile" class="flex items-center justify-center gap-3">
                        <div class="text-4xl">✅</div>
                        <div>
                            <div class="font-semibold" style="color: var(--color-text-primary)" x-text="pdfFile?.name"></div>
                            <div class="text-sm" style="color: var(--color-text-secondary)" x-text="pdfFile ? formatSize(pdfFile.size) : ''"></div>
                        </div>
                        <button type="button" @click.stop="clearPdf()" class="btn btn-outline btn-sm ml-4">Remove</button>
                    </div>
                </div>
            </div>

            {{-- Text Content (Quill rich-text editor) --}}
            <div x-show="contentTab === 'text'" x-transition>
                {{-- Hidden textarea — value synced from Quill before submit --}}
                <textarea name="html_content" id="html_content_input" class="hidden">{{ old('html_content') }}</textarea>
                {{-- Quill editor mount point --}}
                <div id="quill-editor"></div>
                <p class="text-xs mt-3" style="color: var(--color-text-secondary)">Use the toolbar above to format headings, bold, lists, alignment, links and more.</p>
            </div>
        </div>

        {{-- Sign Fields Builder --}}
        <div class="card">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="font-bold text-lg" style="color: var(--color-text-primary)">Signature Fields</h2>
                    <p class="text-sm mt-0.5" style="color: var(--color-text-secondary)">Define what the customer needs to fill in when signing</p>
                </div>
                <div class="flex gap-2 flex-wrap justify-end">
                    <button type="button" @click="addField('signature')" class="field-type-btn" style="color: var(--color-text-primary)">✍️ Signature</button>
                    <button type="button" @click="addField('initials')" class="field-type-btn" style="color: var(--color-text-primary)">🖊 Initials</button>
                    <button type="button" @click="addField('date')" class="field-type-btn" style="color: var(--color-text-primary)">📅 Date</button>
                    <button type="button" @click="addField('text')" class="field-type-btn" style="color: var(--color-text-primary)">📝 Text</button>
                </div>
            </div>

            {{-- Fields List --}}
            <div class="space-y-3" x-show="fields.length > 0">
                <template x-for="(field, index) in fields" :key="field.id">
                    <div class="sign-field-card" style="background: var(--color-surface-2)">
                        <div class="flex items-center gap-3">
                            {{-- Type Icon --}}
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0"
                                 style="background: rgba(99,102,241,0.12)">
                                <span x-text="fieldIcon(field.field_type)"></span>
                            </div>

                            {{-- Label --}}
                            <div class="flex-1">
                                <input type="text" x-model="field.label"
                                       :placeholder="'Label for ' + fieldTypeName(field.field_type) + ' field'"
                                       class="form-input py-2 text-sm"
                                       style="background: var(--color-surface)">
                            </div>

                            {{-- Type selector --}}
                            <select x-model="field.field_type" class="form-input py-2 text-sm w-36">
                                <option value="signature">Signature</option>
                                <option value="initials">Initials</option>
                                <option value="date">Date</option>
                                <option value="text">Text</option>
                                <option value="checkbox">Checkbox</option>
                            </select>

                            {{-- Required toggle --}}
                            <label class="flex items-center gap-1.5 text-sm cursor-pointer" style="color: var(--color-text-secondary)">
                                <input type="checkbox" x-model="field.required" class="rounded"> Required
                            </label>

                            {{-- Remove --}}
                            <button type="button" @click="removeField(index)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-red-400 hover:bg-red-500/10 transition-colors flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            {{-- Hidden form inputs --}}
                            <input type="hidden" :name="'sign_fields[' + index + '][field_type]'" :value="field.field_type">
                            <input type="hidden" :name="'sign_fields[' + index + '][label]'" :value="field.label">
                            <input type="hidden" :name="'sign_fields[' + index + '][required]'" :value="field.required ? 1 : 0">
                            <input type="hidden" :name="'sign_fields[' + index + '][page_number]'" value="1">
                            <input type="hidden" :name="'sign_fields[' + index + '][x_position]'" value="0">
                            <input type="hidden" :name="'sign_fields[' + index + '][y_position]'" value="0">
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty state --}}
            <div x-show="fields.length === 0" class="text-center py-10 rounded-xl" style="background: var(--color-surface-2)">
                <div class="text-4xl mb-3">✍️</div>
                <p class="font-medium" style="color: var(--color-text-primary)">No signature fields yet</p>
                <p class="text-sm mt-1" style="color: var(--color-text-secondary)">Use the buttons above to add fields the customer must fill in</p>
            </div>

            {{-- Default fields hint --}}
            <div x-show="fields.length === 0" class="mt-3 flex gap-2 justify-center flex-wrap">
                <button type="button" @click="addDefaultFields()" class="btn btn-outline btn-sm">Add Standard Fields (Signature + Date)</button>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex gap-4">
            <button type="submit" class="btn btn-primary text-base px-8">Create Contract (Draft)</button>
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
// ── Quill rich-text editor ──────────────────────────────────────────────
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
        placeholder: 'Write your contract here…\n\nStart with a heading, then add your terms and conditions.',
        modules: { toolbar: toolbarOptions },
    });
    // Pre-fill from old() on validation failure
    const stored = document.getElementById('html_content_input').value;
    if (stored && stored.trim()) {
        quill.clipboard.dangerouslyPasteHTML(stored);
    }
    // Sync to hidden textarea before form submit
    document.querySelector('form[action*="contracts"]').addEventListener('submit', () => {
        document.getElementById('html_content_input').value = quill.root.innerHTML;
    });
});

function contractCreator() {
    return {
        contentTab: 'pdf',
        dragging: false,
        pdfFile: null,
        fields: [],

        addField(type) {
            const labels = { signature: 'Customer Signature', initials: 'Initials', date: 'Date Signed', text: 'Full Name', checkbox: 'I agree to the terms' };
            this.fields.push({ id: Date.now(), field_type: type, label: labels[type] || '', required: true });
        },

        addDefaultFields() {
            this.addField('signature');
            this.addField('date');
        },

        removeField(index) {
            this.fields.splice(index, 1);
        },

        fieldIcon(type) {
            const icons = { signature: '✍️', initials: '🖊', date: '📅', text: '📝', checkbox: '☑️' };
            return icons[type] || '📝';
        },

        fieldTypeName(type) {
            const names = { signature: 'Signature', initials: 'Initials', date: 'Date', text: 'Text', checkbox: 'Checkbox' };
            return names[type] || type;
        },

        handleDrop(event) {
            this.dragging = false;
            const file = event.dataTransfer.files[0];
            if (file && file.type === 'application/pdf') {
                this.pdfFile = file;
                // Set on the actual input
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.pdfInput.files = dt.files;
            }
        },

        handleFileSelect(event) {
            this.pdfFile = event.target.files[0] || null;
        },

        clearPdf() {
            this.pdfFile = null;
            this.$refs.pdfInput.value = '';
        },

        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }
    };
}
</script>
@endpush
@endsection
