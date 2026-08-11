<!-- Modal -->
<div class="modal fade" id="issuanceModal" tabindex="-1" aria-labelledby="issuanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="issuanceModalLabel">Issuance Details #{{ $issuance->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Header Info -->
                <table class="table table-bordered mb-4">
                    <tbody>
                        <tr>
                            <th class="table-light" style="width: 20%">Receiver Name</th>
                            <td style="width: 30%">{{ $issuance->receiver_name ?? 'N/A' }}</td>
                            <th class="table-light" style="width: 20%">Remarks</th>
                            <td style="width: 30%">{{ $issuance->remarks ?? 'None' }}</td>
                        </tr>
                        <tr>
                            <th class="table-light">Date Released</th>
                            <td>{{ $issuance->date_issued->format('F d, Y h:i A') }}</td>
                            <th class="table-light">Supply Request</th>
                            <td>
                                @if($issuance->supplyRequestItem?->supplyRequest)
                                    <a href="{{ route('supply-requests.show', $issuance->supplyRequestItem->supplyRequest) }}">{{ $issuance->supplyRequestItem->supplyRequest->request_no }}</a>
                                @else
                                    Direct issuance
                                @endif
                            </td>
                        </tr>
                        @if($issuance->supplyRequestItem)
                        <tr>
                            <th class="table-light">Receipt Status</th>
                            <td colspan="3">
                                @if($issuance->received_at)
                                    <span class="badge bg-success">Received</span>
                                    by {{ $issuance->receivedByUser?->name ?? $issuance->receiver_name }} on {{ $issuance->received_at->format('F d, Y h:i A') }}
                                    @if($issuance->receipt_notes)<div class="text-muted mt-1">{{ $issuance->receipt_notes }}</div>@endif
                                @else
                                    <span class="badge bg-warning">Awaiting requester receipt</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                <!-- Issued Items -->
                <h6 class="fw-bold text-secondary mb-3">Issued Units</h6>
                
                @foreach($groupedUnits as $itemName => $transactions)
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>Item:</strong> {{ $itemName }} <span class="badge bg-primary ms-2">{{ $transactions->sum('quantity') }} PC/S issued</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0 text-center align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">No</th>
                                            <th>Serial</th>
                                            <th>Full Code</th>
                                            <th>QR Code</th>
                                            <th>Issued By</th>
                                            <th>PC/S Issued</th>
                                            <th>PC/S Remaining</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transactions as $transaction)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $transaction->unit->serial ?? '-' }}</td>
                                                <td>{{ $transaction->unit->full_code ?? '-' }}</td>
                                                <td>{{ $transaction->unit->qr_code ?? '-' }}</td>
                                                <td>{{ ($transaction->issue_mode ?? 'BOX') === 'BOX' ? 'Box' : 'PC/S' }}</td>
                                                <td>{{ number_format($transaction->quantity) }}</td>
                                                <td>{{ number_format($transaction->pcs_after ?? 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
