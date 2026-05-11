<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">No</th>
                <th class="text-nowrap">Item Name</th>
                <th class="text-nowrap">SKU</th>
                <th class="text-nowrap">Category</th>
                <th class="text-nowrap">Unit</th>
                <th class="text-nowrap">Reorder Level</th>
                <th class="text-nowrap">Description</th>
                <th class="text-nowrap" width="150px">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
            <tr>
                <td>{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->sku }}</td>
                <td>{{ $item->category->category_name ?? '-' }}</td>
                <td>{{ $item->unit->unit_name ?? '-' }}</td>
                <td>{{ $item->reorder_level }}</td>
                <td>{{ $item->description }}</td>
                <td>
                    <a class="btn btn-primary me-2 btn-sm edit-item" href="javascript:void(0)" data-id="{{ $item->item_id }}"><i class="bi bi-pencil-square me-1"></i></a>
                    <button type="button" class="btn btn-danger btn-sm delete-item" data-id="{{ $item->item_id }}"><i class="bi bi-trash me-1"></i></button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@php
    $from = $items->firstItem() ?? 0;
    $to = $items->lastItem() ?? 0;
    $total = $items->total() ?? 0;
@endphp
<div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
    <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
    <div>{!! $items->links() !!}</div>
</div>
