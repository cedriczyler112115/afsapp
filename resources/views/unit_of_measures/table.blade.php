<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">No</th>
                <th class="text-nowrap">Name</th>
                <th class="text-nowrap">Code</th>
                <th class="text-nowrap">Description</th>
                <th class="text-nowrap">Status</th>
                <th class="text-nowrap" width="280px">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($units as $unit)
            <tr>
                <td>{{ ($units->currentPage() - 1) * $units->perPage() + $loop->iteration }}</td>
                <td>{{ $unit->unit_name }}</td>
                <td>{{ $unit->unit_code }}</td>
                <td>{{ $unit->description }}</td>
                <td><span class="badge {{ (int)$unit->is_active === 1 ? 'bg-success' : 'bg-secondary' }}">{{ (int)$unit->is_active === 1 ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <a class="btn btn-primary me-2 btn-sm edit-unit" href="javascript:void(0)" data-id="{{ $unit->id }}"><i class="bi bi-pencil-square me-1"></i></a>
                    <button type="button" class="btn btn-danger btn-sm delete-unit" data-id="{{ $unit->id }}"><i class="bi bi-trash me-1"></i></button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@php
    $from = $units->firstItem() ?? 0;
    $to = $units->lastItem() ?? 0;
    $total = $units->total() ?? 0;
@endphp
<div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
    <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
    <div>{!! $units->links() !!}</div>
</div>
