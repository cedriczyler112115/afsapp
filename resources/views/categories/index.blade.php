@extends('layouts.app')

@section('title', '4Ps AFS-IS - Categories')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Categories</span>
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
                <button class="btn btn-primary btn-sm" id="create-category-btn"><i class="bi bi-plus-circle me-1"></i>Create</button>
            </div>
        </div>
        <div id="table-container">
            @include('categories.table')
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="categoryModalLabel">Create Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="categoryForm" autocomplete="off">
            <input type="hidden" id="category_id" name="category_id">
            <div class="mb-3">
                <label for="category_name" class="form-label">Name</label>
                <input type="text" class="form-control" id="category_name" name="category_name" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="1">Active</option>
                    <option value="2">Inactive</option>
                </select>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="save-category-btn">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Fetch Categories
        function fetchCategories(page = 1) {
            let search = $('#search-input').val();
            let per_page = $('#per_page').val();

            $.ajax({
                url: "{{ route('categories.index') }}",
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
                fetchCategories(1);
            }, 300);
        });

        // Per Page Change
        $('#per_page').on('change', function() {
            fetchCategories(1);
        });

        // Pagination Click
        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            let href = $(this).attr('href');
            if (href && href !== '#') {
                let url = new URL(href);
                let page = url.searchParams.get('page') || 1;
                fetchCategories(page);
            }
        });

        // Create Category
        $('#create-category-btn').click(function() {
            $('#categoryForm')[0].reset();
            $('#category_id').val('');
            $('#categoryModalLabel').text('Create Category');
            $('#categoryModal').modal('show');
        });

        // Edit Category
        $(document).on('click', '.edit-category', function() {
            let id = $(this).data('id');
            $.get("{{ url('categories') }}/" + id + "/edit", function(data) {
                $('#categoryModalLabel').text('Edit Category');
                $('#category_id').val(data.category_id);
                $('#category_name').val(data.category_name);
                $('#description').val(data.description);
                $('#status').val(data.status);
                $('#categoryModal').modal('show');
            });
        });

        // Save Category (Create/Update)
        $('#save-category-btn').click(function() {
            let id = $('#category_id').val();
            let url = id ? "{{ url('categories') }}/" + id : "{{ route('categories.store') }}";
            let type = id ? "PUT" : "POST";

            $.ajax({
                url: url,
                type: type,
                data: $('#categoryForm').serialize(),
                success: function(response) {
                    $('#categoryModal').modal('hide');
                    fetchCategories(); // Refresh table
                    alert(response.success);
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = '';
                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '\n';
                    });
                    alert(errorMessage);
                }
            });
        });

        // Delete Category
        $(document).on('click', '.delete-category', function() {
            let id = $(this).data('id');
            if (confirm("Are you sure you want to delete this category?")) {
                $.ajax({
                    url: "{{ url('categories') }}/" + id,
                    type: "DELETE",
                    success: function(response) {
                        fetchCategories(); // Refresh table
                        alert(response.success);
                    },
                    error: function(xhr) {
                        alert('Error deleting category');
                    }
                });
            }
        });
    });
</script>
@endpush
