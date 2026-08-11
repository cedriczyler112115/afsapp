<div class="modal fade" id="issuanceModal" tabindex="-1" aria-labelledby="issuanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="issuanceModalLabel">Damage Report #{{ $issuance->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered mb-4">
                    <tbody>
                        <tr>
                            <th class="table-light" style="width: 20%">Reported By</th>
                            <td style="width: 30%">{{ $issuance->receiver_name ?? 'N/A' }}</td>
                            <th class="table-light" style="width: 20%">Remarks</th>
                            <td style="width: 30%">{{ $issuance->remarks ?? 'None' }}</td>
                        </tr>
                        <tr>
                            <th class="table-light">Date Reported</th>
                            <td colspan="3">{{ $issuance->date_issued ? $issuance->date_issued->format('F d, Y h:i A') : '-' }}</td>
                        </tr>
                    </tbody>
                </table>

                <h6 class="fw-bold text-secondary mb-3">Damaged Units</h6>
                @foreach($groupedUnits as $itemName => $units)
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>Item:</strong> {{ $itemName }} <span class="badge bg-primary ms-2">{{ $units->count() }} record(s)</span>
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
                                            <th class="text-nowrap">PC/S per Box</th>
                                            <th class="text-nowrap">Reported By</th>
                                            <th class="text-nowrap">Damaged PC/S</th>
                                            <th class="text-nowrap">Remaining PC/S</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($units as $damage)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $damage->unit->serial ?? '-' }}</td>
                                                <td>{{ $damage->unit->full_code ?? '-' }}</td>
                                                <td>{{ $damage->unit->qr_code ?? '-' }}</td>
                                                <td>{{ number_format(max(1, (int) ($damage->item->pcs_per_unit ?? 1))) }}</td>
                                                <td>{{ $damage->issue_mode === 'PCS' ? 'PC/S' : 'Box' }}</td>
                                                <td>{{ number_format((int) $damage->quantity) }}</td>
                                                <td>{{ number_format(max(0, (int) ($damage->pcs_after ?? 0))) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if(!empty($damagePhotos) && $damagePhotos->count())
                    <h6 class="fw-bold text-secondary mt-4 mb-3">Damage Photos</h6>
                    <div class="row g-3">
                        @foreach($damagePhotos as $photo)
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ $photo['url'] }}" target="_blank" class="d-block text-decoration-none">
                                    <img src="{{ $photo['url'] }}"
                                         alt="Damage photo"
                                         class="img-fluid rounded border"
                                         style="width: 100%; height: 180px; object-fit: cover;">
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
