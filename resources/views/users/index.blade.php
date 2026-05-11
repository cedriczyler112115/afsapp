@extends('layouts.app')

@section('title', '4Ps AFS-IS - User Management')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>User Management</span>
    </div>
    <div class="card-body p-2 p-md-3">
        @if(session('status'))
            <div class="alert alert-success mb-3">{{ session('status') }}</div>
        @endif

        <div class="row g-2 mb-3 align-items-end">
            <div class="col-md-3">
                <label for="search-input" class="form-label small mb-1">Search</label>
                <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Name or Email" autocomplete="off" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label small mb-1">Status</label>
                <select id="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1" {{ (string)request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ (string)request('status') === '0' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="level_id" class="form-label small mb-1">User Level</label>
                <select id="level_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($userLevels as $lvl)
                        <option value="{{ $lvl->id }}" {{ (string)request('level_id') === (string)$lvl->id ? 'selected' : '' }}>{{ $lvl->level_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="division_id" class="form-label small mb-1">Division</label>
                <select id="division_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($divisions as $div)
                        <option value="{{ $div->id }}" {{ (string)request('division_id') === (string)$div->id ? 'selected' : '' }}>{{ $div->division_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="section_id" class="form-label small mb-1">Section</label>
                <select id="section_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec->id }}" {{ (string)request('section_id') === (string)$sec->id ? 'selected' : '' }}>{{ $sec->section_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label for="per_page" class="form-label small mb-1">Show</label>
                <select id="per_page" class="form-select form-select-sm">
                    <option value="10" {{ (string)request('per_page', 10) === '10' ? 'selected' : '' }}>10</option>
                    <option value="25" {{ (string)request('per_page') === '25' ? 'selected' : '' }}>25</option>
                    <option value="50" {{ (string)request('per_page') === '50' ? 'selected' : '' }}>50</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary w-100" id="reset-filters"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
            </div>
        </div>

        <input type="hidden" id="sort_by" value="{{ $sortBy }}">
        <input type="hidden" id="sort_dir" value="{{ $sortDir }}">

        <div id="table-container">
            @include('users.table')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        function fetchUsers(page = 1) {
            const search = $('#search-input').val();
            const per_page = $('#per_page').val();
            const status = $('#status').val();
            const level_id = $('#level_id').val();
            const division_id = $('#division_id').val();
            const section_id = $('#section_id').val();
            const sort_by = $('#sort_by').val();
            const sort_dir = $('#sort_dir').val();

            $.ajax({
                url: "{{ route('users.index') }}",
                type: "GET",
                data: { page, search, per_page, status, level_id, division_id, section_id, sort_by, sort_dir },
                success: function (response) {
                    $('#table-container').html(response);
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    url.searchParams.set('search', search);
                    url.searchParams.set('per_page', per_page);
                    url.searchParams.set('status', status);
                    url.searchParams.set('level_id', level_id);
                    url.searchParams.set('division_id', division_id);
                    url.searchParams.set('section_id', section_id);
                    url.searchParams.set('sort_by', sort_by);
                    url.searchParams.set('sort_dir', sort_dir);
                    window.history.pushState({}, '', url);
                }
            });
        }

        let timeout = null;
        $('#search-input').on('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                fetchUsers(1);
            }, 300);
        });

        $('#per_page, #status, #level_id, #division_id, #section_id').on('change', function () {
            fetchUsers(1);
        });

        $('#reset-filters').on('click', function (e) {
            e.preventDefault();
            $('#search-input').val('');
            $('#status').val('');
            $('#level_id').val('');
            $('#division_id').val('');
            $('#section_id').val('');
            $('#per_page').val('10');
            $('#sort_by').val('id');
            $('#sort_dir').val('desc');
            fetchUsers(1);
        });

        $(document).on('click', '#table-container .pagination a', function (e) {
            e.preventDefault();
            const href = $(this).attr('href');
            if (!href || href === '#') return;
            const url = new URL(href);
            const page = url.searchParams.get('page') || 1;
            fetchUsers(page);
        });

        $(document).on('click', '#table-container a[data-sort]', function (e) {
            e.preventDefault();
            const newSortBy = $(this).data('sort');
            const currentSortBy = $('#sort_by').val();
            const currentSortDir = $('#sort_dir').val();
            let nextDir = 'asc';
            if (newSortBy === currentSortBy) {
                nextDir = currentSortDir === 'asc' ? 'desc' : 'asc';
            }
            $('#sort_by').val(newSortBy);
            $('#sort_dir').val(nextDir);
            fetchUsers(1);
        });
    });
</script>
@endpush

