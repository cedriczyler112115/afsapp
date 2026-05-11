@extends('layouts.app')

@section('title', '4Ps AFS-IS - Stock In (Receiving)')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Stock In (Receiving)</span>
    </div>
    <div class="card-body p-2 p-md-3">
        <div class="row g-2 mb-3 align-items-end">
            <!-- Search -->
            <div class="col-md-3">
                <label for="category_id" class="form-label small mb-1">Search Keyword</label>
                <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Search..." autocomplete="off">
            </div>
             <!-- Category Filter -->
             <div class="col-md-3">
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
                <label for="item_id" class="form-label small mb-1">Item</label>
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
            <!-- Per Page -->
            <div class="col-md-2 d-flex justify-content-end align-items-end gap-2 ms-auto">
                <div class="d-flex align-items-center">
                    <label for="per_page" class="me-2 text-nowrap">Show:</label>
                    <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>
            </div>
        </div>
        <div id="table-container">
            @include('stock_in.table')
        </div>
    </div>
</div>

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

        // Fetch Data Function
        function fetchStockTransactions(page = 1) {
            let perPage = $('#per_page').val();
            let search = $('#search-input').val();
            let categoryId = $('#category_id').val();
            let itemId = $('#item_id').val();

            $.ajax({
                url: "{{ route('stock-in.index') }}",
                type: "GET",
                data: { 
                    page: page,
                    per_page: perPage, 
                    search: search,
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
            
            $.ajax({
                url: "{{ route('stock-in.index') }}",
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
                    // Trigger table refresh
                    fetchStockTransactions();
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
            fetchStockTransactions(page);
        });

        // Search Input (Debounced)
        let debounceTimer;
        $('#search-input').on('keyup', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchStockTransactions();
            }, 500);
        });

        // Filters Change
        $('#item_id, #per_page').on('change', function() {
            fetchStockTransactions();
        });

        // Reset Filters
        $('#reset-filters').on('click', function() {
            $('#search-input').val('');
            $('#category_id').val('').trigger('change.select2');
            $('#item_id').val('').trigger('change.select2');
            $('#per_page').val('10');
            fetchStockTransactions();
        });
    });
</script>
@endpush
@endsection
