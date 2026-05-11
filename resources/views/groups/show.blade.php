@extends('layouts.app')

@section('title', 'Library - View Group')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Group Details</span>
        <div class="d-flex gap-2">
            <a href="{{ route('groups.edit', $group) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
            <a href="{{ route('groups.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-secondary small">Id</div>
                <div class="fw-semibold">{{ $group->id }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Status</div>
                <div>
                    <span class="badge {{ (int) $group->status === 1 ? 'bg-success' : 'bg-secondary' }}">{{ (int) $group->status === 1 ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Group Name</div>
                <div class="fw-semibold">{{ $group->group_name }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Created By</div>
                <div>{{ $group->created_by }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Date Created</div>
                <div>{{ $group->date_created ? \Carbon\Carbon::parse($group->date_created)->format('Y-m-d H:i') : '' }}</div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end">
            <form action="{{ route('groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Delete this group?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection

