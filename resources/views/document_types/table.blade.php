<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">No</th>
                <th class="text-nowrap">Name</th>
                <th class="text-nowrap">Status</th>
                <th class="text-nowrap" width="220px">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($types as $type)
            <tr>
                <td>{{ ($types->currentPage() - 1) * $types->perPage() + $loop->iteration }}</td>
                <td>{{ $type->name }}</td>
                <td><span class="badge {{ (int)$type->is_active === 1 ? 'bg-success' : 'bg-secondary' }}">{{ (int)$type->is_active === 1 ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <a class="btn btn-primary me-2 btn-sm edit-type" href="javascript:void(0)" data-id="{{ $type->id }}"><i class="bi bi-pencil-square me-1"></i></a>
                    <button type="button" class="btn btn-danger btn-sm delete-type" data-id="{{ $type->id }}"><i class="bi bi-trash me-1"></i></button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@php
    $from = $types->firstItem() ?? 0;
    $to = $types->lastItem() ?? 0;
    $total = $types->total() ?? 0;
@endphp
<div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
    <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
    <div>{!! $types->links() !!}</div>
</div>
