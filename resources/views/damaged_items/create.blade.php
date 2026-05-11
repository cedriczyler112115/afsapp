@extends('layouts.app')
@section('content')
<style>
    .blink-badge {
        animation: blinker 1s linear infinite;
    }
    @keyframes blinker {
        50% {
            opacity: 0;
        }
    }
    .modal-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    /* Rotate accordion icon when expanded */
    .accordion-button-icon {
        transition: transform 0.2s ease-in-out;
    }
    .collapsed .accordion-button-icon {
        transform: rotate(-90deg);
    }
</style>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center bg-danger text-white">
        <span>Report Damage</span>
        <button onclick="history.go(-1)" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</button>
    </div>
    <div class="card-body">
        <form id="reportDamageForm" autocomplete="off">
            @csrf
            
            <!-- Details -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 text-secondary fw-bold"><i class="bi bi-info-circle me-2"></i>Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="remarks" class="form-label fw-bold">Describe the damage or reason of unserviceability</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="5" placeholder="Describe the damage or reason for unserviceability..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Damage Photos Upload -->
            <div class="mb-3">
                <label for="damage_photos" class="form-label fw-bold small mb-1">Damage Photos (optional)</label>
                <input type="file"
                       id="damage_photos"
                       name="damage_photos[]"
                       class="form-control form-control-sm"
                       accept="image/*"
                       multiple>
                <div class="form-text">Images only. You can select multiple photos.</div>
                <div id="damage_photos_preview" class="mt-2 d-flex flex-wrap gap-2"></div>
            </div>

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
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center collapsed" data-bs-toggle="collapse" data-bs-target="#itemInfoCollapse" style="cursor: pointer;">
                    <h6 class="mb-0 text-secondary fw-bold"><i class="bi bi-info-circle me-2"></i>Item Information</h6>
                    <i class="bi bi-chevron-down accordion-button-icon"></i>
                </div>
                <div id="itemInfoCollapse" class="collapse">
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
                        <!-- Remaining Stock -->
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center mb-1 text-muted">
                                <i class="bi bi-calculator me-2"></i>
                                <small class="fw-bold text-uppercase">REMAINING STOCK AFTER THIS REPORT</small>
                            </div>
                            <div class="ps-4 d-flex align-items-center">
                                <h6 class="fw-bold text-dark mb-0 me-2" id="info_remaining_stock">-</h6>
                                <span class="badge bg-danger d-none blink-badge" id="zero_stock_badge">No Stock Left</span>
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
            </div>

            <!-- Item Units Search and Table -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Select Units to Report as Damaged</h6>
                <button type="button" class="btn btn-primary btn-sm" id="btnShowItems">
                    <i class="bi bi-list-check me-1"></i>Show Items
                </button>
            </div>
            
            <div class="mb-3">
                <div class="input-group">
                    <input type="text" class="form-control" id="unit_search" placeholder="Scan or type unit code and press Enter..." disabled>
                    <button class="btn btn-outline-secondary" type="button" id="btnScanQr" disabled>
                        <i class="bi bi-qr-code-scan"></i>
                    </button>
                </div>
                <!-- Inline Alert Div -->
                <div id="unitSearchAlert" class="mt-2 d-none"></div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="unitsTable">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;">No.</th>
                            <th>Serial</th>
                            <th>Full Code</th>
                            <th>QR Code</th>
                            <th style="width: 50px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
                <div id="noRecordsFound" class="text-center text-muted p-3 d-none">No record found</div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-exclamation-triangle me-1"></i>Report Damage</button>
            </div>
        </form>
    </div>
</div>

<!-- Available Units Modal -->
<div class="modal fade" id="availableUnitsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Available Units</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="modalUnitsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">
                                    <input type="checkbox" id="selectAllModal" class="modal-checkbox">
                                </th>
                                <th>Serial</th>
                                <th>Full Code</th>
                                <th>QR Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dynamic Content -->
                        </tbody>
                    </table>
                </div>
                <div id="modalNoRecords" class="text-center text-muted p-3 d-none">No available units found</div>
            </div>
            <div class="modal-footer justify-content-between">
                <div class="d-flex align-items-center">
                     <button class="btn btn-outline-secondary" type="button" id="btnDecreaseQty"><i class="bi bi-dash"></i></button>
                     <input type="text" class="form-control text-center mx-1" id="inputAutoQty" value="0" style="width: 60px;" readonly>
                     <button class="btn btn-outline-secondary" type="button" id="btnIncreaseQty"><i class="bi bi-plus"></i></button>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btnAddSelectedUnits">Add Selected</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Scanner Modal -->
<div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scan QR/Barcode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="reader" style="width: 100%;"></div>
                <!-- Scanner Alert Container -->
                <div id="scannerAlert" class="mt-2 d-none"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    $(document).ready(function() {
        let unitIndex = 0;
        let addedUnitIds = [];
        let currentItemQuantity = 0;

            $('#damage_photos').on('change', function() {
                let preview = $('#damage_photos_preview');
                preview.empty();

                let files = this.files;
                if (!files || !files.length) {
                    return;
                }

                Array.from(files).forEach(function(file) {
                    if (!file.type.startsWith('image/')) {
                        return;
                    }

                    let reader = new FileReader();
                    reader.onload = function(e) {
                        let img = $('<img>').attr('src', e.target.result);
                        img.css({
                            width: '80px',
                            height: '80px',
                            objectFit: 'cover',
                            borderRadius: '4px',
                            border: '1px solid #dee2e6'
                        });
                        preview.append(img);
                    };
                    reader.readAsDataURL(file);
                });
            });

        // Initialize Select2
        $('#item_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select Item',
            allowClear: true
        });

        // Item Selection Change
        $('#item_id').change(function() {
            const itemId = $(this).val();
            
            // Clear existing rows
            $('#unitsTable tbody').empty();
            $('#noRecordsFound').addClass('d-none');
            unitIndex = 0;
            addedUnitIds = [];
            
            if (itemId) {
                $('#unit_search').prop('disabled', false).focus();
                $('#btnScanQr').prop('disabled', false);
                
                // Fetch Item Details
                $.ajax({
                    url: "{{ route('stock-in.get-item', ['id' => 'ITEM_ID_PLACEHOLDER']) }}".replace('ITEM_ID_PLACEHOLDER', itemId),
                    type: 'GET',
                    success: function(data) {
                        $('#info_item_name').text(data.item_name || '-');
                        $('#info_sku').text(data.sku || '-');
                        $('#info_category').text(data.category || '-');
                        $('#info_unit').text(data.unit || '-');
                        currentItemQuantity = parseInt(data.current_quantity) || 0;
                        $('#info_current_quantity').text(data.current_quantity || '0');
                        updateRemainingStock();
                        $('#info_description').text(data.description || '-');
                    },
                    error: function() {
                        toastr.error('Failed to fetch item details');
                        clearInfoFields();
                    }
                });
            } else {
                $('#unit_search').prop('disabled', true);
                $('#btnScanQr').prop('disabled', true);
                clearInfoFields();
            }
        });

        function clearInfoFields() {
            currentItemQuantity = 0;
            $('#info_item_name').text('-');
            $('#info_sku').text('-');
            $('#info_category').text('-');
            $('#info_unit').text('-');
            $('#info_current_quantity').text('-');
            $('#info_remaining_stock').text('-');
            $('#info_description').text('-');
            $('#zero_stock_badge').addClass('d-none');
        }

        // Show Items Button Logic
        $('#btnShowItems').click(function() {
            const itemId = $('#item_id').val();
            if (!itemId) {
                toastr.warning('Please select an item first.');
                return;
            }

            $.ajax({
                url: "{{ route('damaged-items.units', ['item_id' => 'ITEM_ID_PLACEHOLDER']) }}".replace('ITEM_ID_PLACEHOLDER', itemId),
                type: 'GET',
                success: function(units) {
                    let tbody = $('#modalUnitsTable tbody');
                    tbody.empty();
                    let count = 0;

                    units.forEach(function(unit) {
                        // Skip if already added
                        if (addedUnitIds.includes(unit.id)) {
                            return;
                        }

                        // Use simple quotes escaping for JSON string
                        let unitJson = JSON.stringify(unit).replace(/'/g, "&#39;");

                        let row = `
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="unit-checkbox modal-checkbox" value="${unit.id}" data-unit='${unitJson}'>
                                </td>
                                <td>${unit.serial || '-'}</td>
                                <td>${unit.full_code || '-'}</td>
                                <td>${unit.qr_code || '-'}</td>
                            </tr>
                        `;
                        tbody.append(row);
                        count++;
                    });

                    if (count === 0) {
                        $('#modalNoRecords').removeClass('d-none');
                        $('#modalUnitsTable').addClass('d-none');
                    } else {
                        $('#modalNoRecords').addClass('d-none');
                        $('#modalUnitsTable').removeClass('d-none');
                    }
                    
                    $('#selectAllModal').prop('checked', false);
                    $('#inputAutoQty').val(0);
                    $('#availableUnitsModal').modal('show');
                },
                error: function() {
                    toastr.error('Failed to fetch available units.');
                }
            });
        });

        // Sync Logic for Quantity Input and Checkboxes
        function updateQtyInput() {
             let checkedCount = $('.unit-checkbox:checked').length;
             $('#inputAutoQty').val(checkedCount);
        }

        function updateCheckboxesFromQty() {
             let qty = parseInt($('#inputAutoQty').val()) || 0;
             let checkboxes = $('.unit-checkbox');
             
             checkboxes.each(function(index) {
                 $(this).prop('checked', index < qty);
             });
             
             // Update Select All state
             let allChecked = checkboxes.length > 0 && checkboxes.length === qty;
             $('#selectAllModal').prop('checked', allChecked);
        }

        // Increase Qty
        $('#btnIncreaseQty').click(function() {
             let currentQty = parseInt($('#inputAutoQty').val()) || 0;
             let maxQty = $('.unit-checkbox').length;
             
             if (currentQty < maxQty) {
                 $('#inputAutoQty').val(currentQty + 1);
                 updateCheckboxesFromQty();
             }
        });

        // Decrease Qty
        $('#btnDecreaseQty').click(function() {
             let currentQty = parseInt($('#inputAutoQty').val()) || 0;
             
             if (currentQty > 0) {
                 $('#inputAutoQty').val(currentQty - 1);
                 updateCheckboxesFromQty();
             }
        });
        
        // Listen to individual checkbox changes to update Qty
        $(document).on('change', '.unit-checkbox', function() {
             updateQtyInput();
             
             // Also update Select All
             let total = $('.unit-checkbox').length;
             let checked = $('.unit-checkbox:checked').length;
             $('#selectAllModal').prop('checked', total > 0 && total === checked);
        });

        // Select All in Modal
        $('#selectAllModal').change(function() {
            $('.unit-checkbox').prop('checked', $(this).prop('checked'));
            updateQtyInput();
        });

        // Add Selected Units from Modal
        $('#btnAddSelectedUnits').click(function() {
            let selectedCount = 0;
            $('.unit-checkbox:checked').each(function() {
                let unit = $(this).data('unit');
                addUnitToTable(unit);
                selectedCount++;
            });
            
            if (selectedCount > 0) {
                $('#availableUnitsModal').modal('hide');
                toastr.success(`${selectedCount} unit(s) added.`);
            } else {
                toastr.warning('No units selected.');
            }
        });

        // Unit Search (Enter key)
        $('#unit_search').keypress(function(e) {
            if (e.which == 13) {
                e.preventDefault();
                searchAndAddUnit($(this).val());
            }
        });

        function searchAndAddUnit(query) {
            if (!query) return;
            
            const itemId = $('#item_id').val();
            
            $.ajax({
                url: "{{ route('damaged-items.find-unit') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    item_id: itemId,
                    query: query
                },
                success: function(response) {
                    if (response.success) {
                        if (addedUnitIds.includes(response.unit.id)) {
                            showInlineAlert('warning', 'Unit already added to the list.');
                        } else {
                            addUnitToTable(response.unit);
                            $('#unit_search').val('');
                            showInlineAlert('success', 'Unit added successfully.');
                        }
                    } else {
                        showInlineAlert('danger', response.message);
                    }
                },
                error: function(xhr) {
                    let msg = 'Error searching unit.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                         msg = xhr.responseJSON.message;
                    }
                    showInlineAlert('danger', msg);
                }
            });
        }

        function showInlineAlert(type, message) {
            let alertClass = 'alert-' + type;
            // Simple alert using bootstrap classes
            let html = `<div class="alert ${alertClass} alert-dismissible fade show py-1 px-3 mb-0" role="alert">
                          ${message}
                          <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>`;
            $('#unitSearchAlert').removeClass('d-none').html(html);
            
            // Auto hide after 3 seconds
            setTimeout(function() {
                $('#unitSearchAlert').addClass('d-none').empty();
            }, 3000);
        }

        function addUnitToTable(unit) {
            if (addedUnitIds.includes(unit.id)) return;

            addedUnitIds.push(unit.id);
            unitIndex++;

            let row = `
                <tr id="row-${unit.id}">
                    <td>${unitIndex}</td>
                    <td>
                        <input type="hidden" name="units[${unitIndex}][unit_id]" value="${unit.id}">
                        ${unit.serial || '-'}
                    </td>
                    <td>${unit.full_code || '-'}</td>
                    <td>${unit.qr_code || '-'}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-unit" data-id="${unit.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#unitsTable tbody').append(row);
            $('#noRecordsFound').addClass('d-none');
            updateRemainingStock();
        }

        // Remove Unit
        $(document).on('click', '.remove-unit', function() {
            let id = $(this).data('id');
            // Remove from array
            addedUnitIds = addedUnitIds.filter(uid => uid !== id);
            // Remove row
            $(`#row-${id}`).remove();
            
            // Re-index logic if strictly needed, but simple counter is fine for now or re-render
            // For submitting, the index key in name="units[index]" doesn't strictly need to be sequential 0..N, just unique.
            // But if we want to be clean, we can leave it.
            
            if (addedUnitIds.length === 0) {
                $('#noRecordsFound').removeClass('d-none');
            }
            updateRemainingStock();
        });

        function updateRemainingStock() {
            let count = addedUnitIds.length;
            let remaining = currentItemQuantity - count;
            
            $('#info_remaining_stock').text(remaining);
            if (remaining <= 0 && currentItemQuantity > 0) { // Only show warning if we actually have stock but used it all up
                 if(remaining < 0) $('#info_remaining_stock').text('0 (Over limit)'); // Should not happen with validation
                 $('#zero_stock_badge').removeClass('d-none');
            } else {
                 $('#zero_stock_badge').addClass('d-none');
            }
        }

        // Handle Form Submission
        $('#reportDamageForm').submit(function(e) {
            e.preventDefault();

            if (addedUnitIds.length === 0) {
                toastr.warning('Please add at least one unit.');
                return;
            }

            let form = this;

            $.confirm({
                title: 'Confirm Damage Report',
                content: 'Are you sure you want to report these items as damaged? This action cannot be undone easily.',
                type: 'red',
                icon: 'bi bi-exclamation-triangle',
                buttons: {
                    confirm: {
                        text: 'Yes, Report It',
                        btnClass: 'btn-danger',
                        action: function () {
                            let formData = new FormData(form);

                            $.ajax({
                                url: "{{ route('damaged-items.store') }}",
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    toastr.success('Damage report submitted successfully!');
                                    setTimeout(function() {
                                        window.location.href = "{{ route('damaged-items.index') }}";
                                    }, 1500);
                                },
                                error: function(xhr) {
                                    if (xhr.status === 422) {
                                        let errors = xhr.responseJSON.errors;
                                        let msg = '';
                                        $.each(errors, function(key, value) {
                                            msg += value + '<br>';
                                        });
                                        toastr.error(msg);
                                    } else {
                                        toastr.error('An error occurred. Please try again.');
                                    }
                                }
                            });
                        }
                    },
                    cancel: function () {
                        // canceled
                    }
                }
            });
        });

        // QR Scanner Logic
        let html5QrcodeScanner = null;

        $('#btnScanQr').click(function() {
            $('#scannerModal').modal('show');
            startScanner();
        });

        $('#scannerModal').on('hidden.bs.modal', function () {
            stopScanner();
        });

        function startScanner() {
            if (html5QrcodeScanner) {
                // Already running
                return;
            }
            
            html5QrcodeScanner = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            html5QrcodeScanner.start({ facingMode: "environment" }, config, onScanSuccess)
            .catch(err => {
                $('#scannerAlert').removeClass('d-none').html(`<div class="alert alert-danger">Camera error: ${err}</div>`);
            });
        }

        function stopScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner.clear();
                    html5QrcodeScanner = null;
                }).catch(err => {
                    console.log("Failed to stop scanner", err);
                });
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            // Play sound or feedback?
            // Search for the unit
            searchAndAddUnit(decodedText);
            
            // Optional: Close modal on success? Or keep open for multiple scans?
            // For now, let's keep open but show feedback in the modal?
            // Actually, searchAndAddUnit shows feedback on the main page.
            // Let's show a toastr on success so user knows it worked without closing modal
             // But we need to know if it worked. searchAndAddUnit is async.
             // Refactoring searchAndAddUnit to return promise or handle callback would be better, but for now:
             // Let's just close modal for simple workflow
             $('#scannerModal').modal('hide');
        }
    });
</script>
@endpush
