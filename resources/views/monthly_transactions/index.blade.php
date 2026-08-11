@extends('layouts.app')

@section('title', '4Ps AFS-IS - Monthly Transactions')

@section('content')
<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <div>
            <div class="fw-semibold">Monthly Transactions</div>
            <small class="opacity-75">Inventory movement history from stock-in, issuance, borrowing, returns, and damaged-item reports.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-primary">{{ number_format($transactions->total()) }} record(s)</span>
            <button type="button" class="btn btn-light btn-sm" id="print-monthly-report">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>

    <div class="card-body p-2 p-md-3">
        <form method="GET" action="{{ route('monthly-transactions.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-6 col-md-2">
                <label for="year" class="form-label small mb-1">Year</label>
                <select id="year" name="year" class="form-select form-select-sm">
                    @foreach($years as $year)
                        <option value="{{ $year }}" @selected($selectedYear === (int) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="month" class="form-label small mb-1">Month</label>
                <select id="month" name="month" class="form-select form-select-sm">
                    @foreach(range(1, 12) as $month)
                        <option value="{{ $month }}" @selected($selectedMonth === $month)>{{ \Carbon\Carbon::create(2000, $month, 1)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="category_id" class="form-label small mb-1">Category</label>
                <select id="category_id" name="category_id" class="form-select form-select-sm select2">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->category_id }}" @selected($selectedCategoryId === (int) $category->category_id)>{{ $category->category_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="type" class="form-label small mb-1">Type</label>
                <select id="type" name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($availableTypes as $transactionType)
                        <option value="{{ $transactionType }}" @selected($selectedType === $transactionType)>{{ $transactionType }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-auto">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Apply</button>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('monthly-transactions.index') }}" title="Reset filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
            <div class="col-md-auto ms-md-auto">
                <label for="per_page" class="form-label small mb-1">Show</label>
                <select id="per_page" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 76px">
                    @foreach([10, 20, 30, 50, 100] as $size)
                        <option value="{{ $size }}" @selected((string) $perPage === (string) $size)>{{ $size }}</option>
                    @endforeach
                    <option value="all" @selected($perPage === 'all')>All</option>
                </select>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 130px">Date & Time</th>
                        <th style="width: 85px">Type</th>
                        <th>Item</th>
                        <th style="width: 150px">Category</th>
                        <th style="width: 150px">Unit / Serial</th>
                        <th style="width: 70px" class="text-center">Qty</th>
                        <th style="width: 190px">Requester / Borrower</th>
                        <th style="width: 160px">Recorded By</th>
                        <th style="width: 130px">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        @php
                            $badge = match($transaction->type) {
                                'IN', 'RETURN' => 'success',
                                'OUT', 'BORROW' => 'primary',
                                'DAMAGED' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td class="text-nowrap">
                                <div>{{ $transaction->date_created?->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $transaction->date_created?->format('h:i A') }}</small>
                            </td>
                            <td><span class="badge bg-{{ $badge }}">{{ $transaction->type }}</span></td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 260px" title="{{ $transaction->item?->item_name }}">{{ $transaction->item?->item_name ?? 'Deleted item' }}</div>
                                @if($transaction->item?->sku)<small class="text-muted">SKU: {{ $transaction->item->sku }}</small>@endif
                            </td>
                            <td>{{ $transaction->item?->category?->category_name ?? 'Uncategorized' }}</td>
                            <td>
                                @if((int) $transaction->unit_id > 0)
                                    <div class="text-truncate" style="max-width: 145px" title="{{ $transaction->unit?->full_code }}">{{ $transaction->unit?->full_code ?? '#'.$transaction->unit_id }}</div>
                                    @if($transaction->unit?->serial)<small class="text-muted">{{ $transaction->unit->serial }}</small>@endif
                                @else
                                    <span class="text-muted">Bulk / non-serialized</span>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($transaction->transaction_quantity) }}</td>
                            <td>
                                <div>{{ $transaction->party_name }}</div>
                                <small class="text-muted">{{ $transaction->party_role }}</small>
                            </td>
                            <td>{{ $transaction->creator?->name ?? 'Unknown' }}</td>
                            <td>{{ $transaction->source_reference ?? 'Transaction #'.$transaction->id }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No transactions found for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted">
                Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ number_format($transactions->total()) }} records
            </small>
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#category_id').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true });

        $('#print-monthly-report').on('click', function () {
            const params = new URLSearchParams(window.location.search);
            const printUrl = new URL("{{ route('monthly-transactions.print') }}", window.location.origin);

            ['year', 'month', 'category_id', 'type'].forEach((key) => {
                const value = params.get(key);
                if (value !== null && value !== '') {
                    printUrl.searchParams.set(key, value);
                }
            });

            window.open(printUrl.toString(), '_blank', 'noopener');
        });
    });
</script>
@endpush
