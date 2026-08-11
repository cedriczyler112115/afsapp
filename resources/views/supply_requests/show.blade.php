@extends('layouts.app')
@section('title', 'Supply Request '.$supplyRequest->request_no)
@section('content')
@php
    $badge = match($supplyRequest->status) {
        'APPROVED' => 'primary', 'PARTIALLY_ISSUED' => 'warning', 'COMPLETED' => 'success',
        'REJECTED' => 'danger', 'CANCELLED' => 'secondary', default => 'info'
    };
@endphp
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-semibold">{{ $supplyRequest->request_no }}</span>
            <span class="badge bg-{{ $badge }} ms-1">{{ str_replace('_', ' ', $supplyRequest->status) }}</span>
            @php $receiptBadge = $supplyRequest->receipt_status === 'RECEIVED' ? 'success' : ($supplyRequest->receipt_status === 'PARTIALLY_RECEIVED' ? 'warning' : 'secondary'); @endphp
            <span class="badge bg-{{ $receiptBadge }} ms-1">{{ str_replace('_', ' ', $supplyRequest->receipt_status) }}</span>
        </div>
        <a href="{{ route('supply-requests.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <div class="card-body p-2 p-md-3">
        @if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="row g-3 mb-3 small">
            <div class="col-md-3"><span class="text-muted d-block">Requested By</span><strong>{{ $supplyRequest->requester->name }}</strong></div>
            <div class="col-md-3"><span class="text-muted d-block">Date Requested</span><strong>{{ $supplyRequest->created_at->format('M d, Y h:i A') }}</strong></div>
            <div class="col-md-3"><span class="text-muted d-block">Date Needed</span><strong>{{ $supplyRequest->needed_at?->format('M d, Y') ?? '-' }}</strong></div>
            <div class="col-md-3"><span class="text-muted d-block">Reviewed By</span><strong>{{ $supplyRequest->reviewer?->name ?? '-' }}</strong></div>
            <div class="col-12"><span class="text-muted d-block">Purpose</span><div>{{ $supplyRequest->purpose }}</div></div>
            @if($supplyRequest->review_notes)<div class="col-12"><span class="text-muted d-block">Review Notes</span><div>{{ $supplyRequest->review_notes }}</div></div>@endif
        </div>
        <form method="POST" action="{{ route('supply-requests.approve', $supplyRequest) }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light"><tr><th>No.</th><th>Item</th><th>SKU</th><th>Issue By</th><th>Requested</th><th>Approved</th><th>Issued</th><th>Received</th><th>Balance</th><th>Stock Out / Receipt Records</th><th>Notes</th>@if($canProcess && in_array($supplyRequest->status, ['APPROVED','PARTIALLY_ISSUED']))<th>Action</th>@endif</tr></thead>
                    <tbody>
                    @foreach($supplyRequest->items as $requestItem)
                        <tr>
                            <td>{{ $loop->iteration }}</td><td>{{ $requestItem->item->item_name }}</td><td>{{ $requestItem->item->sku ?? '-' }}</td><td>{{ $requestItem->issue_mode === 'BOX' ? 'Box' : 'PC/S' }}</td>
                            <td class="text-center">{{ number_format($requestItem->requested_quantity) }}</td>
                            <td style="width:125px">
                                @if($canProcess && $supplyRequest->status === 'PENDING')
                                    <input type="number" name="approved[{{ $requestItem->id }}]" min="0" max="{{ $requestItem->requested_quantity }}" value="{{ old('approved.'.$requestItem->id, $requestItem->requested_quantity) }}" class="form-control form-control-sm text-center" required>
                                @else<div class="text-center">{{ $requestItem->approved_quantity === null ? '-' : number_format($requestItem->approved_quantity) }}</div>@endif
                            </td>
                            <td class="text-center">{{ number_format($requestItem->issued_quantity) }}</td>
                            <td class="text-center">{{ number_format($requestItem->received_quantity) }}</td>
                            <td class="text-center fw-semibold">{{ number_format($requestItem->remaining_quantity) }}</td>
                            <td class="small" style="min-width:230px">
                                @forelse($requestItem->issuances as $issuance)
                                    @php $issuanceRequestQuantity = $requestItem->issue_mode === 'BOX' ? $issuance->stockTransactions->count() : $issuance->stockTransactions->sum('quantity'); @endphp
                                    <div class="border-bottom py-1" title="Issued {{ $issuance->date_issued?->format('M d, Y h:i A') }}">
                                        <div><i class="bi bi-box-arrow-up text-success me-1"></i>#{{ $issuance->id }} - {{ number_format($issuanceRequestQuantity) }} {{ $requestItem->issue_mode === 'BOX' ? 'box(es)' : 'PC/S' }}</div>
                                        @if($issuance->received_at)
                                            <div class="text-success"><i class="bi bi-check-circle me-1"></i>Received {{ $issuance->received_at->format('M d, Y h:i A') }}</div>
                                            @if($issuance->receipt_notes)<div class="text-muted">{{ $issuance->receipt_notes }}</div>@endif
                                        @elseif($supplyRequest->requester_id === auth()->id())
                                            <div class="d-flex gap-1 mt-1">
                                                <input name="receipt_notes" form="receive-issuance-{{ $issuance->id }}" class="form-control form-control-sm" maxlength="1000" placeholder="Optional receipt notes">
                                                <button type="submit" form="receive-issuance-{{ $issuance->id }}" class="btn btn-success btn-sm text-nowrap"><i class="bi bi-check2-circle me-1"></i>Receive</button>
                                            </div>
                                        @else
                                            <div class="text-warning"><i class="bi bi-clock me-1"></i>Awaiting requester receipt</div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-muted">Not yet issued</span>
                                @endforelse
                            </td>
                            <td>{{ $requestItem->notes ?? '-' }}</td>
                            @if($canProcess && in_array($supplyRequest->status, ['APPROVED','PARTIALLY_ISSUED']))
                                <td class="text-center">@if($requestItem->remaining_quantity > 0)<a href="{{ route('stock-out.create', ['request_item' => $requestItem->id]) }}" class="btn btn-success btn-sm text-nowrap"><i class="bi bi-box-arrow-up me-1"></i>Issue</a>@else<span class="text-success small"><i class="bi bi-check-circle"></i> Done</span>@endif</td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($canProcess && $supplyRequest->status === 'PENDING')
                <div class="row g-2 mt-2"><div class="col-md-8"><textarea name="review_notes" class="form-control form-control-sm" rows="2" placeholder="Optional approval notes"></textarea></div><div class="col-md-4 d-flex justify-content-end align-items-end"><button class="btn btn-primary btn-sm"><i class="bi bi-check-circle me-1"></i>Approve Request</button></div></div>
            @endif
        </form>
        @if($supplyRequest->requester_id === auth()->id())
            @foreach($supplyRequest->items as $requestItem)
                @foreach($requestItem->issuances->whereNull('received_at') as $issuance)
                    <form id="receive-issuance-{{ $issuance->id }}" method="POST" action="{{ route('supply-requests.receive', [$supplyRequest, $issuance]) }}">@csrf</form>
                @endforeach
            @endforeach
        @endif
        @if($canProcess && $supplyRequest->status === 'PENDING')
            <form method="POST" action="{{ route('supply-requests.reject', $supplyRequest) }}" class="border-top mt-3 pt-3">@csrf<label class="form-label small">Reason for rejection</label><div class="d-flex gap-2"><input name="review_notes" required maxlength="1000" class="form-control form-control-sm" placeholder="Required reason"><button class="btn btn-outline-danger btn-sm text-nowrap"><i class="bi bi-x-circle me-1"></i>Reject</button></div></form>
        @endif
        @if($supplyRequest->requester_id === auth()->id() && $supplyRequest->status === 'PENDING')
            <form method="POST" action="{{ route('supply-requests.cancel', $supplyRequest) }}" class="mt-3 text-end">@csrf<button class="btn btn-outline-secondary btn-sm">Cancel My Request</button></form>
        @endif
    </div>
</div>
@endsection
