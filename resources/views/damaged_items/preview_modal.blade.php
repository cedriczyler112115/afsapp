<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Damaged Items Summary & Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h5>REPORT OF DAMAGED ITEMS</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Reported By</th>
                                <th>Item</th>
                                <th>Serial/Code</th>
                                <th>Date Reported</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($issuances as $issuance)
                                @foreach($issuance->itemUnits as $unit)
                                <tr>
                                    <td>{{ $issuance->receiver_name }}</td>
                                    <td>{{ $unit->item->item_name }}</td>
                                    <td>{{ $unit->serial ?? $unit->full_code }}</td>
                                    <td>{{ $issuance->date_issued->format('Y-m-d') }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <label for="issuance-purpose" class="form-label fw-bold">Purpose / Remarks:</label>
                    <textarea class="form-control" id="issuance-purpose" rows="3" placeholder="Enter purpose or remarks for this report..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="confirm-print">Confirm & Print</button>
            </div>
        </div>
    </div>
</div>
