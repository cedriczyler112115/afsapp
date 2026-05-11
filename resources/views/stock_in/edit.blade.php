@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Stock List - {{ $item->item_name }} ({{ $item->sku }})</span>
        <a class="btn btn-secondary btn-sm ms-2" href="{{ route('stock-in.index') }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <div class="card-body">
        @if(session('success') || session('error') || $errors->any())
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
            @if(session('success'))
            <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
                <div class="d-flex">
                    <div class="toast-body">{{ session('success') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            @endif
            @if(session('error'))
            <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                <div class="d-flex">
                    <div class="toast-body">{{ session('error') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            @endif
            @if ($errors->any())
            <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="6000">
                <div class="d-flex">
                    <div class="toast-body">{{ $errors->first() }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            @endif
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var toasts = document.querySelectorAll('.toast');
                toasts.forEach(function (toastEl) {
                    var t = new bootstrap.Toast(toastEl);
                    t.show();
                });
            });
        </script>
        @endif

        <form action="{{ route('stock-in.transaction.store', $item->item_id) }}" method="POST" class="mb-3" autocomplete="off">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <label class="form-label">Serial</label>
                    <input type="text" name="serial" class="form-control form-control-sm" value="{{ old('serial') }}">
                </div>
                <div class="col-sm-4">
                    <label class="form-label">QR Code</label>
                    <input type="text" name="qr_code" class="form-control form-control-sm" value="{{ old('qr_code') }}">
                </div>
                <div class="col-sm-4 d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> Add Unit</button>
                    <button type="button" class="btn btn-success btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#bulkAddModal">
                        <i class="bi bi-boxes me-1"></i> Bulk Add
                    </button>
                </div>
            </div>
        </form>

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
                            <h6 class="fw-bold text-dark mb-0">{{ $item->item_name ?? '-' }}</h6>
                        </div>
                    </div>
                    <!-- SKU -->
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center mb-1 text-muted">
                            <i class="bi bi-upc-scan me-2"></i>
                            <small class="fw-bold text-uppercase">SKU</small>
                        </div>
                        <div class="ps-4">
                            <h6 class="fw-bold text-dark mb-0">{{ $item->sku ?? '-' }}</h6>
                        </div>
                    </div>
                    <!-- Category -->
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center mb-1 text-muted">
                            <i class="bi bi-tags me-2"></i>
                            <small class="fw-bold text-uppercase">Category</small>
                        </div>
                        <div class="ps-4">
                            <h6 class="fw-bold text-dark mb-0">{{ $item->category->category_name ?? '-' }}</h6>
                        </div>
                    </div>
                    <!-- Unit -->
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center mb-1 text-muted">
                            <i class="bi bi-rulers me-2"></i>
                            <small class="fw-bold text-uppercase">Unit</small>
                        </div>
                        <div class="ps-4">
                            <h6 class="fw-bold text-dark mb-0">{{ $item->unit->unit_name ?? '-' }}</h6>
                        </div>
                    </div>
                    <!-- Current Quantity -->
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center mb-1 text-muted">
                            <i class="bi bi-layers me-2"></i>
                            <small class="fw-bold text-uppercase">Current Quantity</small>
                        </div>
                        <div class="ps-4">
                            <h6 class="fw-bold text-dark mb-0">{{ $item->current_quantity ?? '0' }}</h6>
                        </div>
                    </div>
                    <!-- Description -->
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-1 text-muted">
                            <i class="bi bi-file-text me-2"></i>
                            <small class="fw-bold text-uppercase">Description</small>
                        </div>
                        <div class="ps-4">
                            <p class="text-secondary mb-0">{{ $item->description ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('stock-in.edit', $item->item_id) }}" class="mb-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <!-- Left Side: Date Filter -->
                <div class="d-flex align-items-center flex-wrap gap-2">
                     <label for="date_filter" class="text-nowrap fw-bold">Date Created:</label>
                     <div class="d-flex align-items-center">
                        <input type="date" name="date_filter" id="date_filter" class="form-control form-control-sm" style="max-width: 150px;" value="{{ request('date_filter') }}" onchange="this.form.submit()">
                        @if(request('date_filter'))
                            <a href="{{ route('stock-in.edit', $item->item_id) }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="bi bi-x"></i></a>
                        @endif
                     </div>
                </div>

                <!-- Right Side: Show & Print -->
                <div class="d-flex align-items-center flex-wrap gap-2">
                     <div class="d-flex align-items-center">
                        <label for="per_page" class="text-nowrap me-2">Show:</label>
                        <select name="per_page" id="per_page" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>All</option>
                        </select>
                     </div>
                     
                     <button type="button" id="btnPrintSelected" class="btn btn-sm btn-primary text-nowrap">
                        <i class="bi bi-printer me-1"></i> Print
                     </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>Serial</th>
                        <th>Full Code</th>
                        <th>QR Code</th>
                        <th>Date Created</th>
                        <th class="text-center">Printed</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockTransactions as $transaction)
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input unit-checkbox" value="{{ $transaction->unit_id }}" data-fullcode="{{ $transaction->full_code }}" data-itemname="{{ $item->item_name }}">
                        </td>
                        <td>{{ $transaction->serial ?? '-' }}</td>
                        <td>{{ $transaction->full_code ?? '-' }}</td>
                        <td>{{ $transaction->qr_code ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i:s') ?? ''}}</td>
                        <td class="text-center">
                            @if($transaction->is_printed)
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td>
                             {{-- Since we are in "Edit Mode", we can provide a way to delete specific transactions --}}
                             {{-- Note: Editing a specific transaction's serial/code is complex. For now, we provide delete. --}}
                             <form action="{{ route('stock-in.transaction.destroy', $transaction->transaction_id) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">No transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>


        </div>
        @php
            $from = $stockTransactions->firstItem() ?? 0;
            $to = $stockTransactions->lastItem() ?? 0;
            $total = $stockTransactions->total() ?? 0;
        @endphp
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
            <div>{{ $stockTransactions->links() }}</div>
        </div>
    </div>
 
</div>

<!-- Bulk Add Modal -->
<div class="modal fade" id="bulkAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Add Units</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <b>{{ $item->item_name }} ({{ $item->sku }})</b><br><br>
                <label class="form-label mb-3">Quantity to Add</label>
                <div class="d-flex align-items-center justify-content-center">
                     <button class="btn btn-outline-secondary" type="button" id="btnDecreaseBulk"><i class="bi bi-dash"></i></button>
                     <input type="number" class="form-control text-center mx-2" id="inputBulkQty" value="0" min="0" max="1000" style="width: 80px;">
                     <button class="btn btn-outline-secondary" type="button" id="btnIncreaseBulk"><i class="bi bi-plus"></i></button>
                </div>
            </div>
            <div class="modal-footer">
                
                <button type="button" style="float: right" class="btn btn-primary btn-sm" id="btnSaveBulk">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="printModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Barcodes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="qrPrintArea" class="d-flex flex-wrap justify-content-center gap-3">
                    <!-- Barcodes will be generated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="btnTagPrinted">
                    <i class="bi bi-check-circle me-1"></i> Tagged as Printed
                </button>
                <button type="button" class="btn btn-primary" onclick="printDiv('qrPrintArea')">
                    <i class="bi bi-printer me-1"></i> Print Now
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
    function printDiv(divId) {
        var printContents = document.getElementById(divId).innerHTML;
        var originalContents = document.body.innerHTML;
        
        // Create a hidden iframe to print
        var iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
        
        var doc = iframe.contentWindow.document;
        doc.open();
        doc.write('<html><head><title>Print Barcodes</title>');
        doc.write('<style>');
        doc.write('@page { size: 62mm auto; margin: 0; }'); // Brother QL-810W 62mm continuous label
        doc.write('body { margin: 0; padding: 0; font-family: sans-serif; width: 62mm; }');
        doc.write('.qr-item { width: 54mm; margin: 1mm auto; page-break-after: always; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; height: auto; padding-top: 2mm; padding-bottom: 2mm; }'); 
        doc.write('.barcode-wrapper { display: flex; justify-content: center; margin-bottom: 1mm; }');
        doc.write('.barcode-img { width: 50mm !important; height: 8mm !important; }');
        doc.write('.qr-text-name { font-size: 10px; font-weight: bold; margin-bottom: 1mm; word-wrap: break-word; width: 100%; text-align: center; }');
        doc.write('.qr-text-code { font-size: 11px; font-weight: bold; font-family: monospace; }');
        doc.write('</style>');
        doc.write('</head><body>');
        doc.write(printContents);
        doc.write('</body></html>');
        doc.close();
        
        iframe.contentWindow.focus();
        setTimeout(function() {
            iframe.contentWindow.print();
            document.body.removeChild(iframe);
        }, 1000); // Increased timeout to ensure rendering
    }

    $(document).ready(function() {
        // ... (existing code)

        $('#selectAll').change(function() {
            $('.unit-checkbox').prop('checked', $(this).prop('checked'));
        });

        $('#btnPrintSelected').click(function() {
            let selectedUnits = [];
            $('.unit-checkbox:checked').each(function() {
                selectedUnits.push({
                    id: $(this).val(),
                    code: $(this).data('fullcode'),
                    itemName: $(this).data('itemname')
                });
            });

            if (selectedUnits.length === 0) {
                toastr.warning('Please select at least one unit to print.');
                return;
            }

            $('#qrPrintArea').empty();
            
            selectedUnits.forEach(function(unit) {
                let container = $('<div class="qr-item"></div>');
                let wrapper = $('<div class="barcode-wrapper"></div>');
                let img = $('<img class="barcode-img" />');
                let nameText = $('<div class="qr-text-name"></div>').text(unit.itemName);
                let codeText = $('<div class="qr-text-code"></div>').text(unit.code);
                
                wrapper.append(img);
                container.append(nameText).append(wrapper).append(codeText);
                $('#qrPrintArea').append(container);
                
                JsBarcode(img[0], unit.code, {
                    format: "CODE128",
                    displayValue: false,
                    margin: 0,
                    height: 30
                });
            });

            $('#printModal').modal('show');
        });

        // Tag as Printed Button
        $('#btnTagPrinted').click(function() {
            let selectedIds = [];
            $('.unit-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) return;

            let btn = $(this);
            btn.prop('disabled', true).text('Tagging...');

            $.ajax({
                url: "{{ route('stock-in.mark-printed') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    unit_ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#printModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error('Error: ' + response.message);
                        btn.prop('disabled', false).text('Tagged as Printed');
                    }
                },
                error: function(xhr) {
                    toastr.error('Error occurred while tagging.');
                    btn.prop('disabled', false).text('Tagged as Printed');
                }
            });
        });

        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            var form = this;
            $.confirm({
                title: 'Confirm Delete',
                content: 'Delete this specific unit entry?',
                type: 'red',
                icon: 'bi bi-exclamation-triangle',
                theme: 'modern',
                draggable: true,
                backgroundDismiss: false,
                buttons: {
                    confirm: {
                        text: 'Yes, Delete',
                        btnClass: 'btn-danger',
                        action: function () {
                            form.submit();
                        }
                    },
                    cancel: function () {
                        // Do nothing
                    }
                }
            });
        });

        // Bulk Add Logic
        $('#btnIncreaseBulk').click(function() {
            let val = parseInt($('#inputBulkQty').val()) || 0;
            if (val < 1000) $('#inputBulkQty').val(val + 1);
        });

        $('#btnDecreaseBulk').click(function() {
            let val = parseInt($('#inputBulkQty').val()) || 0;
            if (val > 1) $('#inputBulkQty').val(val - 1);
        });

        $('#btnSaveBulk').click(function() {
            let qty = $('#inputBulkQty').val();
            let btn = $(this);
            
            // Disable button
            btn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: "{{ route('stock-in.bulk-store', $item->item_id) }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    quantity: qty
                },
                success: function(response) {
                    if(response.success) {
                        toastr.success(response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        toastr.error(response.message || 'Error occurred');
                        btn.prop('disabled', false).text('Save');
                    }
                },
                error: function(xhr) {
                    let msg = 'Error occurred';
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    toastr.error(msg);
                    btn.prop('disabled', false).text('Save');
                }
            });
        });
    });
</script>
@endpush
