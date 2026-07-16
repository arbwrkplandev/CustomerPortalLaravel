@extends('layouts.app')
@section('title', 'Contract Agreements')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Contract Agreements</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Manage agreement templates from WrkPlan ERP</p>
        </div>
        <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Agreement
        </a>
    </div>

    <div class="card mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input flex-1 min-w-48" placeholder="Search by ID, type, title, or creator...">
            <select name="tagged" class="form-input w-44">
                <option value="">All Tags</option>
                <option value="yes" {{ request('tagged') === 'yes' ? 'selected' : '' }}>Tagged</option>
                <option value="no" {{ request('tagged') === 'no' ? 'selected' : '' }}>Not Tagged</option>
            </select>
            <button type="submit" class="btn btn-outline">Filter</button>
            @if(request()->anyFilled(['search', 'tagged']))
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline">Clear</a>
            @endif
        </form>
    </div>

    @if($agreements->isEmpty())
        <div class="card text-center py-16">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-semibold mb-2" style="color: var(--color-text-primary)">No contract agreements found</h3>
            <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary mt-4">Create First Agreement</a>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Length</th>
                        <th>Tagged</th>
                        <th>Compressed</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agreements as $agreement)
                    <tr>
                        <td class="font-mono text-xs" style="color: var(--color-text-secondary)">#{{ $agreement->id }}</td>
                        <td style="color: var(--color-text-primary)">{{ $agreement->document_type ?: '—' }}</td>
                        <td>
                            <div class="font-semibold" style="color: var(--color-text-primary)">{{ $agreement->document_title ?: '—' }}</div>
                            <div class="text-xs mt-0.5" style="color: var(--color-text-secondary)">By {{ $agreement->created_by ?: '—' }}</div>
                        </td>
                        <td style="color: var(--color-text-secondary)">{{ number_format((int) $agreement->content_length) }}</td>
                        <td>
                            @if($agreement->is_tagged)
                                <span class="badge badge-warning">Tagged</span>
                            @else
                                <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">No</span>
                            @endif
                        </td>
                        <td>
                            @if($agreement->is_compressed)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">No</span>
                            @endif
                        </td>
                        <td style="color: var(--color-text-secondary)">
                            {{ $agreement->created_at ? $agreement->created_at->format('M d, Y') : '—' }}
                        </td>
                        <td style="color: var(--color-text-secondary)">
                            {{ $agreement->updated_at ? $agreement->updated_at->format('M d, Y') : '—' }}
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.contracts.show', $agreement->id) }}" class="btn btn-outline btn-sm">View</a>
                                @if(!$agreement->is_tagged)
                                <form method="POST" action="{{ route('admin.contracts.delete', $agreement->id) }}" class="inline" onsubmit="return confirm('Delete this contract agreement?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Delete
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $agreements->links() }}</div>
    @endif
</div>
@endsection
