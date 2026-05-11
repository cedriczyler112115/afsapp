<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">No</th>
                <th class="text-nowrap">Item Name</th>
                <th class="text-nowrap">Category</th>
                <th class="text-nowrap">SKU</th>
                <th class="text-nowrap">Current Qty</th>
                <th class="text-nowrap">Reorder Level</th>
                <th class="text-nowrap">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stockTransactions as $transaction)
            <tr>
                <td>{{ ($stockTransactions->currentPage() - 1) * $stockTransactions->perPage() + $loop->iteration }}</td>
                <td>{{ $transaction->item_name }}</td>
                <td>{{ $transaction->category_name }}</td>
                <td>{{ $transaction->sku }}</td>
                <td>{{ $transaction->current_quantity }}</td>
                <td>{{ $transaction->reorder_level }}</td>
                <td class="text-nowrap">
                    <a href="{{ route('stock-in.edit', $transaction->item_id) }}" class="btn btn-sm btn-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                    @if($transaction->current_quantity == 0)
                    <form action="{{ route('stock-in.destroy', $transaction->item_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete all stock-in history for this item? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        {{-- <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button> --}}
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">No Stock In Transactions Found</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="table-light fw-bold">
                <td colspan="4" class="text-end">Overall Total:</td>
                <td>{{ $overallTotalQuantity ?? 0 }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

@php
    $from = $stockTransactions->firstItem() ?? 0;
    $to = $stockTransactions->lastItem() ?? 0;
    $total = $stockTransactions->total() ?? 0;
@endphp
<div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
    <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
    <div>{!! $stockTransactions->links() !!}</div>
</div>
