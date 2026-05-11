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
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Stock Out (Issuance)</span>
        <button onclick="history.go(-1)" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</button>
    </div>
    <div class="card-body">
        <form id="stockOutForm" autocomplete="off">
            @csrf
            
            <!-- Issuance Details -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 text-secondary fw-bold"><i class="bi bi-person-badge me-2"></i>Issuance Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="user_id" class="form-label fw-bold">Issued To (Receiver) <span class="text-danger">*</span></label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">Select Receiver</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="remarks" class="form-label fw-bold">Remarks / Issued to (Write the accountable person)</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="1" placeholder="Optional remarks..."></textarea>
                        </div>
                    </div>
                </div>
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
                                <small class="fw-bold text-uppercase">REMAINING STOCK AFTER THIS ISSUANCE</small>
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
                <h6 class="mb-0">Select Units to Issue</h6>
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
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle me-1"></i>Confirm Issuance</button>
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

        // Initialize Select2
        $('#item_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select Item',
            allowClear: true
        });

        $('#user_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select Receiver',
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
                url: "{{ route('stock-out.units', ['item_id' => 'ITEM_ID_PLACEHOLDER']) }}".replace('ITEM_ID_PLACEHOLDER', itemId),
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
                // Ensure unit is an object if it came back as string (though data() usually handles JSON parsing if formatted correctly)
                if (typeof unit === 'string') {
                    unit = JSON.parse(unit);
                }
                addUnitRow(unit);
                selectedCount++;
            });

            if (selectedCount > 0) {
                $('#availableUnitsModal').modal('hide');
                toastr.success(selectedCount + ' unit(s) added.');
                $('#noRecordsFound').addClass('d-none');
            } else {
                toastr.warning('No units selected.');
            }
        });

        // Scanner Logic
        let html5QrcodeScanner = null;
        let audioCtx = null;

        $('#btnScanQr').on('click touchstart', function() {
            // Initialize AudioContext on user interaction to bypass autoplay policy
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            
            // Resume if suspended
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            
            // Play a silent oscillator to truly wake up the audio engine on mobile
            try {
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();
                oscillator.type = 'sine';
                oscillator.frequency.value = 440; // Standard A4
                gainNode.gain.value = 0; // Silent
                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);
                oscillator.start(0);
                oscillator.stop(audioCtx.currentTime + 0.001);
            } catch (e) {
                // console.error("Audio unlock failed", e);
            }

            // Check for secure context (HTTPS or localhost)
            if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1' && !location.hostname.endsWith('.test')) {
                 // Note: .test domains in Herd *can* be http, but camera requires https. 
                 // However, browser simply checks window.isSecureContext.
            }

            if (!window.isSecureContext) {
                 $.alert({
                    title: '<i class="bi bi-shield-lock-fill text-danger"></i> Camera Access Error',
                    content: 'Browsers block camera access on insecure connections (HTTP).<br><br>' +
                             '<strong>Solution for Laravel Herd:</strong><br>' +
                             '1. Open your terminal.<br>' +
                             '2. Run: <code>herd secure</code><br>' +
                             '3. Reload this page using <strong>HTTPS</strong>.',
                    type: 'red',
                    boxWidth: '400px',
                    useBootstrap: false,
                    buttons: {
                        ok: {
                            text: 'Got it',
                            btnClass: 'btn-red'
                        }
                    }
                });
                return;
            }

            $('#scannerModal').modal('show');
            clearScannerAlert();
            
            // Initialize scanner when modal opens
            if (!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner(
                    "reader", 
                    { 
                        fps: 30, 
                        qrbox: {width: 150, height: 150},
                        experimentalFeatures: {
                            useBarCodeDetectorIfSupported: true
                        }
                    },
                    /* verbose= */ false
                );
            }
            
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        });

        function onScanSuccess(decodedText, decodedResult) {
            // Handle the scanned code without closing the modal
            
            let query = decodedText.trim();
            if (!query) return;

            // Play beep sound
            playScanSound();

            // Clear previous alerts
            clearScannerAlert();

            // Client-side duplicate check
            let isDuplicate = false;
            $('#unitsTable tbody tr').each(function() {
                let serial = $(this).find('td:eq(1)').text().trim();
                let fullCode = $(this).find('td:eq(2)').text().trim();
                let qrCode = $(this).find('td:eq(3)').text().trim();

                if ((serial !== '-' && query === serial) || 
                    (fullCode !== '-' && query === fullCode) || 
                    (qrCode !== '-' && query === qrCode)) {
                    isDuplicate = true;
                    return false;
                }
            });

            if (isDuplicate) {
                showScannerAlert('This unit is already added.', 'warning');
                return;
            }

            // Backend Check
            let itemId = $('#item_id').val();
            $.ajax({
                url: "{{ route('stock-out.find-unit') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    item_id: itemId,
                    query: query
                },
                success: function(response) {
                    if (response.success) {
                        addUnitRow(response.unit);
                        showScannerAlert('Unit added successfully: ' + (response.unit.serial || response.unit.qr_code), 'success');
                    } else {
                        showScannerAlert(response.message || 'Item not found.', 'danger');
                    }
                },
                error: function(xhr) {
                    showScannerAlert('Error searching for unit.', 'danger');
                }
            });
        }

        function onScanFailure(error) {
            // handle scan failure, usually better to ignore and keep scanning.
        }

        // Stop scanner when modal is closed
        $('#scannerModal').on('hidden.bs.modal', function () {
            if (html5QrcodeScanner) {
                 html5QrcodeScanner.clear().catch(error => {
                    // console.error("Failed to clear html5QrcodeScanner. ", error);
                });
            }
        });

        // Helper to show scanner alerts
        let scannerAlertTimeout;
        function showScannerAlert(message, type) {
            let alertClass = 'alert-' + type;
            let icon = '';
            
            if (type === 'success') icon = '<i class="bi bi-check-circle-fill me-2"></i>';
            else if (type === 'danger') icon = '<i class="bi bi-x-circle-fill me-2"></i>';
            else if (type === 'warning') icon = '<i class="bi bi-exclamation-triangle-fill me-2"></i>';

            // Clear previous timeout
            if (scannerAlertTimeout) {
                clearTimeout(scannerAlertTimeout);
                scannerAlertTimeout = null;
            }

            $('#scannerAlert')
                .removeClass('d-none alert-success alert-danger alert-warning alert-info')
                .addClass('alert ' + alertClass)
                .html(icon + message)
                .show()
                .css('opacity', 1);

            // Auto fade out after 3 seconds
            scannerAlertTimeout = setTimeout(function() {
                $('#scannerAlert').fadeOut(function() {
                    $(this).addClass('d-none');
                });
            }, 3000);
        }

        function clearScannerAlert() {
            if (scannerAlertTimeout) {
                clearTimeout(scannerAlertTimeout);
                scannerAlertTimeout = null;
            }
            $('#scannerAlert').addClass('d-none').empty();
        }

        // Play Beep Sound
        function playScanSound() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(1500, audioCtx.currentTime); // 1500Hz beep
            
            gainNode.gain.setValueAtTime(1.0, audioCtx.currentTime); // Maximum Volume
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            oscillator.start();
            oscillator.stop(audioCtx.currentTime + 0.1); // Beep duration 0.1s
        }

        // Helper to show inline alerts below unit search
        let alertTimeout;
        function showUnitSearchAlert(message, type, autoDismiss = true) {
            // Types: success, danger, warning, info
            let alertClass = 'alert-' + type;
            let icon = '';
            
            if (type === 'success') icon = '<i class="bi bi-check-circle-fill me-2"></i>';
            else if (type === 'danger') icon = '<i class="bi bi-x-circle-fill me-2"></i>';
            else if (type === 'warning') icon = '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
            else icon = '<i class="bi bi-info-circle-fill me-2"></i>';

            // Clear previous timeout if exists
            if (alertTimeout) {
                clearTimeout(alertTimeout);
                alertTimeout = null;
            }

            $('#unitSearchAlert')
                .removeClass('d-none alert-success alert-danger alert-warning alert-info')
                .addClass('alert ' + alertClass)
                .html(icon + message)
                .show()
                .css('opacity', 1);

            if (autoDismiss) {
                alertTimeout = setTimeout(function() {
                    $('#unitSearchAlert').fadeOut(function() {
                        $(this).addClass('d-none');
                    });
                }, 3000);
            }
        }

        function clearUnitSearchAlert() {
            if (alertTimeout) {
                clearTimeout(alertTimeout);
                alertTimeout = null;
            }
            $('#unitSearchAlert').addClass('d-none').empty();
        }

        // Search Input Keypress (Enter)
        $('#unit_search').keypress(function(e) {
            if (e.which == 13) {
                e.preventDefault();
                let query = $(this).val().trim();
                let itemId = $('#item_id').val();

                if (!query) return;

                clearUnitSearchAlert(); // Clear previous alerts

                // Client-side duplicate check: Check if unit is already in the table
                let isDuplicate = false;
                $('#unitsTable tbody tr').each(function() {
                    let serial = $(this).find('td:eq(1)').text().trim();
                    let fullCode = $(this).find('td:eq(2)').text().trim();
                    let qrCode = $(this).find('td:eq(3)').text().trim();

                    // Check exact match against any of the visible identifiers
                    if ((serial !== '-' && query === serial) || 
                        (fullCode !== '-' && query === fullCode) || 
                        (qrCode !== '-' && query === qrCode)) {
                        isDuplicate = true;
                        return false; // break loop
                    }
                });

                if (isDuplicate) {
                    showUnitSearchAlert('This unit is already exists in the table.', 'danger');
                    $('#unit_search').val('');
                    return;
                }

                $.ajax({
                    url: "{{ route('stock-out.find-unit') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        item_id: itemId,
                        query: query
                    },
                    success: function(response) {
                        if (response.success) {
                            addUnitRow(response.unit);
                            $('#unit_search').val('');
                            $('#noRecordsFound').addClass('d-none');
                            // Optional: Show success message briefly or just rely on the row appearing
                            showUnitSearchAlert('Unit added successfully.', 'success');
                            // setTimeout(clearUnitSearchAlert, 3000); // Handled by showUnitSearchAlert
                        } else {
                            showUnitSearchAlert(response.message || 'Item not found. Please try again.', 'danger');
                            // If table is empty, show "No record found" message
                            if ($('#unitsTable tbody tr').length === 0) {
                                $('#noRecordsFound').removeClass('d-none');
                            }
                        }
                    },
                    error: function(xhr) {
                        showUnitSearchAlert('Error searching for unit.', 'danger');
                    }
                });
            }
        });

        function addUnitRow(unit) {
            if (addedUnitIds.includes(unit.id)) {
                showUnitSearchAlert('This unit is already added.', 'warning');
                return;
            }

            addedUnitIds.push(unit.id);

            let rowNumber = $('#unitsTable tbody tr').length + 1;

            const html = `
                <tr>
                    <td class="text-center fw-bold">${rowNumber}</td>
                    <td>${unit.serial || '-'}</td>
                    <td>${unit.full_code || '-'}</td>
                    <td>${unit.qr_code || '-'}</td>
                    <td class="text-center">
                        <input type="hidden" name="units[${unitIndex}][unit_id]" value="${unit.id}">
                        <button type="button" class="btn btn-sm btn-danger remove-row mb-0" data-id="${unit.id}"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            `;
            $('#unitsTable tbody').append(html);
            unitIndex++;
            updateRemainingStock();
        }

        // Remove Row Button (Delegated)
        $('#unitsTable').on('click', '.remove-row', function() {
            let unitId = $(this).data('id');
            $(this).closest('tr').remove();
            
            // Remove from added IDs array
            addedUnitIds = addedUnitIds.filter(id => id !== unitId);
            
            // Re-index rows
            $('#unitsTable tbody tr').each(function(index) {
                $(this).find('td:eq(0)').text(index + 1);
            });
            
            updateRemainingStock();

            if ($('#unitsTable tbody tr').length === 0) {
                 $('#noRecordsFound').removeClass('d-none');
            }
        });

        function updateRemainingStock() {
            let issuedCount = addedUnitIds.length;
            let remaining = currentItemQuantity - issuedCount;
            $('#info_remaining_stock').text(remaining);

            // Show badge if remaining is 0 or less, BUT only if an item is actually selected
            // checking $('#item_id').val() ensures we don't show it when form is cleared
            if (remaining <= 0 && $('#item_id').val()) {
                $('#zero_stock_badge').removeClass('d-none');
            } else {
                $('#zero_stock_badge').addClass('d-none');
            }
        }

        // Form Submission
        $('#stockOutForm').submit(function(e) {
            e.preventDefault();
            
            // Basic Validation
            if ($('#unitsTable tbody tr').length === 0) {
                toastr.warning('Please add at least one unit.');
                return;
            }

            if (!$('#user_id').val()) {
                toastr.warning('Please select a receiver.');
                return;
            }

            let itemName = $('#info_item_name').text();
            let totalQty = $('#unitsTable tbody tr').length;
            let receiverName = $('#user_id option:selected').text();
            let remarks = $('#remarks').val() || 'None';

            let content = `
                <div class="mb-2"><strong>Item:</strong> ${itemName}</div>
                <div class="mb-2"><strong>Total Quantity:</strong> ${totalQty}</div>
                <div class="mb-2"><strong>Receiver:</strong> ${receiverName}</div>
                <div class="mb-2"><strong>Remarks:</strong> ${remarks}</div>
                <div class="alert alert-warning mt-3"><i class="bi bi-exclamation-triangle me-2"></i>Are you sure you want to proceed with this issuance?</div>
            `;

            $.confirm({
                title: 'Confirm Issuance',
                content: content,
                type: 'blue',
                columnClass: 'medium',
                buttons: {
                    confirm: {
                        text: 'Confirm & Issue',
                        btnClass: 'btn-blue',
                        action: function () {
                            submitStockOut();
                        }
                    },
                    cancel: function () {
                        // cancel
                    }
                }
            });
        });

        function submitStockOut() {
            let formData = $('#stockOutForm').serialize();

            $.ajax({
                url: "{{ route('stock-out.store') }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    // toastr.success(response.success);
                    window.location.href = "{{ route('stock-out.index') }}";
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = '';
                    if (errors) {
                         if (errors.error) {
                             errorMessage = errors.error[0];
                         } else {
                            $.each(errors, function(key, value) {
                                errorMessage += value[0] + '<br>';
                            });
                         }
                    } else {
                        errorMessage = 'An error occurred.';
                    }
                    toastr.error(errorMessage);
                }
            });
        }
    });
</script>
@endpush
