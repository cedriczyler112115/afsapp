@extends('layouts.app')

@section('title', '4Ps AFS-IS - Unit of Measures')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Unit of Measures</span>
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
                <button class="btn btn-primary btn-sm" id="create-unit-btn"><i class="bi bi-plus-circle me-1"></i>Create</button>
            </div>
        </div>
        <div id="table-container">
            @include('unit_of_measures.table')
        </div>
    </div>
</div>

<!-- Unit Modal -->
<div class="modal fade" id="unitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="unitModalLabel">Create Unit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="unitForm" autocomplete="off">
            <input type="hidden" id="unit_id" name="id">
            <div class="mb-3">
                <label for="unit_name" class="form-label">Name</label>
                <input type="text" class="form-control" id="unit_name" name="unit_name" required>
            </div>
            <div class="mb-3">
                <label for="unit_code" class="form-label">Code</label>
                <input type="text" class="form-control" id="unit_code" name="unit_code" required>
            </div>
            <div class="mb-3">
                <label for="unit_type" class="form-label">Type</label>
                <input type="text" class="form-control" id="unit_type" name="unit_type" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="is_active" class="form-label">Status</label>
                <select class="form-select" id="is_active" name="is_active" required>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="save-unit-btn">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Fetch Units
        function fetchUnits(page = 1) {
            let search = $('#search-input').val();
            let per_page = $('#per_page').val();

            $.ajax({
                url: "{{ route('unit_of_measures.index') }}",
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
                fetchUnits(1);
            }, 300);
        });

        // Per Page Change
        $('#per_page').on('change', function() {
            fetchUnits(1);
        });

        // Pagination Click
        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            let href = $(this).attr('href');
            if (href && href !== '#') {
                let url = new URL(href);
                let page = url.searchParams.get('page') || 1;
                fetchUnits(page);
            }
        });

        // Create Unit
        $('#create-unit-btn').click(function() {
            $('#unitForm')[0].reset();
            $('#unit_id').val('');
            $('#unitModalLabel').text('Create Unit');
            $('#unitModal').modal('show');
        });

        // Edit Unit
        $(document).on('click', '.edit-unit', function() {
            let id = $(this).data('id');
            $.get("{{ url('unit_of_measures') }}/" + id + "/edit", function(data) {
                $('#unitModalLabel').text('Edit Unit');
                $('#unit_id').val(data.id);
                $('#unit_name').val(data.unit_name);
                $('#unit_code').val(data.unit_code);
                $('#unit_type').val(data.unit_type);
                $('#description').val(data.description);
                $('#is_active').val(data.is_active);
                $('#unitModal').modal('show');
            });
        });

        // Save Unit (Create/Update)
        $('#save-unit-btn').click(function() {
            let id = $('#unit_id').val();
            let url = id ? "{{ url('unit_of_measures') }}/" + id : "{{ route('unit_of_measures.store') }}";
            let type = id ? "PUT" : "POST";

            $.ajax({
                url: url,
                type: type,
                data: $('#unitForm').serialize(),
                success: function(response) {
                    $('#unitModal').modal('hide');
                    fetchUnits(); // Refresh table
                    $.alert({
                        title: 'Success!',
                        content: response.success,
                        draggable: true,
                        backgroundDismiss: false
                    });
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = '';
                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '<br>';
                    });
                    $.alert({
                        title: 'Error!',
                        content: errorMessage,
                        draggable: true,
                        backgroundDismiss: false,
                        type: 'red'
                    });
                }
            });
        });

        // Delete Unit
        $(document).on('click', '.delete-unit', function() {
            let id = $(this).data('id');
            $.confirm({
                title: 'Confirm Delete',
                content: 'Are you sure you want to delete this unit?',
                draggable: true,
                backgroundDismiss: false,
                buttons: {
                    confirm: {
                        text: 'Yes, Delete',
                        btnClass: 'btn-danger',
                        action: function () {
                            $.ajax({
                                url: "{{ url('unit_of_measures') }}/" + id,
                                type: "DELETE",
                                success: function(response) {
                                    fetchUnits(); // Refresh table
                                    $.alert({
                                        title: 'Deleted!',
                                        content: response.success,
                                        draggable: true,
                                        backgroundDismiss: false
                                    });
                                },
                                error: function(xhr) {
                                    $.alert({
                                        title: 'Error!',
                                        content: 'Error deleting unit',
                                        draggable: true,
                                        backgroundDismiss: false,
                                        type: 'red'
                                    });
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
