@extends('layouts.app')

@section('title', '4PS AFS-IS - Inbox')

@section('content')
@php
    $currentSort = $sort ?? (string) request('sort', 'date_forwarded');
    $currentDir = $dir ?? (string) request('dir', 'desc');
    $toggleDir = function (string $col) use ($currentSort, $currentDir) {
        return $currentSort === $col && strtolower($currentDir) === 'asc' ? 'desc' : 'asc';
    };
    $sortUrl = function (string $col) use ($toggleDir) {
        return route('inbox.index', array_merge(request()->all(), ['sort' => $col, 'dir' => $toggleDir($col)]));
    };
@endphp
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Inbox</span>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('inbox.index', array_merge(request()->all(), ['export' => 'csv'])) }}">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>

    <div class="card-body p-2 p-md-3">
        <form method="GET" action="{{ route('inbox.index') }}" class="row g-2 mb-3 align-items-end">
            <input type="hidden" name="sort" value="{{ $currentSort }}">
            <input type="hidden" name="dir" value="{{ $currentDir }}">

            <div class="col-md-4">
                <label for="search" class="form-label small mb-1">Search</label>
                <input type="text" id="search" name="search" class="form-control form-control-sm" placeholder="Document number / Title / Origin office" value="{{ $search ?? request('search') }}" autocomplete="off">
                <div id="search-hint" class="small text-secondary mt-1 d-none">Press Enter to search</div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-sm btn-primary w-100" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
                <a class="btn btn-sm btn-outline-secondary w-100" href="{{ route('inbox.index') }}">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">No.</th>
                        <th style="width: 170px;"><a href="{{ $sortUrl('document_number') }}" class="text-decoration-none">Reference Number</a></th>
                        <th style="width: 220px;"><a href="{{ $sortUrl('drn') }}" class="text-decoration-none">DRN/FETS/ICS/DV/PAR&nbsp;NO.</a></th>
                        <th style="width: 180px;"><a href="{{ $sortUrl('type') }}" class="text-decoration-none">Document Type</a></th>
                        <th style="width: 130px;"><a href="{{ $sortUrl('transaction_type') }}" class="text-decoration-none">Transaction&nbsp;Type</a></th>
                        <th><a href="{{ $sortUrl('title') }}" class="text-decoration-none">Subject</a></th>
                        <th style="width: 220px;"><a href="{{ $sortUrl('origin_office') }}" class="text-decoration-none">From</a></th>
                        <th style="width: 150px;"><a href="{{ $sortUrl('date_forwarded') }}" class="text-decoration-none">Date Forwarded</a></th>
                        <th style="width: 170px;"><a href="{{ $sortUrl('date_received') }}" class="text-decoration-none">Date Received</a></th>
                        <th style="width: 200px;"><a href="{{ $sortUrl('received_in_behalf') }}" class="text-decoration-none">Received In Behalf</a></th>
                        <th style="width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="text-nowrap">{{ ($rows->currentPage() - 1) * $rows->perPage() + $loop->iteration }}</td>
                            <td class="text-nowrap fw-semibold">{{ $row->document_number }}</td>
                            <td class="text-nowrap">{{ $row->drn ?? '' }}</td>
                            <td class="text-nowrap">{{ $row->type ?? '' }}</td>
                            <td class="text-nowrap">
                                @php
                                    $txId = (int) ($row->transaction_type ?? 0);
                                    $txLabel = $txId === 2 ? 'OUTGOING' : ($txId === 1 ? 'INCOMING' : '-');
                                @endphp
                                {{ $txLabel }}
                            </td>
                            <td>{{ $row->title }}</td>
                            <td>{{ $row->origin_office ?? '-' }}</td>
                            <td class="text-nowrap">
                                @if($row->date_forwarded)
                                    {{ \Carbon\Carbon::parse($row->date_forwarded)->format('F j, Y g:i A') }}
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($row->date_received)
                                    {{ \Carbon\Carbon::parse($row->date_received)->format('F j, Y g:i A') }}
                                @endif
                            </td>
                            @php
                                $behalfName = (string) ($row->received_in_behalf_name ?? '');
                                $behalfRaw = $row->received_in_behalf ?? null;
                                $fallback = '';
                                if ($behalfName !== '') {
                                    $fallback = mb_strtoupper($behalfName, 'UTF-8');
                                } elseif (is_numeric($behalfRaw) && (int) $behalfRaw > 0) {
                                    $fallback = 'USER #'.((int) $behalfRaw);
                                } else {
                                    $fallback = '';
                                }
                            @endphp
                            <td>{{ $fallback }}</td>
                            <td class="text-nowrap">
                                @if($row->date_received)
                                    <span class="badge bg-success">Received</span>
                                @else
                                    <form method="POST" action="{{ route('inbox.receive', $row->recipient_id) }}" class="d-inline js-inbox-receive-form">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary js-inbox-receive-btn">
                                            <i class="bi bi-check2-circle"></i> Mark Received
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('incoming-documents.show', ['incoming_document' => $row->document_id, 'from' => 'inbox']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">No documents awaiting reception.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @php
            $from = $rows->firstItem() ?? 0;
            $to = $rows->lastItem() ?? 0;
            $total = $rows->total() ?? 0;
        @endphp
        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
            <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
            <div>{!! $rows->links() !!}</div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    $(function () {
        let activeSearch = $('#search').val() || '';
        let searchReadyTimer = null;

        function setSearchReady(isReady) {
            $('#search-hint').toggleClass('d-none', !isReady);
            $('#search').toggleClass('border-warning', isReady);
        }

        $('#search').on('input', function () {
            const current = $(this).val() || '';
            clearTimeout(searchReadyTimer);
            if (current === activeSearch) {
                setSearchReady(false);
                return;
            }
            searchReadyTimer = setTimeout(function () {
                setSearchReady(true);
            }, 400);
        });

        $('#search').on('keydown', function (e) {
            if (e.key !== 'Enter') return;
            activeSearch = $(this).val() || '';
            setSearchReady(false);
        });

        function extractErrorMessage(xhr) {
            if (!xhr) return 'Request failed.';
            if (xhr.responseJSON) {
                if (typeof xhr.responseJSON.message === 'string' && xhr.responseJSON.message.trim() !== '') {
                    return xhr.responseJSON.message;
                }
                if (xhr.responseJSON.errors && typeof xhr.responseJSON.errors === 'object') {
                    const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                    if (firstKey && Array.isArray(xhr.responseJSON.errors[firstKey]) && xhr.responseJSON.errors[firstKey][0]) {
                        return String(xhr.responseJSON.errors[firstKey][0]);
                    }
                }
            }
            if (typeof xhr.responseText === 'string' && xhr.responseText.trim() !== '') {
                return xhr.responseText;
            }
            return 'Request failed.';
        }

        $(document).on('submit', '.js-inbox-receive-form', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $btn = $form.find('.js-inbox-receive-btn');

            if ($btn.data('loading') === 1) return;

            $.confirm({
                title: 'Confirm Action',
                content: 'Are you sure you want to mark this document as received?',
                buttons: {
                    confirm: {
                        text: 'Confirm',
                        btnClass: 'btn-primary',
                        action: function () {
                            const originalHtml = $btn.html();
                            $btn.data('loading', 1);
                            $btn.prop('disabled', true);
                            $btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Processing');

                            $.ajax({
                                url: $form.attr('action'),
                                method: 'POST',
                                data: $form.serialize(),
                                dataType: 'json',
                                headers: { 'Accept': 'application/json' }
                            }).done(function (data) {
                                if (data && data.success === true) {
                                    $form.replaceWith('<span class="badge bg-success">Received</span>');
                                    toastr.success('Marked as received.');
                                    return;
                                }

                                $btn.html(originalHtml);
                                $btn.prop('disabled', false);
                                $btn.data('loading', 0);
                                toastr.error('Failed to mark as received.');
                            }).fail(function (xhr) {
                                $btn.html(originalHtml);
                                $btn.prop('disabled', false);
                                $btn.data('loading', 0);
                                toastr.error(extractErrorMessage(xhr));
                            });
                        }
                    },
                    cancel: {
                        text: 'Cancel'
                    }
                }
            });
        });
    });
</script>
@endpush
@endsection
