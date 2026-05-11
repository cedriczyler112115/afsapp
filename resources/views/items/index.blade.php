@extends('layouts.app')

@section('title', '4Ps Storage Inventory - Items')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>List of Stock Items</span>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
                <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Search..." autocomplete="off" style="max-width: 250px;">
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center">
                    <label for="per_page" class="me-2 text-nowrap">Show:</label>
                    <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" id="create-item-btn"><i class="bi bi-plus-circle me-1"></i>Create</button>
            </div>
        </div>
        <div id="table-container">
            @include('items.table')
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
  <div id="liveToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toast-message">
        <!-- Message here -->
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<!-- Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="itemModalLabel">Create Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="itemForm" autocomplete="off">
            <input type="hidden" id="item_id" name="item_id">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="item_name" class="form-label">Item Name</label>
                    <input type="text" class="form-control" id="item_name" name="item_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="sku" class="form-label">SKU</label>
                    <input type="text" class="form-control" id="sku" name="sku" maxlength="50" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->category_id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="unit_id" class="form-label">Unit</label>
                    <select class="form-select" id="unit_id" name="unit_id" required>
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="reorder_level" class="form-label">Reorder Level</label>
                    <input type="number" class="form-control" id="reorder_level" name="reorder_level" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="save-item-btn">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        function showToast(message, type = 'success') {
            let toastEl = document.getElementById('liveToast');
            let toastBody = document.getElementById('toast-message');
            toastBody.innerHTML = message;
            
            if (type === 'success') {
                toastEl.classList.remove('bg-danger');
                toastEl.classList.add('bg-success');
            } else {
                toastEl.classList.remove('bg-success');
                toastEl.classList.add('bg-danger');
            }
            
            let toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        // Fetch Items
        function fetchItems(page = 1) {
            let search = $('#search-input').val();
            let per_page = $('#per_page').val();

            $.ajax({
                url: "{{ route('items.index') }}",
                type: "GET",
                data: { page: page, search: search, per_page: per_page },
                success: function(response) {
                    $('#table-container').html(response);
                    // Update URL
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    url.searchParams.set('search', search);
                    url.searchParams.set('per_page', per_page);
                    window.history.pushState({}, '', url);
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        }

        // Search Input (Debounced)
        let timeout = null;
        $('#search-input').on('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                fetchItems(1);
            }, 300);
        });

        // Per Page Change
        $('#per_page').on('change', function() {
            fetchItems(1);
        });

        // Pagination Click
        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            let href = $(this).attr('href');
            if (href && href !== '#') {
                let url = new URL(href);
                let page = url.searchParams.get('page') || 1;
                fetchItems(page);
            }
        });

        // Create Item
        $('#create-item-btn').click(function() {
            $('#itemForm')[0].reset();
            $('#item_id').val('');
            $('#itemModalLabel').text('Create Item');
            $('#itemModal').modal('show');
        });

        // Edit Item
        $(document).on('click', '.edit-item', function() {
            let id = $(this).data('id');
            $.ajax({
                url: "{{ url('items') }}/" + id + "/edit",
                type: "GET",
                success: function(data) {
                    $('#itemModalLabel').text('Edit Item');
                    $('#item_id').val(data.item_id);
                    $('#item_name').val(data.item_name);
                    $('#sku').val(data.sku);
                    $('#category_id').val(data.category_id);
                    $('#unit_id').val(data.unit_id);
                    $('#reorder_level').val(data.reorder_level);
                    $('#description').val(data.description);
                    $('#itemModal').modal('show');
                },
                error: function(xhr) {
                    console.error("Error fetching item data:", xhr);
                    showToast('Error fetching item details.', 'error');
                }
            });
        });

        // Save Item (Create/Update)
        $('#save-item-btn').click(function() {
            let id = $('#item_id').val();
            let url = id ? "{{ url('items') }}/" + id : "{{ route('items.store') }}";
            let type = id ? "PUT" : "POST";

            $.ajax({
                url: url,
                type: type,
                data: $('#itemForm').serialize(),
                success: function(response) {
                    $('#itemModal').modal('hide');
                    fetchItems(); // Refresh table
                    showToast(response.success, 'success');
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = '';
                    if (errors) {
                        $.each(errors, function(key, value) {
                            errorMessage += value[0] + '<br>';
                        });
                    } else {
                        errorMessage = 'An error occurred.';
                    }
                    showToast(errorMessage, 'error');
                }
            });
        });

        // Delete Item
        $(document).on('click', '.delete-item', function() {
            let id = $(this).data('id');
            $.confirm({
                title: 'Confirm Delete',
                content: 'Are you sure you want to delete this item?',
                draggable: true,
                backgroundDismiss: false,
                buttons: {
                    confirm: {
                        text: 'Yes, Delete',
                        btnClass: 'btn-danger',
                        action: function () {
                            $.ajax({
                                url: "{{ url('items') }}/" + id,
                                type: "DELETE",
                                success: function(response) {
                                    fetchItems(); // Refresh table
                                    showToast(response.success, 'success');
                                },
                                error: function(xhr) {
                                    showToast('Error deleting item', 'error');
                                }
                            });
                        }
                    },
                    cancel: function () {
                        // Do nothing
                    }
                }
            });
        });
    });
</script>
@endpush
