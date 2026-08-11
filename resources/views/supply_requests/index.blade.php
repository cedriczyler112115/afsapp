@extends('layouts.app')

@section('title', 'Supply Requests')

@section('content')
<style>
    .supply-request-pending-row > td {
        background-color: #fff1f2 !important;
    }
    .supply-request-pending-row:hover > td {
        background-color: #ffe4e6 !important;
    }
</style>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-semibold">Supply Requests</span>
            <div class="small text-muted">Request supplies and track approval through issuance.</div>
        </div>
        <a href="{{ route('supply-requests.create') }}" class="btn btn-primary btn-sm text-nowrap">
            <i class="bi bi-plus-circle me-1"></i>New Request
        </a>
    </div>
    <div class="card-body p-2 p-md-3">
        @if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-5">
                <label class="form-label small mb-1">Search</label>
                <input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Request no., requester or item">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="READY" @selected(request('status') === 'READY')>READY FOR ISSUANCE</option>
                    @foreach(['PENDING','APPROVED','PARTIALLY_ISSUED','COMPLETED','REJECTED','CANCELLED'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Show</label>
                <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(['10', '20', '50', '100', 'ALL'] as $size)
                        <option value="{{ $size }}" @selected(strtoupper((string) request('per_page', '10')) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('supply-requests.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover align-middle mb-2">
                <thead class="table-light">
                    <tr>
                        <th>Request No.</th>
                        @if($canProcess)<th>Requested By</th>@endif
                        <th>Items</th>
                        <th>Needed Date</th>
                        <th>Status</th>
                        <th>Receipt</th>
                        <th>Requested</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $supplyRequest)
                        @php
                            $badge = match($supplyRequest->status) {
                                'APPROVED' => 'primary', 'PARTIALLY_ISSUED' => 'warning', 'COMPLETED' => 'success',
                                'REJECTED' => 'danger', 'CANCELLED' => 'secondary', default => 'info'
                            };
                        @endphp
                        <tr @class(['supply-request-pending-row' => $supplyRequest->status === 'PENDING'])>
                            <td class="text-nowrap fw-semibold">{{ $supplyRequest->request_no }}</td>
                            @if($canProcess)<td>{{ $supplyRequest->requester->name }}</td>@endif
                            <td title="{{ $supplyRequest->items->pluck('item.item_name')->join(', ') }}">
                                {{ $supplyRequest->items->count() }} item(s)
                            </td>
                            <td class="text-nowrap">{{ $supplyRequest->needed_at?->format('M d, Y') ?? '-' }}</td>
                            <td><span class="badge bg-{{ $badge }}">{{ str_replace('_', ' ', $supplyRequest->status) }}</span></td>
                            <td>
                                @php $receiptBadge = $supplyRequest->receipt_status === 'RECEIVED' ? 'success' : ($supplyRequest->receipt_status === 'PARTIALLY_RECEIVED' ? 'warning' : 'secondary'); @endphp
                                <span class="badge bg-{{ $receiptBadge }}">{{ str_replace('_', ' ', $supplyRequest->receipt_status) }}</span>
                            </td>
                            <td class="text-nowrap">{{ $supplyRequest->created_at->format('M d, Y h:i A') }}</td>
                            <td class="text-center">
                                <a href="{{ route('supply-requests.show', $supplyRequest) }}" class="btn btn-outline-primary btn-sm" title="View request"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canProcess ? 8 : 7 }}" class="text-center text-muted py-4">No supply requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} records</div>
            <div>{{ $requests->links() }}</div>
        </div>
    </div>
</div>
@endsection
