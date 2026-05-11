@extends('layouts.app')

@section('title', 'Incoming Document - Edit')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Edit Incoming Document</span>
        <a href="{{ route('incoming-documents.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        <form action="{{ route('incoming-documents.update', $incomingDocument) }}" method="POST" enctype="multipart/form-data" autocomplete="off" data-validate="incoming-document">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-2 mb-4">
                    <label class="form-label">Document From <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                    @php $transactionType = (int) old('transaction_type', $incomingDocument->transaction_type ?? 1); @endphp
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
                    <label class="form-label">Sender Type <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                    @php $senderType = (string) old('document_from_type', $incomingDocument->document_from_type ?? 'section'); @endphp
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
                            <option value="{{ $s->id }}" data-type="{{ $s->source_type }}" {{ (string)old('document_source_id', $incomingDocument->document_source_id) === (string)$s->id ? 'selected' : '' }}>{{ strtoupper($s->source_type) }} - {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Document Reference Number</label>
                    <input type="text" name="document_reference_number" class="form-control @error('document_reference_number') is-invalid @enderror" value="{{ old('document_reference_number', $incomingDocument->document_reference_number) }}" maxlength="80">
                    @error('document_reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>   
                <div class="col-md-2">
                    <label class="form-label">Date Received <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                    <input type="date" name="date_received" class="form-control @error('date_received') is-invalid @enderror" value="{{ old('date_received', optional($incomingDocument->date_received)->format('Y-m-d')) }}" required>
                    <div class="invalid-feedback" data-feedback-for="date_received">@error('date_received'){{ $message }}@enderror</div>
                </div>   
                <div class="col-md-2">
                    <label class="form-label">DRN</label>
                    <input type="text" name="drn" class="form-control @error('drn') is-invalid @enderror" value="{{ old('drn', $incomingDocument->drn) }}" maxlength="80">
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
                            <option value="{{ $t->id }}" {{ (string)old('document_type_id', $incomingDocument->document_type_id) === (string)$t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" data-feedback-for="document_type_id">@error('document_type_id'){{ $message }}@enderror</div>
                </div>

                <div class="col-md-10">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject', $incomingDocument->subject) }}" required maxlength="255">
                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $incomingDocument->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Signed By</label>
                    <input type="text" name="signed_by" class="form-control @error('signed_by') is-invalid @enderror" value="{{ old('signed_by', $incomingDocument->signed_by) }}" maxlength="150">
                    @error('signed_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date Signed</label>
                    <input type="date" name="date_signed" class="form-control @error('date_signed') is-invalid @enderror" value="{{ old('date_signed', optional($incomingDocument->date_signed)->format('Y-m-d')) }}">
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
                            <option value="{{ $p }}" {{ old('priority_level', $incomingDocument->priority_level) === $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                    @error('priority_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Deadline Date</label>
                    <input type="date" name="deadline_date" class="form-control @error('deadline_date') is-invalid @enderror" value="{{ old('deadline_date', optional($incomingDocument->deadline_date)->format('Y-m-d')) }}">
                    @error('deadline_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Received Remarks</label>
                    <input type="text" name="received_remarks" class="form-control @error('received_remarks') is-invalid @enderror" value="{{ old('received_remarks', $incomingDocument->received_remarks ?? '') }}">
                    @error('received_remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-12 d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .select2-container--bootstrap-5 .select2-selection.is-invalid { border-color: var(--bs-danger); }
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
                allowClear: true
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
                allowClear: true
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
