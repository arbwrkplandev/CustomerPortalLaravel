@extends('layouts.app')
@section('title', 'Announcements')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Announcements</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Broadcast messages to customers</p>
        </div>
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Announcement
        </a>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl" style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3)">
        <p class="text-green-400">{{ session('success') }}</p>
    </div>
    @endif

    @if($announcements->isEmpty())
        <div class="card text-center py-16">
            <div class="text-5xl mb-4">📢</div>
            <h3 class="text-lg font-semibold" style="color: var(--color-text-primary)">No announcements yet</h3>
            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary mt-4">Create First Announcement</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($announcements as $announcement)
            <div class="card">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="font-bold text-lg" style="color: var(--color-text-primary)">{{ $announcement->title }}</h3>
                            @if($announcement->is_published) <span class="badge badge-success">Published</span>
                            @else <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Draft</span>
                            @endif
                            <span class="badge" style="background: rgba(99,102,241,0.15); color: #818cf8">{{ ucfirst($announcement->type) }}</span>
                        </div>
                        <p class="text-sm" style="color: var(--color-text-secondary)">{{ Str::limit($announcement->content, 150) }}</p>
                        <div class="flex items-center gap-4 mt-3 text-xs" style="color: var(--color-text-secondary)">
                            <span>Created {{ $announcement->created_at->format('M d, Y') }}</span>
                            @if($announcement->published_at)
                            <span>Published {{ \Carbon\Carbon::parse($announcement->published_at)->format('M d, Y') }}</span>
                            @endif
                            @if($announcement->expires_at)
                            <span>Expires {{ \Carbon\Carbon::parse($announcement->expires_at)->format('M d, Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-outline btn-sm">Edit</a>
                        <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn {{ $announcement->is_published ? 'btn-warning' : 'btn-success' }} btn-sm">
                                {{ $announcement->is_published ? 'Unpublish' : 'Publish' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('Delete this announcement?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $announcements->links() }}</div>
    @endif
</div>
@endsection
