@extends('layouts.app')
@section('title', 'Edit Announcement')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp max-w-2xl">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline">← Back</a>
        <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Edit Announcement</h1>
    </div>

    <div class="card">
        @if($errors->any())
        <div class="mb-6 p-4 rounded-xl" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3)">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                <li class="text-sm text-red-400">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="space-y-6">
            @csrf @method('PUT')
            <div>
                <label class="form-label">Title <span class="text-red-400">*</span></label>
                <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required class="form-input">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Type</label>
                    <select name="type" class="form-input">
                        @foreach(['info', 'warning', 'success', 'maintenance', 'feature'] as $t)
                        <option value="{{ $t }}" {{ old('type', $announcement->type) === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-input">
                        @foreach(['low', 'medium', 'high', 'critical'] as $p)
                        <option value="{{ $p }}" {{ old('priority', $announcement->priority) === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Content <span class="text-red-400">*</span></label>
                <textarea name="content" rows="6" required class="form-input">{{ old('content', $announcement->content) }}</textarea>
            </div>
            <div>
                <label class="form-label">Expires At</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $announcement->expires_at ? \Carbon\Carbon::parse($announcement->expires_at)->format('Y-m-d\TH:i') : '') }}" class="form-input">
            </div>
            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', $announcement->is_published) ? 'checked' : '' }} class="rounded">
                <label for="is_published" class="form-label mb-0">Published</label>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
