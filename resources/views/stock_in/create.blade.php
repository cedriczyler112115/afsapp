@extends('layouts.app')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Add Stock (Stock In)</span>
        <a class="btn btn-secondary btn-sm ms-2" href="{{ route('stock-in.index') }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <div class="card-body">
        <form id="stockInForm" autocomplete="off">
            @csrf
            <!-- Item Selection -->
            <div class="mb-4">
                <label for="item_id" class="form-label fw-bold">Item Name <span class="text-danger">*</span></label>
                <select class="form-select border p-2" id="item_id" name="item_id" required>
                    <option value="">Select Item</option>
                    @foreach($items as $item)
                        <option value="{{ $item->item_id }}">{{ $item->item_name }} ({{ $item->sku }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Item Info (Read-only) -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 text-secondary fw-bold"><i class="bi bi-info-circle me-2"></i>Item Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Item Name -->
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center mb-1 text-muted">
                                <i class="bi bi-box-seam me-2"></i>
                                <small class="fw-bold text-uppercase">Item Name</small>
                            </div>
                            <div class="ps-4">
                                <h6 class="fw-bold text-dark mb-0" id="info_item_name">-</h6>
                            </div>
                        </div>
                        <!-- SKU -->
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center mb-1 text-muted">
                                <i class="bi bi-upc-scan me-2"></i>
                                <small class="fw-bold text-uppercase">SKU</small>
                            </div>
                            <div class="ps-4">
                                <h6 class="fw-bold text-dark mb-0" id="info_sku">-</h6>
                            </div>
                        </div>
                        <!-- Category -->
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center mb-1 text-muted">
                                <i class="bi bi-tags me-2"></i>
                                <small class="fw-bold text-uppercase">Category</small>
                            </div>
                            <div class="ps-4">
                                <h6 class="fw-bold text-dark mb-0" id="info_category">-</h6>
                            </div>
                        </div>
                        <!-- Unit -->
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center mb-1 text-muted">
                                <i class="bi bi-rulers me-2"></i>
                                <small class="fw-bold text-uppercase">Unit</small>
                            </div>
                            <div class="ps-4">
                                <h6 class="fw-bold text-dark mb-0" id="info_unit">-</h6>
                            </div>
                        </div>
                        <!-- Current Quantity -->
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center mb-1 text-muted">
                                <i class="bi bi-layers me-2"></i>
                                <small class="fw-bold text-uppercase">Current Quantity</small>
                            </div>
                            <div class="ps-4">
                                <h6 class="fw-bold text-dark mb-0" id="info_current_quantity">-</h6>
                            </div>
                        </div>
                        <!-- Description -->
                        <div class="col-12">
                            <div class="d-flex align-items-center mb-1 text-muted">
                                <i class="bi bi-file-text me-2"></i>
                                <small class="fw-bold text-uppercase">Description</small>
                            </div>
                            <div class="ps-4">
                                <p class="text-secondary mb-0" id="info_description">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item Units Table -->
            <h6 class="mb-3">Item Units</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="unitsTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Serial</th>
                            <th>Full Code</th>
                            <th>QR Code</th>
                            <th style="width: 50px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic Rows -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">
                                <button type="button" class="btn btn-sm btn-info" id="addRowBtn">
                                    <i class="bi bi-plus-lg me-1"></i> Add Unit
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle me-1"></i>Save Stock</button>
                {{-- <a class="btn btn-secondary btn-sm ms-2" href="{{ route('stock-in.index') }}"><i class="bi bi-arrow-left me-1"></i>Back</a> --}}
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let unitIndex = 0;

        // Initialize Select2
        $('#item_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select Item',
            allowClear: true
        });

        // Add initial row
        addUnitRow();

        // Item Selection Change
        $('#item_id').change(function() {
            const itemId = $(this).val();
            if (itemId) {
                // Fetch Item Details
                $.ajax({
                    url: '/stock-in/item/' + itemId,
                    type: 'GET',
                    success: function(data) {
                        $('#info_item_name').text(data.item_name || '-');
                        $('#info_sku').text(data.sku || '-');
                        $('#info_category').text(data.category || '-');
                        $('#info_unit').text(data.unit || '-');
                        $('#info_current_quantity').text(data.current_quantity || '0');
                        $('#info_description').text(data.description || '-');
                    },
                    error: function() {
                        toastr.error('Failed to fetch item details');
                        clearInfoFields();
                    }
                });
            } else {
                clearInfoFields();
            }
        });

        function clearInfoFields() {
            $('#info_item_name').text('-');
            $('#info_sku').text('-');
            $('#info_category').text('-');
            $('#info_unit').text('-');
            $('#info_current_quantity').text('-');
            $('#info_description').text('-');
        }

        // Add Row Button
        $('#addRowBtn').click(function() {
            addUnitRow();
        });

        // Remove Row Button (Delegated)
        $('#unitsTable').on('click', '.remove-row', function() {
            if ($('#unitsTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                toastr.warning('At least one unit is required.');
            }
        });

        function addUnitRow() {
            const html = `
                <tr>
                    <td>
                        <input type="text" name="units[${unitIndex}][serial]" class="form-control border px-2" placeholder="Serial">
                    </td>
                    <td>
                        <input type="text" name="units[${unitIndex}][full_code]" class="form-control border px-2" placeholder="Full Code">
                    </td>
                    <td>
                        <input type="text" name="units[${unitIndex}][qr_code]" class="form-control border px-2" placeholder="QR Code">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-row mb-0"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            `;
            $('#unitsTable tbody').append(html);
            unitIndex++;
        }

        // Form Submission
        $('#stockInForm').submit(function(e) {
            e.preventDefault();
            
            // Frontend Validation: Check if item is selected
            if (!$('#item_id').val()) {
                toastr.error('Please select an item.');
                return;
            }

            // Check if at least one row exists
            if ($('#unitsTable tbody tr').length === 0) {
                toastr.error('Please add at least one unit.');
                return;
            }

            // Per-row validation: each row must have at least one identifier
            let invalidRows = [];
            $('#unitsTable tbody tr').each(function(index) {
                const serial = $(this).find('input[name^="units"][name$="[serial]"]').val()?.trim() || '';
                const fullCode = $(this).find('input[name^="units"][name$="[full_code]"]').val()?.trim() || '';
                const qrCode = $(this).find('input[name^="units"][name$="[qr_code]"]').val()?.trim() || '';
                if (!serial && !fullCode && !qrCode) {
                    invalidRows.push(index + 1);
                }
            });
            if (invalidRows.length > 0) {
                toastr.error('Each row must have at least one of Serial, Full Code, or QR Code. Invalid rows: ' + invalidRows.join(', '));
                return;
            }

            $.ajax({
                url: "{{ route('stock-in.store') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $.alert({
                            title: 'Success!',
                            content: response.message,
                            type: 'green',
                            buttons: {
                                ok: {
                                    text: 'OK',
                                    btnClass: 'btn-green',
                                    action: function() {
                                        window.location.href = "{{ route('stock-in.index') }}";
                                    }
                                }
                            }
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = '';
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errorMessage += value[0] + '<br>';
                        });
                    }
                    
                    $.alert({
                        title: 'Error!',
                        content: errorMessage,
                        type: 'red'
                    });
                }
            });
        });
    });
</script>
@endpush
