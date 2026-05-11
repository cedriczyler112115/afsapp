@extends('layouts.app')

@section('title', '4Ps AFS-IS - Low Stock Alert')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Low Stock Alert</span>
        <a href="{{ route('items.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Items
        </a>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">
                            <a href="{{ route('low-stock.index', ['sort' => 'sku', 'direction' => request('sort') == 'sku' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                SKU
                                @if(request('sort') == 'sku')
                                    <i class="bi bi-arrow-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up ms-1 text-muted opacity-25"></i>
                                @endif
                            </a>
                        </th>
                        <th scope="col">
                            <a href="{{ route('low-stock.index', ['sort' => 'item_name', 'direction' => request('sort') == 'item_name' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                Item
                                @if(request('sort') == 'item_name')
                                    <i class="bi bi-arrow-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up ms-1 text-muted opacity-25"></i>
                                @endif
                            </a>
                        </th>
                        <th scope="col">
                            <a href="{{ route('low-stock.index', ['sort' => 'current_quantity', 'direction' => request('sort') == 'current_quantity' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                Current Qty
                                @if(request('sort') == 'current_quantity')
                                    <i class="bi bi-arrow-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up ms-1 text-muted opacity-25"></i>
                                @endif
                            </a>
                        </th>
                        <th scope="col">
                            <a href="{{ route('low-stock.index', ['sort' => 'reorder_level', 'direction' => request('sort') == 'reorder_level' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                Reorder Level
                                @if(request('sort') == 'reorder_level')
                                    <i class="bi bi-arrow-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up ms-1 text-muted opacity-25"></i>
                                @endif
                            </a>
                        </th>
                        <th scope="col">
                            <a href="{{ route('low-stock.index', ['sort' => 'shortage', 'direction' => request('sort') == 'shortage' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                Shortage
                                @if(request('sort') == 'shortage')
                                    <i class="bi bi-arrow-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up ms-1 text-muted opacity-25"></i>
                                @endif
                            </a>
                        </th>
                        <th scope="col">
                            <a href="{{ route('low-stock.index', ['sort' => 'status', 'direction' => request('sort') == 'status' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                Status
                                @if(request('sort') == 'status')
                                    <i class="bi bi-arrow-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @else
                                    <i class="bi bi-arrow-down-up ms-1 text-muted opacity-25"></i>
                                @endif
                            </a>
                        </th>
                        <th scope="col" class="text-end">
                            <i class="bi bi-files text-muted"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $shortage = $item->current_quantity - $item->reorder_level;
                            $status = '';
                            $statusColor = '';
                            
                            if ($item->current_quantity < $item->reorder_level) {
                                $status = 'Critical';
                                $statusColor = 'text-danger';
                            } elseif ($item->current_quantity == $item->reorder_level) {
                                $status = 'Low';
                                $statusColor = 'text-warning';
                            } else {
                                $status = 'OK';
                                $statusColor = 'text-success';
                            }
                        @endphp
                        <tr>
                            <td>{{ $item->sku }}</td>
                            <td>
                                <div class="fw-bold">{{ $item->item_name }}</div>
                                @if($item->category)
                                    <small class="text-muted">{{ $item->category->category_name }}</small>
                                @endif
                            </td>
                            <td>{{ $item->current_quantity }}</td>
                            <td>{{ $item->reorder_level }}</td>
                            <td>
                                <span class="{{ $shortage < 0 ? 'text-danger' : ($shortage == 0 ? 'text-warning' : 'text-success') }}">
                                    {{ $shortage }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-circle-fill {{ $statusColor }} me-2" style="font-size: 0.75rem;"></i>
                                    <span>{{ $status }}</span>
                                </div>
                            </td>
                            <td class="text-end">
                                {{-- Placeholder for actions or copy icon as per image --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                No items found.
                            </td>
                        </tr>
                    @endforelse
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
            <div>{!! $items->appends(request()->query())->links() !!}</div>
        </div>
    </div>
</div>
@endsection
