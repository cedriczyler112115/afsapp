@extends('layouts.app')

@section('title', '4Ps AFS-IS - Borrowings')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Borrowings</span>
        <a href="{{ route('borrowings.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Borrowing</a>
    </div>
    <div class="card-body p-2 p-md-3">
            <!-- Filters -->
            <div class="row g-2 mb-3 align-items-end">
                <!-- Search -->
                <div class="col-md-2">
                    <label for="search-input" class="form-label small mb-1">Search Keyword</label>
                    <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Search..." autocomplete="off">
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
                <div class="col-md-2">
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
                <!-- Status Filter -->
                <div class="col-md-2">
                    <label for="status" class="form-label small mb-1">Status</label>
                    <select id="status" class="form-select form-select-sm select2">
                        <option value="">All Statuses</option>
                        <option value="BORROWED">Borrowed</option>
                        <option value="RETURNED">Returned</option>
                        <option value="OVERDUE">Overdue</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>
                </div>
                <!-- Borrower Filter -->
                <div class="col-md-2">
                    <label for="borrower_id" class="form-label small mb-1">Borrower</label>
                    <select id="borrower_id" class="form-select form-select-sm select2">
                        <option value="">All Borrowers</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
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
                <div class="col-md-1 d-flex justify-content-end align-items-end gap-2 ms-auto">
                    <div class="d-flex align-items-center">
                        <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="table-container">
                @include('borrowings.table')
            </div>
        </div>
    </div>
@endsection

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
        function fetchBorrowings(page = 1) {
            let perPage = $('#per_page').val();
            let search = $('#search-input').val();
            let categoryId = $('#category_id').val();
            let itemId = $('#item_id').val();
            let status = $('#status').val();
            let borrowerId = $('#borrower_id').val();

            $.ajax({
                url: "{{ route('borrowings.index') }}",
                type: "GET",
                data: { 
                    page: page,
                    per_page: perPage, 
                    search: search,
                    category_id: categoryId,
                    item_id: itemId,
                    status: status,
                    borrower_id: borrowerId
                },
                success: function(data) {
                    $('#table-container').html(data);
                },
                error: function(xhr) {
                    console.error("Error fetching data:", xhr);
                }
            });
        }

        // Event Listeners for Filters
        $('#per_page, #category_id, #item_id, #status, #borrower_id').on('change', function() {
            fetchBorrowings();
        });

        // Debounce for Search
        let debounceTimer;
        $('#search-input').on('keyup', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchBorrowings();
            }, 500);
        });

        // Pagination Click Handling
        $(document).on('click', '.pagination a', function(event) {
            event.preventDefault();
            let page = $(this).attr('href').split('page=')[1];
            fetchBorrowings(page);
        });

        // Reset Filters
        $('#reset-filters').on('click', function() {
            $('#search-input').val('');
            $('#category_id').val('').trigger('change.select2');
            $('#item_id').val('').trigger('change.select2');
            $('#status').val('').trigger('change.select2');
            $('#borrower_id').val('').trigger('change.select2');
            $('#per_page').val('10');
            fetchBorrowings();
        });

        // Dependent Dropdown: Load Items based on Category
        $('#category_id').on('change', function() {
            let categoryId = $(this).val();
            let itemSelect = $('#item_id');

            // Store current selection if any (to preserve if possible, though unlikely when cat changes)
            // Actually usually we clear item if category changes
            
            $.ajax({
                url: "{{ route('borrowings.index') }}",
                type: "GET",
                data: { 
                    get_items_by_category: true,
                    category_id: categoryId
                },
                success: function(data) {
                    itemSelect.empty();
                    itemSelect.append('<option value="">All Items</option>');
                    $.each(data, function(key, item) {
                        itemSelect.append('<option value="'+ item.item_id +'">'+ item.item_name +'</option>');
                    });
                    itemSelect.trigger('change.select2'); // Update Select2
                    // We also trigger fetchBorrowings here because of the 'change' listener on category_id, 
                    // but we might want to ensure item filter is cleared/reset logically.
                    // The 'change' listener on category_id already called fetchBorrowings().
                }
            });
        });
    });
</script>
@endpush
