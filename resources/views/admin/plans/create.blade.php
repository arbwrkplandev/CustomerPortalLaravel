@extends('layouts.app')
@section('portal-name', 'Admin Hub')
@section('page-title', 'Add Plan')
@section('page-subtitle', 'Create plan with description and pricing rates')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="max-w-3xl animate-fadeInUp">
    <div class="mb-6">
        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline">← Back to Plans</a>
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

        <form method="POST" action="{{ route('admin.plans.store') }}" class="space-y-5">
            @csrf
            @include('admin.plans.partials.form-fields')
            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">Create Plan</button>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
