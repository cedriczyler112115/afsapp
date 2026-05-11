@extends('layouts.app')

@section('title', 'Library - Document Sources')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Document Sources</span>
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
            <button class="btn btn-primary btn-sm" id="create-source-btn"><i class="bi bi-plus-circle me-1"></i>Create</button>
        </div>

        <div id="table-container"></div>
    </div>
</div>

<div class="modal fade" id="sourceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="sourceModalLabel">Create Document Source</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="sourceForm" autocomplete="off">
            <input type="hidden" id="source_id" name="id">
            <div class="mb-3">
                <label for="source_type" class="form-label">Type</label>
                <select class="form-select" id="source_type" name="source_type" required>
                    <option value="section">Section</option>
                    <option value="staff">Staff</option>
                </select>
                <div class="invalid-feedback" data-source-feedback-for="source_type"></div>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required maxlength="150">
                <div class="invalid-feedback" data-source-feedback-for="name"></div>
            </div>
            <div class="mb-3">
                <label for="is_active" class="form-label">Status</label>
                <select class="form-select" id="is_active" name="is_active" required>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <div class="invalid-feedback" data-source-feedback-for="is_active"></div>
            </div>
        </form>
        <div class="alert alert-danger d-none mb-0" role="alert" id="sourceFormGenericError"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="save-source-btn">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let lastCreatedId = null;

        function pulseRow($row) {
            if (!$row || !$row.length) return;
            $row.addClass('table-success');
            window.setTimeout(function () {
                $row.removeClass('table-success');
            }, 1600);
        }

        function clearSourceFormErrors() {
            $('#sourceFormGenericError').addClass('d-none').text('');
            $('#sourceForm').find('.is-invalid').removeClass('is-invalid');
            $('#sourceForm').find('[data-source-feedback-for]').text('');
        }

        function setSourceFormInvalid(name, message) {
            const $field = $('#sourceForm').find('[name="' + name + '"]');
            $field.addClass('is-invalid');
            $('#sourceForm').find('[data-source-feedback-for="' + name + '"]').text(message || '');
        }

        function openCreateSourceModal(prefillName) {
            clearSourceFormErrors();
            $('#sourceForm')[0].reset();
            $('#source_id').val('');
            $('#sourceModalLabel').text('Create Document Source');
            $('#name').val(prefillName || '');
            $('#sourceModal').modal('show');
        }

        function fetchSources(page = 1) {
            let search = $('#search-input').val();
            let per_page = $('#per_page').val();

            $.ajax({
                url: "{{ route('document-sources.index') }}",
                type: "GET",
                data: { page: page, search: search, per_page: per_page },
                success: function(response) {
                    $('#table-container').html(response);
                    if (lastCreatedId) {
                        const $row = $('#table-container').find('tr[data-source-id="' + String(lastCreatedId) + '"]');
                        if ($row.length) {
                            $row[0].scrollIntoView({ block: 'nearest' });
                            pulseRow($row);
                            lastCreatedId = null;
                        }
                    }
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    url.searchParams.set('search', search);
                    url.searchParams.set('per_page', per_page);
                    window.history.pushState({}, '', url);
                }
            });
        }

        fetchSources();

        $(document).on('click', '#table-container .pagination a', function(e) {
            e.preventDefault();
            const url = new URL($(this).attr('href'));
            const page = url.searchParams.get('page') || 1;
            fetchSources(page);
        });

        $('#search-input').on('input', function() {
            fetchSources();
        });

        $('#per_page').on('change', function() {
            fetchSources();
        });

        $('#create-source-btn').click(function() {
            openCreateSourceModal('');
        });

        $(document).on('click', '.js-add-new-document-source', function () {
            const prefill = String($(this).data('prefill') || '').trim();
            openCreateSourceModal(prefill);
        });

        $('#sourceModal').on('shown.bs.modal', function () {
            $('#name').trigger('focus');
        });
        $('#sourceModal').on('hidden.bs.modal', function () {
            clearSourceFormErrors();
        });

        $(document).on('click', '.edit-source', function() {
            let id = $(this).data('id');
            $.get("{{ url('document-sources') }}/" + id + "/edit", function(data) {
                $('#sourceModalLabel').text('Edit Document Source');
                $('#source_id').val(data.id);
                $('#source_type').val(data.source_type);
                $('#name').val(data.name);
                $('#is_active').val(data.is_active ? 1 : 0);
                $('#sourceModal').modal('show');
            });
        });

        $('#save-source-btn').click(function() {
            clearSourceFormErrors();
            let id = $('#source_id').val();
            let url = id ? "{{ url('document-sources') }}/" + id : "{{ route('document-sources.store') }}";
            let type = id ? "PUT" : "POST";

            $.ajax({
                url: url,
                type: type,
                data: $('#sourceForm').serialize(),
                success: function(response) {
                    $('#sourceModal').modal('hide');
                    if (!id && response && response.data && response.data.id) {
                        lastCreatedId = response.data.id;
                    }
                    fetchSources();
                    $.alert({ title: 'Success!', content: response.success, draggable: true, backgroundDismiss: false });
                },
                error: function(xhr) {
                    const status = xhr && xhr.status ? xhr.status : 0;
                    const errors = xhr && xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
                    if (status === 422 && errors) {
                        Object.keys(errors).forEach(function (k) {
                            if (!errors[k] || !errors[k].length) return;
                            setSourceFormInvalid(k, errors[k][0]);
                        });
                        $('#sourceFormGenericError').removeClass('d-none').text('Please fix the highlighted fields.');
                        return;
                    }
                    $('#sourceFormGenericError').removeClass('d-none').text('An error occurred.');
                }
            });
        });

        $(document).on('click', '.delete-source', function() {
            let id = $(this).data('id');
            $.confirm({
                title: 'Confirm',
                content: 'Delete this document source?',
                buttons: {
                    yes: function () {
                        $.ajax({
                            url: "{{ url('document-sources') }}/" + id,
                            type: "DELETE",
                            success: function(response) {
                                fetchSources();
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
