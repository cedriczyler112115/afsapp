@extends('layouts.app')

@section('title', '4Ps AFS-IS - Damaged & Unserviceable')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center bg-danger text-white">
        <span>Damaged & Unserviceable</span>
    </div>
    <div class="card-body p-2 p-md-3">
        <div class="row g-2 mb-3 align-items-end">
            <!-- Search -->
            <div class="col-md-2">
               <label for="search-input" class="form-label small mb-1">Search Receiver</label>
               <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Search..." autocomplete="off">
            </div>
            <!-- Date Released Filter -->
            <div class="col-md-2">
                <label for="date_released" class="form-label small mb-1">Date Released</label>
                <input type="date" id="date_released" class="form-control form-control-sm" value="{{ request('date_released') }}" placeholder="Date Released">
            </div>
            <!-- Category Filter -->
            <div class="col-md-2">
                <label for="category_id" class="form-label small mb-1">Category</label>
                <select id="category_id" class="form-select form-select-sm select2">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->category_id }}" {{ request('category_id') == $category->category_id ? 'selected' : '' }}>
                            {{ $category->category_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <!-- Item Filter -->
            <div class="col-md-3">
                <label for="item_id" class="form-label small fw-bold mb-1">Item</label>
                <select id="item_id" class="form-select form-select-sm select2">
                    <option value="">All Items</option>
                    @foreach($items as $item)
                        <option value="{{ $item->item_id }}" {{ request('item_id') == $item->item_id ? 'selected' : '' }}>
                            {{ $item->item_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <!-- Reset Button -->
            <div class="col-md-1" style="width: 40px">
                <button type="button" id="reset-filters" class="btn btn-sm btn-primary w-100" title="Reset Filters">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
            <!-- Per Page & Action -->
            <div class="col-md-auto ms-auto d-flex justify-content-end align-items-end gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center">
                        <label for="per_page" class="me-2 text-nowrap">Show:</label>
                        <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        </select>
                    </div>

                    <a href="{{ route('damaged-items.create') }}" class="btn btn-primary btn-sm text-nowrap">
                        <i class="bi bi-plus-circle me-1"></i>Report Damage
                    </a>
                    
                    <button type="button" id="print-issuances" class="btn btn-sm btn-success text-nowrap" disabled title="Print Selected">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>
            </div>
        </div>
        <div id="table-container">
            @include('damaged_items.table')
        </div>
    </div>
</div>

<div id="modal-container"></div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: function() {
                $(this).data('placeholder');
            }
        });

        // Checkbox Logic
        $(document).on('change', '#select-all', function() {
            $('.issuance-checkbox').prop('checked', $(this).prop('checked'));
            togglePrintButton();
        });

        $(document).on('change', '.issuance-checkbox', function() {
            togglePrintButton();
            
            // Update Select All state
            let allChecked = $('.issuance-checkbox').length === $('.issuance-checkbox:checked').length;
            $('#select-all').prop('checked', allChecked);
        });

        function togglePrintButton() {
            let count = $('.issuance-checkbox:checked').length;
            if (count > 0) {
                $('#print-issuances').prop('disabled', false).html(`<i class="bi bi-printer me-1"></i>Print (${count})`);
            } else {
                $('#print-issuances').prop('disabled', true).html(`<i class="bi bi-printer me-1"></i>Print`);
            }
        }
        
        // Print Button Logic (Toolbar)
        $('#print-issuances').on('click', function() {
            let selectedIds = [];
            $('.issuance-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) return;

            $.ajax({
                url: "{{ route('damaged-items.preview') }}",
                type: "POST",
                data: { ids: selectedIds },
                success: function(response) {
                    $('#modal-container').html(response);
                    $('#previewModal').modal('show');
                },
                error: function() {
                    toastr.error('Error loading preview');
                }
            });
        });

        // Confirm Print (Modal)
        $(document).on('click', '#confirm-print', function() {
            let selectedIds = [];
            $('.issuance-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            let purpose = $('#issuance-purpose').val();
            if(!purpose.trim()){
                toastr.error('Please provide a purpose');
                $('#issuance-purpose').focus();
                return;
            }
            
            $.ajax({
                url: "{{ route('damaged-items.group') }}",
                type: 'POST',
                data: {
                    ids: selectedIds,
                    purpose: purpose
                },
                success: function(response) {
                    if(response.success) {
                        $('#previewModal').modal('hide');
                        toastr.success('Damage reports grouped successfully');
                        fetchDamagedItems(); // Reload table
                        window.open(response.print_url, '_blank');
                    }
                },
                error: function(xhr) {
                    toastr.error('Error processing request');
                }
            });
        });

        // Fetch Data Function
        function fetchDamagedItems(page = 1) {
            let perPage = $('#per_page').val();
            let search = $('#search-input').val();
            let dateReleased = $('#date_released').val();
            let categoryId = $('#category_id').val();
            let itemId = $('#item_id').val();

            $.ajax({
                url: "{{ route('damaged-items.index') }}?page=" + page,
                type: "GET",
                data: { 
                    per_page: perPage, 
                    search: search,
                    date_released: dateReleased,
                    category_id: categoryId,
                    item_id: itemId
                },
                success: function(data) {
                    $('#table-container').html(data);
                },
                error: function(xhr) {
                    console.error("Error fetching data:", xhr);
                }
            });
        }

        // Fetch Items based on Category
        $('#category_id').on('change', function() {
            let categoryId = $(this).val();
            let $itemSelect = $('#item_id');

            // Clear current items
            $itemSelect.empty().append('<option value="">All Items</option>');
            
            // Show loading state if desired, or just fetch
            $.ajax({
                url: "{{ route('damaged-items.index') }}",
                type: "GET",
                data: { 
                    get_items_by_category: true,
                    category_id: categoryId 
                },
                success: function(items) {
                    $.each(items, function(index, item) {
                        $itemSelect.append(new Option(item.item_name, item.item_id));
                    });
                    // Refresh Select2
                    $itemSelect.trigger('change.select2'); // Notify select2 of updates
                    
                    // Trigger table refresh
                    fetchDamagedItems();
                },
                error: function(xhr) {
                    console.error("Error fetching items:", xhr);
                }
            });
        });

        // Pagination Click
        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            let url = new URL($(this).attr('href'));
            let page = url.searchParams.get('page');
            fetchDamagedItems(page);
        });

        // Search Input (Debounced)
        let debounceTimer;
        $('#search-input').on('keyup', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchDamagedItems();
            }, 500);
        });

        // Filters Change
        $('#date_released, #item_id, #per_page').on('change', function() {
            fetchDamagedItems();
        });

        // Reset Filters
        $('#reset-filters').on('click', function() {
            $('#search-input').val('');
            $('#date_released').val('');
            $('#category_id').val('').trigger('change.select2');
            $('#item_id').val('').trigger('change.select2');
            $('#per_page').val('10'); 
            
            fetchDamagedItems();
        });

        $(document).on('click', '.view-details', function() {
            let id = $(this).data('id');
            let url = "{{ route('damaged-items.show', ':id') }}".replace(':id', id);

            $.ajax({
                url: url,
                type: "GET",
                success: function(response) {
                    $('#modal-container').html(response.html);
                    $('#issuanceModal').modal('show');
                },
                error: function(xhr) {
                    toastr.error('Error fetching details');
                }
            });
        });
    });
</script>
@endpush
@endsection
