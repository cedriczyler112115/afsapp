@extends('layouts.app')

@section('title', 'Incoming Document - Create')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Create Incoming Document</span>
        <a href="{{ route('incoming-documents.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        <form action="{{ route('incoming-documents.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off" data-validate="incoming-document">
            @csrf

            <div class="row g-3">
                <div class="col-md-2 mb-4">
                    <label class="form-label">Transaction Type <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                    @php $transactionType = (int) old('transaction_type', 1); @endphp
                    <x-oval-radio-group
                        name="transaction_type"
                        :options="[
                            ['value' => 1, 'label' => 'Incoming'],
                            ['value' => 2, 'label' => 'Outgoing'],
                        ]"
                        :value="$transactionType"
                        aria-label="Document From"
                        size="sm"
                        :required="true"
                    />
                    <div class="text-danger small mt-1 @error('transaction_type') d-block @else d-none @enderror" data-feedback-for="transaction_type">@error('transaction_type'){{ $message }}@enderror</div>
                </div>


                <div class="col-md-2">
                    <label class="form-label">Document From <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                    @php $senderType = (string) old('document_from_type', 'section'); @endphp
                    <x-oval-radio-group
                        name="document_from_type"
                        :options="[
                            ['value' => 'section', 'label' => 'Section'],
                            ['value' => 'staff', 'label' => 'Staff'],
                        ]"
                        :value="$senderType"
                        aria-label="Sender Type"
                        size="sm"
                        :required="true"
                    />
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Document Source <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                    <select name="document_source_id" id="document_source_id" class="form-select @error('document_source_id') is-invalid @enderror" required>
                        <option value="">Select source</option>
                        @foreach($sources as $s)
                            <option value="{{ $s->id }}" data-type="{{ $s->source_type }}" {{ (string)old('document_source_id') === (string)$s->id ? 'selected' : '' }}>{{ strtoupper($s->source_type) }} - {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Document Reference Number</label>
                    <input type="text" name="document_reference_number" class="form-control @error('document_reference_number') is-invalid @enderror" value="{{ old('document_reference_number') }}" maxlength="80">
                    @error('document_reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>   
                <div class="col-md-2">
                    <label class="form-label">Date Received <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                    <input type="date" name="date_received" class="form-control @error('date_received') is-invalid @enderror" value="{{ old('date_received') }}" required>
                    <div class="invalid-feedback" data-feedback-for="date_received">@error('date_received'){{ $message }}@enderror</div>
                </div>   
                <div class="col-md-2">
                    <label class="form-label">DRN</label>
                    <input type="text" name="drn" class="form-control @error('drn') is-invalid @enderror" value="{{ old('drn') }}" maxlength="80">
                    @error('drn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>                          
                    <div class="invalid-feedback" data-feedback-for="document_source_id">@error('document_source_id'){{ $message }}@enderror</div>
                    <div class="text-danger small mt-1 @error('document_from_type') d-block @else d-none @enderror" data-feedback-for="document_from_type">@error('document_from_type'){{ $message }}@enderror</div>
                </div>

                <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-check-label" for="from_section">Document Type <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                    <div class="d-flex gap-3 mb-2">
                    </div>                    
                    <select name="document_type_id" id="document_type_id" class="form-select @error('document_type_id') is-invalid @enderror" required>
                        <option value="">Select type</option>
                        @foreach($types as $t)
                            <option value="{{ $t->id }}" {{ (string)old('document_type_id') === (string)$t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" data-feedback-for="document_type_id">@error('document_type_id'){{ $message }}@enderror</div>
                </div>

                <div class="col-md-10">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" required maxlength="255">
                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Signed By</label>
                    <input type="text" name="signed_by" class="form-control @error('signed_by') is-invalid @enderror" value="{{ old('signed_by') }}" maxlength="150">
                    @error('signed_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date Signed</label>
                    <input type="date" name="date_signed" class="form-control @error('date_signed') is-invalid @enderror" value="{{ old('date_signed') }}">
                    @error('date_signed')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Attachment</label>
                    <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror">
                    @error('attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Priority Level</label>
                    <select name="priority_level" class="form-select @error('priority_level') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach(['LOW','NORMAL','HIGH','URGENT'] as $p)
                            <option value="{{ $p }}" {{ old('priority_level') === $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                    @error('priority_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Deadline Date</label>
                    <input type="date" name="deadline_date" class="form-control @error('deadline_date') is-invalid @enderror" value="{{ old('deadline_date') }}">
                    @error('deadline_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Received Remarks</label>
                    <input type="text" name="received_remarks" class="form-control @error('received_remarks') is-invalid @enderror" value="{{ old('received_remarks') }}">
                    @error('received_remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-12 d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addDocumentSourceModal" tabindex="-1" aria-labelledby="addDocumentSourceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDocumentSourceModalLabel">Create Document Source</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addDocumentSourceForm" autocomplete="off" novalidate>
                    <div class="mb-3">
                        <label for="add_source_type" class="form-label">Type</label>
                        <select class="form-select" id="add_source_type" name="source_type" required>
                            <option value="section">Section</option>
                            <option value="staff">Staff</option>
                        </select>
                        <div class="invalid-feedback" data-add-source-feedback-for="source_type"></div>
                    </div>
                    <div class="mb-3">
                        <label for="add_source_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="add_source_name" name="name" required maxlength="150">
                        <div class="invalid-feedback" data-add-source-feedback-for="name"></div>
                    </div>
                    <div class="mb-3">
                        <label for="add_source_is_active" class="form-label">Status</label>
                        <select class="form-select" id="add_source_is_active" name="is_active" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-add-source-feedback-for="is_active"></div>
                    </div>
                </form>
                <div class="alert alert-danger d-none mb-0" role="alert" id="addSourceGenericError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="addSourceCancelBtn">Close</button>
                <button type="button" class="btn btn-primary" id="addSourceSaveBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="addSourceSpinner"></span>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addDocumentTypeModal" tabindex="-1" aria-labelledby="addDocumentTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDocumentTypeModalLabel">Create Document Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addDocumentTypeForm" autocomplete="off" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="add_type_name">Name</label>
                        <input type="text" class="form-control" id="add_type_name" name="name" maxlength="150" required>
                        <div class="invalid-feedback" data-add-type-feedback-for="name"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="add_type_is_active">Status</label>
                        <select class="form-select" id="add_type_is_active" name="is_active" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-add-type-feedback-for="is_active"></div>
                    </div>
                </form>
                <div class="alert alert-danger d-none mb-0" role="alert" id="addTypeGenericError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="addTypeCancelBtn">Close</button>
                <button type="button" class="btn btn-primary" id="addTypeSaveBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="addTypeSpinner"></span>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .select2-container--bootstrap-5 .select2-selection.is-invalid { border-color: var(--bs-danger); }
    .select2-results__message .select2-add-new-source,
    .select2-results__message .select2-add-new-type { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        const allSourceOptions = [];
        $('#document_source_id option').each(function() {
            const val = $(this).attr('value');
            const type = $(this).data('type');
            if (!val || !type) return;
            allSourceOptions.push({ value: val, type: type, text: $(this).text() });
        });

        function initSourceSelect2() {
            const select = $('#document_source_id');
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select source',
                allowClear: true,
                escapeMarkup: function (markup) { return markup; },
                language: {
                    noResults: function () {
                        return '<span class="d-inline-flex align-items-center flex-wrap gap-2">' +
                            '<span class="text-secondary">No result found</span>' +
                            '<button type="button" class="btn btn-sm btn-link p-0 select2-add-new-source" aria-label="Add new document source" tabindex="0">' +
                            '<i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add New</button>' +
                            '</span>';
                    },
                },
            });
        }

        function initTypeSelect2() {
            const select = $('#document_type_id');
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select type',
                allowClear: true,
                escapeMarkup: function (markup) { return markup; },
                language: {
                    noResults: function () {
                        return '<span class="d-inline-flex align-items-center flex-wrap gap-2">' +
                            '<span class="text-secondary">No result found</span>' +
                            '<button type="button" class="btn btn-sm btn-link p-0 select2-add-new-type" aria-label="Add new document type" tabindex="0">' +
                            '<i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add New</button>' +
                            '</span>';
                    },
                },
            });
        }

        function rebuildSourceOptions() {
            const type = $('input[name="document_from_type"]:checked').val();
            const selected = $('#document_source_id').val();
            const select = $('#document_source_id');

            select.empty();
            select.append(new Option('Select source', '', false, false));

            let stillSelected = false;
            allSourceOptions.forEach(function(opt) {
                if (opt.type !== type) return;
                const isSelected = selected && String(selected) === String(opt.value);
                if (isSelected) stillSelected = true;
                select.append(new Option(opt.text, opt.value, false, isSelected));
            });

            if (!stillSelected) {
                select.val('');
            }

            initSourceSelect2();
        }

        initTypeSelect2();
        rebuildSourceOptions();
        $('input[name="document_from_type"]').on('change', rebuildSourceOptions);

        const bindSelect2AddNewKeyboard = function (selectId, buttonSelector, openFn) {
            const $select = $(selectId);
            $select.off('select2:open.addNew').on('select2:open.addNew', function () {
                const $field = $('.select2-container--open .select2-search__field');
                $field.off('keydown.addNew').on('keydown.addNew', function (e) {
                    if (e.key !== 'Enter') return;
                    const $btn = $('.select2-container--open .select2-results__message').find(buttonSelector);
                    if (!$btn.length) return;

                    e.preventDefault();
                    e.stopPropagation();
                    const term = String($field.val() || '').trim();
                    $(selectId).select2('close');
                    openFn(term);
                });
            });
        };

        const pulseSelected = function ($selectEl) {
            const $container = $selectEl.next('.select2-container');
            const $sel = $container.find('.select2-selection');
            if (!$sel.length) return;

            $sel.addClass('border border-success');
            window.setTimeout(function () {
                $sel.removeClass('border border-success');
            }, 1600);
        };

        const modalEl = document.getElementById('addDocumentSourceModal');
        const addSourceModal = modalEl ? new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false }) : null;

        const $addSourceForm = $('#addDocumentSourceForm');
        const $addSourceError = $('#addSourceGenericError');
        const $addSourceSpinner = $('#addSourceSpinner');
        const $addSourceSaveBtn = $('#addSourceSaveBtn');
        const $addSourceCancelBtn = $('#addSourceCancelBtn');

        const clearAddSourceErrors = function () {
            $addSourceError.addClass('d-none').text('');
            $addSourceForm.find('.is-invalid').removeClass('is-invalid');
            $addSourceForm.find('[data-add-source-feedback-for]').text('');
        };

        const setAddSourceInvalid = function (name, message) {
            const $field = $addSourceForm.find('[name="' + name + '"]');
            $field.addClass('is-invalid');
            $addSourceForm.find('[data-add-source-feedback-for="' + name + '"]').text(message || '');
        };

        const setAddSourceSaving = function (saving) {
            $addSourceSpinner.toggleClass('d-none', !saving);
            $addSourceSaveBtn.prop('disabled', saving);
            $addSourceCancelBtn.prop('disabled', saving);
            $addSourceForm.find('input,select,textarea,button').prop('disabled', saving);
            $addSourceSaveBtn.prop('disabled', saving);
            $addSourceCancelBtn.prop('disabled', saving);
        };

        const openAddSourceModal = function (prefillName) {
            if (!addSourceModal) return;

            clearAddSourceErrors();

            const currentType = $('input[name="document_from_type"]:checked').val() || 'section';
            $('#add_source_type').val(currentType);
            $('#add_source_is_active').val('1');
            $('#add_source_name').val(prefillName || '');

            addSourceModal.show();
        };

        if (modalEl) {
            modalEl.addEventListener('shown.bs.modal', function () {
                $('#add_source_name').trigger('focus');
            });
            modalEl.addEventListener('hidden.bs.modal', function () {
                setAddSourceSaving(false);
                clearAddSourceErrors();
                $addSourceForm[0].reset();
            });
        }

        $(document).on('click', '.select2-add-new-source', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const term = $('.select2-container--open .select2-search__field').val() || '';
            $('#document_source_id').select2('close');
            openAddSourceModal(String(term).trim());
        });

        const typeModalEl = document.getElementById('addDocumentTypeModal');
        const addTypeModal = typeModalEl ? new bootstrap.Modal(typeModalEl, { backdrop: 'static', keyboard: false }) : null;

        const $addTypeForm = $('#addDocumentTypeForm');
        const $addTypeError = $('#addTypeGenericError');
        const $addTypeSpinner = $('#addTypeSpinner');
        const $addTypeSaveBtn = $('#addTypeSaveBtn');
        const $addTypeCancelBtn = $('#addTypeCancelBtn');

        const clearAddTypeErrors = function () {
            $addTypeError.addClass('d-none').text('');
            $addTypeForm.find('.is-invalid').removeClass('is-invalid');
            $addTypeForm.find('[data-add-type-feedback-for]').text('');
        };

        const setAddTypeInvalid = function (name, message) {
            const $field = $addTypeForm.find('[name="' + name + '"]');
            $field.addClass('is-invalid');
            $addTypeForm.find('[data-add-type-feedback-for="' + name + '"]').text(message || '');
        };

        const setAddTypeSaving = function (saving) {
            $addTypeSpinner.toggleClass('d-none', !saving);
            $addTypeSaveBtn.prop('disabled', saving);
            $addTypeCancelBtn.prop('disabled', saving);
            $addTypeForm.find('input,select,textarea,button').prop('disabled', saving);
        };

        const openAddTypeModal = function (prefillName) {
            if (!addTypeModal) return;
            clearAddTypeErrors();
            $('#add_type_is_active').val('1');
            $('#add_type_name').val(prefillName || '');
            addTypeModal.show();
        };

        if (typeModalEl) {
            typeModalEl.addEventListener('shown.bs.modal', function () {
                $('#add_type_name').trigger('focus');
            });
            typeModalEl.addEventListener('hidden.bs.modal', function () {
                setAddTypeSaving(false);
                clearAddTypeErrors();
                if ($addTypeForm.length) {
                    $addTypeForm[0].reset();
                }
            });
        }

        $(document).on('click', '.select2-add-new-type', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const term = $('.select2-container--open .select2-search__field').val() || '';
            $('#document_type_id').select2('close');
            openAddTypeModal(String(term).trim());
        });

        const attachNewTypeToParentForm = function (payload) {
            const id = payload && payload.id ? String(payload.id) : '';
            const name = payload && payload.name ? String(payload.name) : '';
            if (!id || !name) return;

            const $typeSelect = $('#document_type_id');
            const exists = $typeSelect.find('option[value="' + id + '"]').length > 0;
            if (!exists) {
                $typeSelect.append(new Option(name, id, false, false));
            }
            $typeSelect.val(id).trigger('change');
            pulseSelected($typeSelect);
        };

        const attachNewSourceToParentForm = function (payload) {
            const id = payload && payload.id ? String(payload.id) : '';
            const sourceType = payload && payload.source_type ? String(payload.source_type) : '';
            const name = payload && payload.name ? String(payload.name) : '';
            if (!id || !sourceType || !name) return;

            const optionText = sourceType.toUpperCase() + ' - ' + name;
            allSourceOptions.push({ value: id, type: sourceType, text: optionText });

            const $radio = $('input[name="document_from_type"][value="' + sourceType + '"]');
            if ($radio.length) {
                $radio.prop('checked', true).trigger('change');
            } else {
                rebuildSourceOptions();
            }

            $('#document_source_id').val(id).trigger('change');
            pulseSelected($('#document_source_id'));
        };

        $addTypeSaveBtn.on('click', function () {
            clearAddTypeErrors();

            const name = String($('#add_type_name').val() || '').trim();
            const isActive = String($('#add_type_is_active').val() || '').trim();

            let ok = true;
            if (name === '') {
                ok = false;
                setAddTypeInvalid('name', 'Name is required.');
            }
            if (isActive !== '0' && isActive !== '1') {
                ok = false;
                setAddTypeInvalid('is_active', 'Status is required.');
            }
            if (!ok) return;

            setAddTypeSaving(true);
            $.ajax({
                url: @json(route('document-types.store')),
                type: 'POST',
                data: { name: name, is_active: isActive },
                success: function (resp) {
                    toastr.success(resp && resp.success ? resp.success : 'Saved.');
                    attachNewTypeToParentForm(resp && resp.data ? resp.data : null);
                    if (addTypeModal) addTypeModal.hide();
                },
                error: function (xhr) {
                    const status = xhr && xhr.status ? xhr.status : 0;
                    if (status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (k) {
                            if (!errors[k] || !errors[k].length) return;
                            setAddTypeInvalid(k, errors[k][0]);
                        });
                        toastr.error('Please fix the highlighted fields.');
                        return;
                    }

                    $addTypeError.removeClass('d-none').text('Failed to save. Please try again.');
                    toastr.error('Failed to save document type.');
                },
                complete: function () {
                    setAddTypeSaving(false);
                },
            });
        });

        $addSourceSaveBtn.on('click', function () {
            clearAddSourceErrors();

            const sourceType = String($('#add_source_type').val() || '').trim();
            const name = String($('#add_source_name').val() || '').trim();
            const isActive = String($('#add_source_is_active').val() || '').trim();

            let ok = true;
            if (sourceType !== 'section' && sourceType !== 'staff') {
                ok = false;
                setAddSourceInvalid('source_type', 'Type is required.');
            }
            if (name === '') {
                ok = false;
                setAddSourceInvalid('name', 'Name is required.');
            }
            if (isActive !== '0' && isActive !== '1') {
                ok = false;
                setAddSourceInvalid('is_active', 'Status is required.');
            }
            if (!ok) return;

            setAddSourceSaving(true);
            $.ajax({
                url: @json(route('document-sources.store')),
                type: 'POST',
                data: {
                    source_type: sourceType,
                    name: name,
                    is_active: isActive,
                },
                success: function (resp) {
                    toastr.success(resp && resp.success ? resp.success : 'Saved.');
                    attachNewSourceToParentForm(resp && resp.data ? resp.data : null);
                    if (addSourceModal) addSourceModal.hide();
                },
                error: function (xhr) {
                    const status = xhr && xhr.status ? xhr.status : 0;
                    if (status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (k) {
                            if (!errors[k] || !errors[k].length) return;
                            setAddSourceInvalid(k, errors[k][0]);
                        });
                        toastr.error('Please fix the highlighted fields.');
                        return;
                    }

                    $addSourceError.removeClass('d-none').text('Failed to save. Please try again.');
                    toastr.error('Failed to save document source.');
                },
                complete: function () {
                    setAddSourceSaving(false);
                }
            });
        });

        bindSelect2AddNewKeyboard('#document_source_id', '.select2-add-new-source', openAddSourceModal);
        bindSelect2AddNewKeyboard('#document_type_id', '.select2-add-new-type', openAddTypeModal);

        const $form = $('form[data-validate="incoming-document"]');
        $form.find('[data-feedback-for]').each(function () {
            $(this).data('serverText', $(this).text());
        });

        const setInvalid = function (selector, message) {
            const $el = $(selector);
            $el.addClass('is-invalid');
            const $feedback = $form.find('[data-feedback-for="' + $el.attr('name') + '"]');
            if ($feedback.length) {
                $feedback.text(message);
            }
            const $select2 = $el.next('.select2-container');
            if ($select2.length) {
                $select2.find('.select2-selection').addClass('is-invalid');
            }
        };

        const clearInvalid = function (selector) {
            const $el = $(selector);
            $el.removeClass('is-invalid');
            const $feedback = $form.find('[data-feedback-for="' + $el.attr('name') + '"]');
            if ($feedback.length) {
                $feedback.text($feedback.data('serverText') || '');
            }
            const $select2 = $el.next('.select2-container');
            if ($select2.length) {
                $select2.find('.select2-selection').removeClass('is-invalid');
            }
        };

        const showFromTypeError = function (message) {
            const $feedback = $form.find('[data-feedback-for="document_from_type"]');
            $feedback.removeClass('d-none').text(message);
        };

        const restoreFromTypeFeedback = function () {
            const $feedback = $form.find('[data-feedback-for="document_from_type"]');
            const serverText = ($feedback.data('serverText') || '').trim();
            $feedback.text(serverText);
            if (serverText === '') {
                $feedback.addClass('d-none');
            } else {
                $feedback.removeClass('d-none');
            }
        };

        const showTransactionTypeError = function (message) {
            const $feedback = $form.find('[data-feedback-for="transaction_type"]');
            $feedback.removeClass('d-none').text(message);
        };

        const restoreTransactionTypeFeedback = function () {
            const $feedback = $form.find('[data-feedback-for="transaction_type"]');
            const serverText = ($feedback.data('serverText') || '').trim();
            $feedback.text(serverText);
            if (serverText === '') {
                $feedback.addClass('d-none');
            } else {
                $feedback.removeClass('d-none');
            }
        };

        $form.on('submit', function (e) {
            let ok = true;

            clearInvalid('input[name="date_received"]');
            clearInvalid('select[name="document_source_id"]');
            clearInvalid('select[name="document_type_id"]');
            restoreFromTypeFeedback();
            restoreTransactionTypeFeedback();

            if (!$('input[name="document_from_type"]:checked').length) {
                ok = false;
                showFromTypeError('Sender type is required.');
            }

            if (!$('input[name="transaction_type"]:checked').length) {
                ok = false;
                showTransactionTypeError('Document type is required.');
            }

            const dateReceived = $('input[name="date_received"]').val();
            if (!dateReceived) {
                ok = false;
                setInvalid('input[name="date_received"]', 'Date received is required.');
            }

            const sourceId = $('#document_source_id').val();
            if (!sourceId) {
                ok = false;
                setInvalid('#document_source_id', 'Sender is required.');
            }

            const typeId = $('#document_type_id').val();
            if (!typeId) {
                ok = false;
                setInvalid('#document_type_id', 'Document type is required.');
            }

            if (!ok) {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        $(document).on('change input', 'input[name="date_received"]', function () {
            clearInvalid(this);
        });
        $(document).on('change', '#document_source_id', function () {
            clearInvalid(this);
        });
        $(document).on('change', '#document_type_id', function () {
            clearInvalid(this);
        });
        $(document).on('change', 'input[name="document_from_type"]', function () {
            restoreFromTypeFeedback();
        });
        $(document).on('change', 'input[name="transaction_type"]', function () {
            restoreTransactionTypeFeedback();
        });
    });
</script>
@endpush
