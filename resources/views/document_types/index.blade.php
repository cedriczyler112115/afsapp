@extends('layouts.app')

@section('title', 'Library - Document Types')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Document Types</span>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Search..." autocomplete="off" style="max-width: 250px;">
                <div class="d-flex align-items-center">
                    <label for="per_page" class="me-2 text-nowrap">Show:</label>
                    <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>
            </div>
            <button class="btn btn-primary btn-sm" id="create-type-btn"><i class="bi bi-plus-circle me-1"></i>Create</button>
        </div>

        <div id="table-container"></div>
    </div>
</div>

<div class="modal fade" id="typeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="typeModalLabel">Create Document Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="typeForm" autocomplete="off">
            <input type="hidden" id="type_id" name="id">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required maxlength="150">
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
        <button type="button" class="btn btn-primary" id="save-type-btn">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        function fetchTypes(page = 1) {
            let search = $('#search-input').val();
            let per_page = $('#per_page').val();

            $.ajax({
                url: "{{ route('document-types.index') }}",
                type: "GET",
                data: { page: page, search: search, per_page: per_page },
                success: function(response) {
                    $('#table-container').html(response);
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    url.searchParams.set('search', search);
                    url.searchParams.set('per_page', per_page);
                    window.history.pushState({}, '', url);
                }
            });
        }

        fetchTypes();

        $(document).on('click', '#table-container .pagination a', function(e) {
            e.preventDefault();
            const url = new URL($(this).attr('href'));
            const page = url.searchParams.get('page') || 1;
            fetchTypes(page);
        });

        $('#search-input').on('input', function() {
            fetchTypes();
        });

        $('#per_page').on('change', function() {
            fetchTypes();
        });

        $('#create-type-btn').click(function() {
            $('#typeForm')[0].reset();
            $('#type_id').val('');
            $('#typeModalLabel').text('Create Document Type');
            $('#typeModal').modal('show');
        });

        $(document).on('click', '.edit-type', function() {
            let id = $(this).data('id');
            $.get("{{ url('document-types') }}/" + id + "/edit", function(data) {
                $('#typeModalLabel').text('Edit Document Type');
                $('#type_id').val(data.id);
                $('#name').val(data.name);
                $('#is_active').val(data.is_active ? 1 : 0);
                $('#typeModal').modal('show');
            });
        });

        $('#save-type-btn').click(function() {
            let id = $('#type_id').val();
            let url = id ? "{{ url('document-types') }}/" + id : "{{ route('document-types.store') }}";
            let type = id ? "PUT" : "POST";

            $.ajax({
                url: url,
                type: type,
                data: $('#typeForm').serialize(),
                success: function(response) {
                    $('#typeModal').modal('hide');
                    fetchTypes();
                    $.alert({ title: 'Success!', content: response.success, draggable: true, backgroundDismiss: false });
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors || {};
                    let errorMessage = '';
                    $.each(errors, function(key, value) { errorMessage += value[0] + '<br>'; });
                    $.alert({ title: 'Error', content: errorMessage || 'An error occurred.', draggable: true, backgroundDismiss: false });
                }
            });
        });

        $(document).on('click', '.delete-type', function() {
            let id = $(this).data('id');
            $.confirm({
                title: 'Confirm',
                content: 'Delete this document type?',
                buttons: {
                    yes: function () {
                        $.ajax({
                            url: "{{ url('document-types') }}/" + id,
                            type: "DELETE",
                            success: function(response) {
                                fetchTypes();
                                $.alert({ title: 'Success!', content: response.success, draggable: true, backgroundDismiss: false });
                            },
                            error: function() {
                                $.alert({ title: 'Error', content: 'Failed to delete.', draggable: true, backgroundDismiss: false });
                            }
                        });
                    },
                    no: function () {}
                }
            });
        });
    });
</script>
@endpush

