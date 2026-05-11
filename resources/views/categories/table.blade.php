<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">No</th>
                <th class="text-nowrap">Name</th>
                <th class="text-nowrap">Description</th>
                <th class="text-nowrap">Status</th>
                <th class="text-nowrap" width="280px">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
            <tr>
                <td>{{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}</td>
                <td>{{ $category->category_name }}</td>
                <td>{{ $category->description }}</td>
                <td><span class="badge {{ (int)$category->status === 1 ? 'bg-success' : 'bg-secondary' }}">{{ (int)$category->status === 1 ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <a class="btn btn-primary me-2 btn-sm edit-category" href="javascript:void(0)" data-id="{{ $category->category_id }}"><i class="bi bi-pencil-square me-1"></i></a>
                    <button type="button" class="btn btn-danger btn-sm delete-category" data-id="{{ $category->category_id }}"><i class="bi bi-trash me-1"></i></button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@php
    $from = $categories->firstItem() ?? 0;
    $to = $categories->lastItem() ?? 0;
    $total = $categories->total() ?? 0;
@endphp
<div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
    <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
    <div>{!! $categories->links() !!}</div>
</div>
