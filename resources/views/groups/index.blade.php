@extends('layouts.app')

@section('title', 'Library - Group Section')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Group Section</span>
        <a href="{{ route('groups.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Create</a>
    </div>
    <div class="card-body p-2 p-md-3">
        @if(session('status'))
            <div class="alert alert-success mb-3">{{ session('status') }}</div>
        @endif
        @if($errors->has('database'))
            <div class="alert alert-danger mb-3">{{ $errors->first('database') }}</div>
        @endif

        <form method="GET" class="row g-2 mb-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Group name" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1" {{ (string)request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ (string)request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Show</label>
                <select name="per_page" class="form-select form-select-sm">
                    <option value="10" {{ (string)request('per_page', 10) === '10' ? 'selected' : '' }}>10</option>
                    <option value="25" {{ (string)request('per_page') === '25' ? 'selected' : '' }}>25</option>
                    <option value="50" {{ (string)request('per_page') === '50' ? 'selected' : '' }}>50</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
                <a class="btn btn-sm btn-outline-secondary w-100" href="{{ route('groups.index') }}"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-nowrap" style="width: 80px;">Id</th>
                        <th class="text-nowrap">Group Name</th>
                        <th class="text-nowrap" style="width: 120px;">Status</th>
                        <th class="text-nowrap" style="width: 130px;">Created By</th>
                        <th class="text-nowrap" style="width: 170px;">Date Created</th>
                        <th class="text-nowrap" style="width: 210px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $g)
                        <tr>
                            <td>{{ $g->id }}</td>
                            <td class="fw-semibold">{{ $g->group_name }}</td>
                            <td>
                                <span class="badge {{ (int) $g->status === 1 ? 'bg-success' : 'bg-secondary' }}">{{ (int) $g->status === 1 ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td>{{ $g->created_by }}</td>
                            <td>{{ $g->date_created ? \Carbon\Carbon::parse($g->date_created)->format('Y-m-d H:i') : '' }}</td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('groups.show', $g) }}"><i class="bi bi-eye"></i></a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('groups.edit', $g) }}"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('groups.destroy', $g) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this group?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No groups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @php
            $from = $groups->firstItem() ?? 0;
            $to = $groups->lastItem() ?? 0;
            $total = $groups->total() ?? 0;
        @endphp
        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
            <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
            <div>{!! $groups->links() !!}</div>
        </div>
    </div>
</div>
@endsection
