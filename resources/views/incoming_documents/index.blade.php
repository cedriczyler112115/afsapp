@extends('layouts.app')

@section('title', '4PS AFS-IS - Document Tracking - Incoming')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Incoming Documents</span>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="print-report-btn">
                <i class="bi bi-printer"></i> Print Report
            </button>            
            <a href="{{ route('incoming-documents.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> New Document
            </a>

        </div>
    </div>
    <div class="card-body p-2 p-md-3">
        @php
            $selectedCreator = (string) request('created_by', '');
            $selectedStatus = strtoupper((string) request('status', ''));
            $selectedTransactionType = (string) request('transaction_type', '');
        @endphp
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small mb-1">FILTERS : </label>
                <x-oval-radio-group
                    name="transaction_type"
                    :options="[
                        ['value' => '', 'label' => 'All'],
                        ['value' => 1, 'label' => 'Incoming'],
                        ['value' => 2, 'label' => 'Outgoing'],
                    ]"
                    :value="$selectedTransactionType"
                    aria-label="Document Type"
                />
            </div>
            <div class="col-md-3">
                <label for="search-input" class="form-label small mb-1">Document Reference Number</label>
                <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Reference / DRN / Subject" autocomplete="off" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label small mb-1">Date From</label>
                <input type="date" id="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label small mb-1">Date To</label>
                <input type="date" id="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3">
                {{-- <label for="status" class="form-label small mb-1">Status</label>
                <select id="status" class="form-select form-select-sm">
                    <option value="" {{ $selectedStatus === '' ? 'selected' : '' }}>All</option>
                    <option value="RECEIVED" {{ $selectedStatus === 'RECEIVED' ? 'selected' : '' }}>RECEIVED</option>
                    <option value="FORWARDED" {{ $selectedStatus === 'FORWARDED' ? 'selected' : '' }}>FORWARDED</option>
                    <option value="ARCHIVED" {{ $selectedStatus === 'ARCHIVED' ? 'selected' : '' }}>ARCHIVED</option>
                </select> --}}
            </div>
            @if(isset($creators) && $creators->count() > 0)
                <div class="col-md-3">
                    <label for="created_by" class="form-label small mb-1">Created by</label>
                    <select id="created_by" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($creators as $u)
                            <option value="{{ $u->id }}" {{ (string) $u->id === $selectedCreator ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-3">
                <label for="per_page" class="form-label small mb-1">Show</label>
                <select id="per_page" class="form-select form-select-sm">
                    <option value="10" {{ (string)request('per_page', 10) === '10' ? 'selected' : '' }}>10</option>
                    <option value="25" {{ (string)request('per_page') === '25' ? 'selected' : '' }}>25</option>
                    <option value="50" {{ (string)request('per_page') === '50' ? 'selected' : '' }}>50</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-sm btn-outline-secondary w-100" id="reset-filters"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
            </div>
        </div>

        <div id="table-container">
            @include('incoming_documents.table')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let activeSearch = $('#search-input').val() || '';
        let searchTimer = null;

        function initPopovers(scope) {
            const root = scope || document;
            const elements = root.querySelectorAll('[data-bs-toggle="popover"]');
            elements.forEach(function (el) {
                const existing = bootstrap.Popover.getInstance(el);
                if (existing) existing.dispose();
                new bootstrap.Popover(el);
            });
        }

        function fetchDocs(page = 1) {
            const search = activeSearch;
            const per_page = $('#per_page').val();
            const date_from = $('#date_from').val();
            const date_to = $('#date_to').val();
            const created_by = $('#created_by').length ? ($('#created_by').val() || '') : '';
            const status = $('#status').val() || '';
            const transaction_type = $('input[name="transaction_type"]:checked').val() || '';

            $.ajax({
                url: "{{ route('incoming-documents.index') }}",
                type: "GET",
                data: { page, search, per_page, date_from, date_to, created_by, status, transaction_type },
                success: function(response) {
                    $('#table-container').html(response);
                    initPopovers(document.getElementById('table-container'));
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    url.searchParams.set('search', search);
                    url.searchParams.set('per_page', per_page);
                    url.searchParams.set('date_from', date_from);
                    url.searchParams.set('date_to', date_to);
                    if (status) {
                        url.searchParams.set('status', status);
                    } else {
                        url.searchParams.delete('status');
                    }
                    if (transaction_type) {
                        url.searchParams.set('transaction_type', transaction_type);
                    } else {
                        url.searchParams.delete('transaction_type');
                    }
                    if (created_by) {
                        url.searchParams.set('created_by', created_by);
                    } else {
                        url.searchParams.delete('created_by');
                    }
                    window.history.pushState({}, '', url);
                }
            });
        }

        $(document).on('click', '#table-container .pagination a', function(e) {
            e.preventDefault();
            const url = new URL($(this).attr('href'));
            const page = url.searchParams.get('page') || 1;
            fetchDocs(page);
        });

        $('#search-input').on('input', function() {
            const current = $(this).val() || '';
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                if (current === activeSearch) return;
                activeSearch = current;
                fetchDocs(1);
            }, 450);
        });

        $('#search-input').on('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            activeSearch = $(this).val() || '';
            fetchDocs(1);
        });

        $('#per_page, #date_from, #date_to, #status').on('change', function() {
            fetchDocs(1);
        });

        if ($('#created_by').length) {
            $('#created_by').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'All',
                allowClear: true,
            });
            $('#created_by').on('change', function () {
                fetchDocs(1);
            });
        }

        $(document).on('change', 'input[name="transaction_type"]', function () {
            fetchDocs(1);
        });

        $('#reset-filters').on('click', function() {
            $(this).prop('disabled', true);
            $('#search-input').val('');
            $('#date_from').val('');
            $('#date_to').val('');
            $('#per_page').val('10');
            $('#status').val('');
            $('input[name=\"transaction_type\"][value=\"\"]').prop('checked', true).trigger('change');
            activeSearch = '';
            if ($('#created_by').length) {
                $('#created_by').val('').trigger('change');
            }
            fetchDocs(1);
            $(this).prop('disabled', false);
        });

        $('#print-report-btn').on('click', function () {
            const url = new URL("{{ route('incoming-documents.monthly-report') }}", window.location.origin);
            const search = activeSearch;
            const per_page = $('#per_page').val();
            const date_from = $('#date_from').val();
            const date_to = $('#date_to').val();
            const created_by = $('#created_by').length ? ($('#created_by').val() || '') : '';
            const status = $('#status').val() || '';
            const transaction_type = $('input[name="transaction_type"]:checked').val() || '';

            if (search) url.searchParams.set('search', search);
            if (date_from) url.searchParams.set('date_from', date_from);
            if (date_to) url.searchParams.set('date_to', date_to);
            if (created_by) url.searchParams.set('created_by', created_by);
            if (status) url.searchParams.set('status', status);
            if (transaction_type) url.searchParams.set('transaction_type', transaction_type);
            url.searchParams.set('per_page', per_page);

            window.open(url.toString(), '_blank', 'noopener,noreferrer');
        });

        initPopovers(document.getElementById('table-container'));
    });
</script>
@endpush
