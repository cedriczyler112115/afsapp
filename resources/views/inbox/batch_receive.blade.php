@extends('layouts.app')

@section('title', '4PS AFS-IS - Batch Receiving')

@section('content')
@php
    $currentSort = $sort ?? (string) request('sort', 'date_forwarded');
    $currentDir = $dir ?? (string) request('dir', 'desc');
    $toggleDir = function (string $col) use ($currentSort, $currentDir) {
        return $currentSort === $col && strtolower($currentDir) === 'asc' ? 'desc' : 'asc';
    };
    $sortUrl = function (string $col) use ($toggleDir) {
        return route('inbox.batch', array_merge(request()->all(), ['sort' => $col, 'dir' => $toggleDir($col)]));
    };
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Route</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_received_docs" title="List of Received Documents" aria-label="List of Received Documents">
            <i class="bi bi-list-check"></i> Show Routes
        </button>
    </div>

    <div class="card-body p-2 p-md-3">
        <form method="GET" action="{{ route('inbox.batch') }}" class="row g-2 mb-3 align-items-end">
            <input type="hidden" name="sort" value="{{ $currentSort }}">
            <input type="hidden" name="dir" value="{{ $currentDir }}">

            {{-- @if(isset($users) && $users->count() > 0) --}}
                <div class="col-md-3">
                    <label for="user_id" class="form-label small mb-1">Recipient</label>
                    <select id="user_id" name="user_id" class="form-select form-select-sm">
                        <option value="" {{ (int) ($userId ?? 0) === (int) ($authUserId ?? 0) ? 'selected' : '' }}>My Inbox</option>
                        @forelse(($users ?? collect()) as $u)
                            <option value="{{ $u->id }}" {{ (int) ($userId ?? 0) === (int) $u->id ? 'selected' : '' }}>
                                {{ mb_strtoupper((string) $u->name, 'UTF-8') }}
                            </option>
                        @empty
                            <option value="" disabled>No other users found</option>
                        @endforelse
                    </select>
                </div>
            {{-- @else
                <input type="hidden" name="user_id" value="{{ (int) ($userId ?? 0) }}">
            @endif --}}

            <div class="col-md-2">
                <label for="document_type_id" class="form-label small mb-1">Type</label>
                <select id="document_type_id" name="document_type_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(($types ?? collect()) as $t)
                        <option value="{{ $t->id }}" {{ (int) ($typeId ?? 0) === (int) $t->id ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="search" class="form-label small mb-1">Search</label>
                <input type="text" id="search" name="search" class="form-control form-control-sm" placeholder="Document number / DRN / Title / Origin office" value="{{ $search ?? request('search') }}" autocomplete="off">
            </div>

            <div class="col-md-1">
                <label for="per_page" class="form-label small mb-1">Show</label>
                <select id="per_page" name="per_page" class="form-select form-select-sm">
                    @foreach([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ (int) ($perPage ?? 25) === (int) $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-sm btn-primary w-100" type="submit">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a class="btn btn-sm btn-outline-secondary w-100" href="{{ route('inbox.batch') }}">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>

        <div class="border rounded-2 p-2 mb-2 d-flex flex-wrap gap-2 align-items-end">
            <div>
                <button type="button" class="btn btn-sm btn-primary" id="btn_create_batch" disabled>
                    <i class="bi bi-collection"></i> Create Routes (<span id="selected_count">0</span>)
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 44px;" class="text-center">
                            <input type="checkbox" id="select_all" class="form-check-input" style="width: 1.35rem; height: 1.35rem;">
                        </th>
                        <th style="width: 170px;"><a href="{{ $sortUrl('document_number') }}" class="text-decoration-none">Document Number</a></th>
                        <th style="width: 220px;"><a href="{{ $sortUrl('drn') }}" class="text-decoration-none">DRN/FETS/ICS/DV/PAR&nbsp;NO.</a></th>
                        <th style="width: 180px;"><a href="{{ $sortUrl('type') }}" class="text-decoration-none">Type</a></th>
                        <th style="width: 130px;"><a href="{{ $sortUrl('transaction_type') }}" class="text-decoration-none">Transaction&nbsp;Type</a></th>
                        <th><a href="{{ $sortUrl('title') }}" class="text-decoration-none">Title</a></th>
                        <th style="width: 220px;"><a href="{{ $sortUrl('origin_office') }}" class="text-decoration-none">Origin Office</a></th>
                        <th style="width: 150px;"><a href="{{ $sortUrl('date_forwarded') }}" class="text-decoration-none">Date Forwarded</a></th>
                        <th style="width: 220px;">Recipient</th>
                        <th style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr data-recipient-id="{{ (int) $row->recipient_id }}" data-doc-number="{{ e((string) $row->document_number) }}" data-doc-drn="{{ e((string) ($row->drn ?? '')) }}" data-doc-title="{{ e((string) $row->title) }}" data-doc-type="{{ e((string) ($row->type ?? '')) }}" data-doc-transaction-type="{{ (int) ($row->transaction_type ?? 0) }}" data-origin-office="{{ e((string) ($row->origin_office ?? '')) }}">
                            <td class="text-center">
                                <input type="checkbox" class="row-check form-check-input" style="width: 1.35rem; height: 1.35rem;" value="{{ (int) $row->recipient_id }}">
                            </td>
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
                            <td class="text-nowrap">{{ $row->recipient_name ? mb_strtoupper((string) $row->recipient_name, 'UTF-8') : '-' }}</td>
                            <td class="text-nowrap">
                                @if((int) ($authUserId ?? 0) === (int) ($userId ?? 0))
                                    <a href="{{ route('incoming-documents.show', ['incoming_document' => $row->document_id, 'from' => 'inbox']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @else
                                    <a href="{{ route('incoming-documents.show', ['incoming_document' => $row->document_id, 'from' => 'inbox']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">No forwarded documents pending receipt.</td>
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

<div class="modal fade" id="createBatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Selected Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Selected Documents</div>
                    <div class="badge bg-primary"><span id="modal_selected_count">0</span></div>
                </div>
                <div class="border rounded p-2 bg-light">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 170px;">Document Number</th>
                                    <th style="width: 220px;">DRN/FETS/ICS/DV/PAR&nbsp;NO.</th>
                                    <th style="width: 160px;">Type</th>
                                    <th style="width: 130px;">Transaction Type</th>
                                    <th>Subject</th>
                                    <th style="width: 220px;">Origin Office</th>
                                </tr>
                            </thead>
                            <tbody id="modal_selected_tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_modal_create">Create</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="receivedDocsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">List of Received Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="received_docs_modal_body" class="small text-muted">Loading...</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createPinModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" aria-labelledby="createPinModalTitle">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPinModalTitle">Create PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="create_pin_sr" class="visually-hidden" aria-live="polite"></div>
                <div id="create_pin_error" class="alert alert-danger py-2 d-none" role="alert" aria-live="assertive"></div>

                <div id="pin_doc_details_create" class="border rounded-3 p-2 mb-3 d-none" aria-label="Document details">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-wrap">
                        <div class="small text-muted fw-semibold">Documents in Batch</div>
                        <div id="pin_doc_count_create" class="small text-muted"></div>
                    </div>
                    <div id="pin_doc_loading_create" class="small text-muted d-none" aria-live="polite">Loading documents…</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" aria-label="Batch documents">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;">Origin Office</th>
                                    <th style="width: 25%;">Type</th>
                                    <th style="width: 15%;">Transaction Type</th>
                                    <th>Subject</th>
                                </tr>
                            </thead>
                            <tbody id="pin_doc_tbody_create"></tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="create_pin" class="form-label">PIN</label>
                    <div class="pin-dot-field">
                        <input id="create_pin" type="text" class="form-control pin-mask-input pin-dot-input" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" aria-describedby="create_pin_help" aria-label="Create PIN" data-raw="">
                        <div class="pin-dot-overlay" aria-hidden="true">
                            <span class="pin-dot"></span>
                            <span class="pin-dot"></span>
                            <span class="pin-dot"></span>
                            <span class="pin-dot"></span>
                        </div>
                    </div>
                    <div id="create_pin_help" class="form-text">Enter a 4-digit PIN.</div>
                </div>

                <div class="mb-0">
                    <label for="create_pin_confirm" class="form-label">Confirm PIN</label>
                    <div class="pin-dot-field">
                        <input id="create_pin_confirm" type="text" class="form-control pin-mask-input pin-dot-input" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" aria-label="Confirm PIN" data-raw="">
                        <div class="pin-dot-overlay" aria-hidden="true">
                            <span class="pin-dot"></span>
                            <span class="pin-dot"></span>
                            <span class="pin-dot"></span>
                            <span class="pin-dot"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_create_pin_save">Save PIN</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="enterPinModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" aria-labelledby="enterPinModalTitle">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="enterPinModalTitle">Enter PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="enter_pin_sr" class="visually-hidden" aria-live="polite"></div>
                <div id="enter_pin_error" class="alert alert-danger py-2 d-none" role="alert" aria-live="assertive"></div>
                <div id="enter_pin_lock" class="alert alert-warning py-2 d-none" role="alert" aria-live="assertive"></div>

                <div id="pin_doc_details_enter" class="border rounded-3 p-2 mb-3 d-none" aria-label="Document details">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-wrap">
                        <div class="small text-muted fw-semibold">Documents in Batch</div>
                        <div id="pin_doc_count_enter" class="small text-muted"></div>
                    </div>
                    <div id="pin_doc_loading_enter" class="small text-muted d-none" aria-live="polite">Loading documents…</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" aria-label="Batch documents">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;">Origin Office</th>
                                    <th style="width: 25%;">Type</th>
                                    <th style="width: 15%;">Transaction Type</th>
                                    <th>Subject</th>
                                </tr>
                            </thead>
                            <tbody id="pin_doc_tbody_enter"></tbody>
                        </table>
                    </div>
                </div>
                <div id="pin_doc_details_enter2" class="border rounded-3 p-2 mb-3" aria-label="Receive in behalf">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="received_in_behalf_toggle" style="width: 1.35rem; height: 1.35rem;">
                        <label class="form-check-label" for="received_in_behalf_toggle">&nbsp;<font size=2><b>Received in behalf</b></font></label>
                    </div>

                    <div id="received_in_behalf_select_wrap" class="mt-3 mb-3 d-none">
                        <select id="received_in_behalf_user_id" class="form-select form-select-sm" aria-label="Select staff to receive">
                            <option value=""></option>
                        </select>
                        <div id="received_in_behalf_error" class="text-danger small mt-1 d-none" aria-live="assertive"></div>
                    </div>

                    <a href="#" id="reset_pin_link" class="d-inline-block small text-decoration-underline mt-1" role="button" aria-label="Reset PIN">Reset PIN</a>

                    <div id="behalf_pin_register_wrap" class="mt-3 d-none" aria-label="PIN registration">
                        <div id="behalf_pin_register_error" class="alert alert-danger py-2 d-none" role="alert" aria-live="assertive"></div>

                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label for="behalf_create_pin" class="form-label mb-1">Register PIN</label>
                                <div class="pin-dot-field">
                                    <input id="behalf_create_pin" type="text" class="form-control form-control-sm pin-mask-input pin-dot-input" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" aria-label="Register PIN" data-raw="">
                                    <div class="pin-dot-overlay" aria-hidden="true">
                                        <span class="pin-dot"></span>
                                        <span class="pin-dot"></span>
                                        <span class="pin-dot"></span>
                                        <span class="pin-dot"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="behalf_create_pin_confirm" class="form-label mb-1">Confirm PIN</label>
                                <div class="pin-dot-field">
                                    <input id="behalf_create_pin_confirm" type="text" class="form-control form-control-sm pin-mask-input pin-dot-input" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" aria-label="Confirm PIN" data-raw="">
                                    <div class="pin-dot-overlay" aria-hidden="true">
                                        <span class="pin-dot"></span>
                                        <span class="pin-dot"></span>
                                        <span class="pin-dot"></span>
                                        <span class="pin-dot"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm mt-2 w-100" id="btn_behalf_pin_register">Register PIN</button>
                        <div class="small text-muted mt-1">After registering, enter the new PIN to receive.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="enter_pin" class="form-label">PIN</label>
                    <div id="pin_source_indicator" class="small text-muted mb-2">Matching PIN: Recipient</div>
                    <div class="pin-dot-field">
                        <input id="enter_pin" type="text" class="form-control pin-mask-input pin-dot-input" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="current-password" aria-label="Enter PIN" data-raw="">
                        <div class="pin-dot-overlay" aria-hidden="true">
                            <span class="pin-dot"></span>
                            <span class="pin-dot"></span>
                            <span class="pin-dot"></span>
                            <span class="pin-dot"></span>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-success w-100" id="btn_enter_pin_receive" disabled>Receive</button>
            </div>
        </div>
    </div>
</div>

<style>
    body.pin-overlay-active #receivedDocsModal.show .modal-dialog {
        filter: blur(6px);
        opacity: 0.7;
        will-change: filter, opacity;
        transition: filter 160ms ease, opacity 160ms ease;
    }

    body.pin-overlay-active #receivedDocsModal.show .modal-dialog,
    body.pin-overlay-active #receivedDocsModal.show .modal-content {
        pointer-events: none;
    }

    .pin-modal-backdrop.show {
        background-color: rgba(0, 0, 0, 0.4);
        opacity: 1;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 1060;
    }

    #createPinModal,
    #enterPinModal {
        z-index: 1065;
    }

    #createPinModal .modal-content,
    #enterPinModal .modal-content {
        box-shadow: 0 1.25rem 2.5rem rgba(0, 0, 0, 0.25);
    }

    .pin-modal-positioned .modal-dialog {
        position: fixed;
        margin: 0;
        max-width: none;
        width: var(--pin-left-width, auto);
        height: var(--pin-left-height, auto);
        left: var(--pin-left, auto);
        top: var(--pin-top, auto);
        transition: top 160ms ease, left 160ms ease, width 160ms ease, height 160ms ease;
    }

    .pin-modal-positioned .modal-dialog.modal-dialog-centered {
        display: block;
        min-height: 0;
    }

    .pin-modal-positioned .modal-content {
        margin-top: 12px;
        height: calc(100% - 12px);
    }

    .pin-modal-positioned.pin-modal-small .modal-dialog {
        height: auto;
        left: var(--pin-center-x, 50%);
        top: var(--pin-center-y, 50%);
        width: min(360px, calc(var(--pin-parent-width, 360px) - 24px));
        max-width: calc(100vw - 24px);
        max-height: calc(var(--pin-parent-height, 100vh) - 24px);
        transform: translate(-50%, -50%) translateY(8px);
    }

    .pin-modal-positioned.pin-modal-small .modal-content {
        margin-top: 0;
        height: auto;
        max-height: calc(var(--pin-parent-height, 100vh) - 24px);
        overflow: auto;
    }

    .pin-mask-input {
        font-size: 2.25rem;
        letter-spacing: 0.45em;
        text-align: center;
        padding-top: 0.65rem;
        padding-bottom: 0.65rem;
        transition: box-shadow 160ms ease, transform 160ms ease;
        caret-color: transparent;
        user-select: none;
    }

    .pin-dot-field {
        --msk-pin-dot-radius: 18px;
        --msk-pin-dot-size: calc(var(--msk-pin-dot-radius) * 2);
        --msk-pin-dot-gap: 12px;
        position: relative;
    }

    .pin-dot-input {
        color: transparent;
        letter-spacing: 0;
        text-shadow: none;
    }

    .pin-dot-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--msk-pin-dot-gap);
        pointer-events: none;
    }

    .pin-dot {
        width: var(--msk-pin-dot-size);
        height: var(--msk-pin-dot-size);
        border-radius: 999px;
        border: 2px solid var(--bs-border-color);
        background: transparent;
        box-sizing: border-box;
        transition: background-color 140ms ease, border-color 140ms ease, transform 140ms ease;
    }

    .pin-dot.pin-dot-filled {
        background: var(--bs-dark);
        border-color: var(--bs-dark);
        transform: scale(1.02);
    }

    .pin-mask-input.pin-mask-bounce {
        transform: scale(1.02);
    }
</style>

@push('scripts')
<script>
    $(function () {
        const targetUserId = {{ (int) ($userId ?? 0) }};
        const receivedDocsBaseUrl = "{{ route('inbox.batch.received') }}";
        const pinStatusUrl = "{{ route('inbox.batch.pin.status') }}";
        const pinCreateUrl = "{{ route('inbox.batch.pin.create') }}";
        const pinResetUrl = "{{ url('inbox/batch-receive/pin/reset') }}";
        const inboxPinCreateUrl = "{{ url('inbox/pin/create') }}";
        const batchDocsUrlTemplate = "{{ route('inbox.batch.documents', ['batch' => '__BATCH__']) }}";
        const pinReceiveUrlTemplate = "{{ route('inbox.batch.receive', ['batch' => '__BATCH__']) }}";
        const activeUsersUrl = "{{ route('inbox.lookups.active-users') }}";
        const createBatchModalEl = document.getElementById('createBatchModal');
        const createBatchModal = createBatchModalEl ? new bootstrap.Modal(createBatchModalEl) : null;
        const receivedDocsModalEl = document.getElementById('receivedDocsModal');
        const receivedDocsModal = receivedDocsModalEl ? new bootstrap.Modal(receivedDocsModalEl) : null;
        const createPinModalEl = document.getElementById('createPinModal');
        const createPinModal = createPinModalEl ? new bootstrap.Modal(createPinModalEl) : null;
        const enterPinModalEl = document.getElementById('enterPinModal');
        const enterPinModal = enterPinModalEl ? new bootstrap.Modal(enterPinModalEl) : null;
        let activeBatchId = null;
        let activeDocDetails = null;
        let lockTimer = null;
        let overlayResizeHandler = null;

        function updateSelectedUI() {
            const count = $('.row-check:checked').length;
            $('#selected_count').text(count);
            $('#btn_create_batch').prop('disabled', count <= 0);
        }

        $('#select_all').on('change', function () {
            $('.row-check').prop('checked', $(this).is(':checked'));
            updateSelectedUI();
        });

        $(document).on('change', '.row-check', function () {
            const all = $('.row-check').length;
            const checked = $('.row-check:checked').length;
            $('#select_all').prop('checked', all > 0 && checked === all);
            updateSelectedUI();
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
            return 'Request failed.';
        }

        function selectedRows() {
            return $('.row-check:checked').map(function () {
                const id = parseInt($(this).val(), 10);
                const $tr = $(this).closest('tr');
                const docNumber = $tr.data('doc-number') || '';
                const drn = $tr.data('doc-drn') || '';
                const type = $tr.data('doc-type') || '';
                const transactionType = parseInt($tr.data('doc-transaction-type'), 10) || 0;
                const subject = $tr.data('doc-title') || '';
                const originOffice = $tr.data('origin-office') || '';
                return { id, docNumber, drn, type, transactionType, subject, originOffice };
            }).get();
        }

        function renderModalList(rows) {
            $('#modal_selected_count').text(rows.length);
            const $tbody = $('#modal_selected_tbody');
            $tbody.empty();
            rows.forEach(function (row) {
                const safeDocNumber = $('<div>').text(row.docNumber || '').html();
                const safeDrn = $('<div>').text(row.drn || '').html();
                const safeType = $('<div>').text(row.type || '').html();
                const txLabel = row.transactionType === 2 ? 'OUTGOING' : (row.transactionType === 1 ? 'INCOMING' : '-');
                const safeTx = $('<div>').text(txLabel).html();
                const safeSubject = $('<div>').text(row.subject || '').html();
                const safeOrigin = $('<div>').text(row.originOffice || '').html();

                $tbody.append(
                    '<tr>' +
                        '<td class="text-nowrap fw-semibold">' + (safeDocNumber !== '' ? safeDocNumber : '-') + '</td>' +
                        '<td class="text-nowrap">' + (safeDrn !== '' ? safeDrn : '-') + '</td>' +
                        '<td class="text-nowrap">' + (safeType !== '' ? safeType : '-') + '</td>' +
                        '<td class="text-nowrap">' + safeTx + '</td>' +
                        '<td>' + (safeSubject !== '' ? safeSubject : '-') + '</td>' +
                        '<td>' + (safeOrigin !== '' ? safeOrigin : '-') + '</td>' +
                    '</tr>'
                );
            });
        }

        function loadReceivedDocs(url) {
            const $body = $('#received_docs_modal_body');
            $body.html('<div class="text-muted small">Loading...</div>');
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'html',
                headers: { 'Accept': 'text/html' }
            }).done(function (html) {
                $body.html(html);
            }).fail(function (xhr) {
                $body.html('<div class="text-danger small">' + $('<div>').text(extractErrorMessage(xhr)).html() + '</div>');
            });
        }

        function digitsOnly(value) {
            return String(value || '').replace(/[^0-9]/g, '').slice(0, 4);
        }

        function renderPinDocDetails() {
            const docs = Array.isArray(activeDocDetails && activeDocDetails.documents) ? activeDocDetails.documents : [];
            const isLoading = !!(activeDocDetails && activeDocDetails.loading);

            const setBlock = function (prefix) {
                const $wrap = $('#pin_doc_details_' + prefix);
                const $loading = $('#pin_doc_loading_' + prefix);
                const $count = $('#pin_doc_count_' + prefix);
                const $tbody = $('#pin_doc_tbody_' + prefix);

                if (isLoading) {
                    $count.text('');
                    $tbody.empty();
                    $loading.removeClass('d-none');
                    $wrap.removeClass('d-none');
                    return;
                }

                $loading.addClass('d-none');
                $tbody.empty();

                if (!docs.length) {
                    $wrap.addClass('d-none');
                    $count.text('');
                    return;
                }

                $count.text(docs.length + ' document' + (docs.length === 1 ? '' : 's'));
                docs.forEach(function (d) {
                    const origin = $('<div>').text(String(d.origin_office || '-') || '-').html();
                    const type = $('<div>').text(String(d.type || '-') || '-').html();
                    const txId = parseInt(d.transaction_type, 10) || 0;
                    const txLabel = txId === 2 ? 'OUTGOING' : (txId === 1 ? 'INCOMING' : '-');
                    const tx = $('<div>').text(txLabel).html();
                    const subject = $('<div>').text(String(d.subject || '-') || '-').html();
                    $tbody.append(
                        '<tr>' +
                            '<td>' + origin + '</td>' +
                            '<td class="text-nowrap">' + type + '</td>' +
                            '<td class="text-nowrap">' + tx + '</td>' +
                            '<td>' + subject + '</td>' +
                        '</tr>'
                    );
                });

                $wrap.removeClass('d-none');
            };

            setBlock('create');
            setBlock('enter');
        }

        function fetchBatchDocuments(batchId) {
            const url = batchDocsUrlTemplate.replace('__BATCH__', String(batchId));
            activeDocDetails = { loading: true, documents: [] };
            renderPinDocDetails();

            return $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                headers: { 'Accept': 'application/json' }
            }).done(function (resp) {
                if (!resp || !resp.success || !Array.isArray(resp.documents)) {
                    activeDocDetails = { loading: false, documents: [] };
                    renderPinDocDetails();
                    return;
                }
                activeDocDetails = { loading: false, documents: resp.documents };
                renderPinDocDetails();
            }).fail(function () {
                activeDocDetails = { loading: false, documents: [] };
                renderPinDocDetails();
            });
        }

        function getRawPin($input) {
            return String($input.attr('data-raw') || '');
        }

        function setRawPin($input, digits) {
            const d = digitsOnly(digits);
            $input.attr('data-raw', d);
            $input.val('');
            renderPinDots($input);
            $input.addClass('pin-mask-bounce');
            window.setTimeout(function () {
                $input.removeClass('pin-mask-bounce');
            }, 140);
        }

        function renderPinDots($input) {
            const raw = getRawPin($input);
            const $dots = $input.closest('.pin-dot-field').find('.pin-dot');
            if ($dots.length !== 4) return;
            $dots.each(function (idx) {
                $(this).toggleClass('pin-dot-filled', idx < raw.length);
            });
        }

        function attachPinMaskHandlers($input) {
            $input.on('keydown', function (e) {
                const key = e.key;
                const $el = $(this);
                let raw = getRawPin($el);

                if (key === 'Tab' || key === 'Shift' || key === 'Escape') {
                    return;
                }
                if (key === 'Enter') {
                    if (this.id === 'enter_pin') {
                        if (!$('#btn_enter_pin_receive').prop('disabled')) {
                            $('#btn_enter_pin_receive').trigger('click');
                        }
                    } else {
                        $('#btn_create_pin_save').trigger('click');
                    }
                    e.preventDefault();
                    return;
                }
                if (key === 'Backspace') {
                    raw = raw.slice(0, -1);
                    setRawPin($el, raw);
                    if (this.id === 'enter_pin') {
                        $('#btn_enter_pin_receive').prop('disabled', raw.length !== 4 || $('#enter_pin').prop('disabled'));
                    }
                    e.preventDefault();
                    return;
                }
                if (key === 'Delete') {
                    setRawPin($el, '');
                    if (this.id === 'enter_pin') {
                        $('#btn_enter_pin_receive').prop('disabled', true);
                    }
                    e.preventDefault();
                    return;
                }
                if (key && key.length === 1) {
                    if (!/^[0-9]$/.test(key)) {
                        e.preventDefault();
                        return;
                    }
                    if (raw.length >= 4) {
                        e.preventDefault();
                        return;
                    }
                    raw += key;
                    setRawPin($el, raw);
                    if (this.id === 'enter_pin') {
                        $('#btn_enter_pin_receive').prop('disabled', raw.length !== 4 || $('#enter_pin').prop('disabled'));
                    }
                    e.preventDefault();
                }
            });

            $input.on('input', function () {
                const $el = $(this);
                const val = String($el.val() || '');
                if (val.indexOf('•') !== -1) {
                    return;
                }
                const digits = digitsOnly(val);
                setRawPin($el, digits);
                if (this.id === 'enter_pin') {
                    $('#btn_enter_pin_receive').prop('disabled', digits.length !== 4 || $('#enter_pin').prop('disabled'));
                }
            });

            $input.on('paste', function (e) {
                const text = (e.originalEvent && e.originalEvent.clipboardData)
                    ? e.originalEvent.clipboardData.getData('text')
                    : '';
                const digits = digitsOnly(text);
                setRawPin($(this), digits);
                if (this.id === 'enter_pin') {
                    $('#btn_enter_pin_receive').prop('disabled', digits.length !== 4 || $('#enter_pin').prop('disabled'));
                }
                e.preventDefault();
            });

            $input.on('focus', function () {
                $(this).val('');
                renderPinDots($(this));
            });
        }

        function setCreatePinError(msg) {
            const $el = $('#create_pin_error');
            if (msg) {
                $el.removeClass('d-none').text(msg);
                $('#create_pin_sr').text(msg);
            } else {
                $el.addClass('d-none').text('');
                $('#create_pin_sr').text('');
            }
        }

        function setEnterPinError(msg) {
            const $el = $('#enter_pin_error');
            if (msg) {
                $el.removeClass('d-none').text(msg);
                $('#enter_pin_sr').text(msg);
            } else {
                $el.addClass('d-none').text('');
                $('#enter_pin_sr').text('');
            }
        }

        function setReceivedInBehalfError(msg) {
            const $el = $('#received_in_behalf_error');
            if (!$el.length) return;
            if (msg) {
                $el.removeClass('d-none').text(msg);
            } else {
                $el.addClass('d-none').text('');
            }
        }

        function setBehalfRegisterError(msg) {
            const $el = $('#behalf_pin_register_error');
            if (!$el.length) return;
            if (msg) {
                $el.removeClass('d-none').text(msg);
            } else {
                $el.addClass('d-none').text('');
            }
        }

        let receivedInBehalfSelectReady = false;
        let behalfSelectedHasPin = null;

        function updatePinSourceIndicator() {
            const $el = $('#pin_source_indicator');
            if (!$el.length) return;

            const checked = $('#received_in_behalf_toggle').is(':checked');
            if (!checked) {
                $el.text('Matching PIN: Recipient');
                return;
            }

            const $select = $('#received_in_behalf_user_id');
            let text = 'Selected staff';
            let hasPin = null;
            if (receivedInBehalfSelectReady && $select.length && typeof $select.select2 === 'function') {
                const data = $select.select2('data');
                if (Array.isArray(data) && data[0]) {
                    text = String(data[0].text || text);
                    if (Object.prototype.hasOwnProperty.call(data[0], 'has_pin')) {
                        hasPin = !!data[0].has_pin;
                    }
                }
            }

            if (hasPin === false) {
                $el.text('Matching PIN: ' + text + ' (PIN not set)');
                return;
            }
            $el.text('Matching PIN: ' + text);
        }

        function clearBehalfRegisterInputs() {
            setBehalfRegisterError('');
            setRawPin($('#behalf_create_pin'), '');
            setRawPin($('#behalf_create_pin_confirm'), '');
        }

        function setBehalfRegisterVisible(visible) {
            const $wrap = $('#behalf_pin_register_wrap');
            if (!$wrap.length) return;
            if (visible) {
                $wrap.removeClass('d-none');
            } else {
                $wrap.addClass('d-none');
                clearBehalfRegisterInputs();
            }
        }

        function readSelectedBehalfHasPin() {
            if (!$('#received_in_behalf_toggle').is(':checked')) {
                behalfSelectedHasPin = null;
                return null;
            }

            const $select = $('#received_in_behalf_user_id');
            const id = parseInt(String($select.val() || ''), 10) || 0;
            if (id <= 0) {
                behalfSelectedHasPin = null;
                return null;
            }

            if (behalfSelectedHasPin === true) {
                return true;
            }

            if (receivedInBehalfSelectReady && typeof $select.select2 === 'function') {
                const data = $select.select2('data');
                if (Array.isArray(data) && data[0] && Object.prototype.hasOwnProperty.call(data[0], 'has_pin')) {
                    behalfSelectedHasPin = !!data[0].has_pin;
                    return behalfSelectedHasPin;
                }
            }

            return behalfSelectedHasPin;
        }

        function refreshReceivedInBehalfSelectedPinState(userId) {
            const $select = $('#received_in_behalf_user_id');
            if (!receivedInBehalfSelectReady || !$select.length || typeof $select.select2 !== 'function') {
                return;
            }

            const selectedId = parseInt(String($select.val() || ''), 10) || 0;
            if (selectedId <= 0 || selectedId !== userId) {
                return;
            }

            const data = $select.select2('data');
            if (!Array.isArray(data) || !data[0]) {
                return;
            }

            const text = String(data[0].text || '').replace(/\s*\(PIN not set\)\s*$/i, '').trim();
            const newData = {
                id: userId,
                text: text || String(data[0].text || ''),
                has_pin: true
            };

            $select.find('option[value="' + String(userId) + '"]').remove();
            const opt = new Option(newData.text, String(userId), true, true);
            $(opt).data('data', newData);
            $select.append(opt).trigger('change');
            behalfSelectedHasPin = true;
            updatePinSourceIndicator();
        }

        function syncBehalfPinUI() {
            const receivedInBehalf = $('#received_in_behalf_toggle').is(':checked');
            const selectedId = parseInt(String($('#received_in_behalf_user_id').val() || ''), 10) || 0;
            const hasPin = readSelectedBehalfHasPin();

            if (!receivedInBehalf) {
                setBehalfRegisterVisible(false);
                return;
            }

            if (selectedId <= 0) {
                setBehalfRegisterVisible(false);
                return;
            }

            setBehalfRegisterVisible(hasPin === false);
        }

        function initReceivedInBehalfSelect() {
            if (receivedInBehalfSelectReady) return;
            const $select = $('#received_in_behalf_user_id');
            if (!$select.length || !$.fn.select2) return;

            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select staff to receive',
                allowClear: true,
                dropdownParent: $('#enterPinModal'),
                ajax: {
                    url: activeUsersUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1,
                            exclude_user_id: targetUserId || null
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: (data && Array.isArray(data.results)) ? data.results : [],
                            pagination: { more: !!(data && data.pagination && data.pagination.more) }
                        };
                    },
                    cache: false
                }
            });

            receivedInBehalfSelectReady = true;
        }

        function resetReceivedInBehalfUI() {
            setReceivedInBehalfError('');
            $('#received_in_behalf_toggle').prop('checked', false);
            $('#received_in_behalf_select_wrap').addClass('d-none');
            const $select = $('#received_in_behalf_user_id');
            if ($select.length) {
                $select.val(null).trigger('change');
            }
            behalfSelectedHasPin = null;
            setBehalfRegisterVisible(false);
            updatePinSourceIndicator();
        }

        function clearLockTimer() {
            if (lockTimer) {
                window.clearInterval(lockTimer);
                lockTimer = null;
            }
        }

        function setEnterPinLocked(seconds) {
            clearLockTimer();
            const $lock = $('#enter_pin_lock');
            const $pin = $('#enter_pin');
            const $btn = $('#btn_enter_pin_receive');

            if (!seconds || seconds <= 0) {
                $lock.addClass('d-none').text('');
                $pin.prop('disabled', false);
                $btn.prop('disabled', digitsOnly(getRawPin($pin)).length !== 4);
                return;
            }

            $pin.prop('disabled', true);
            $btn.prop('disabled', true);
            $lock.removeClass('d-none').text('Too many failed attempts. Try again in ' + seconds + ' seconds.');

            let remaining = seconds;
            lockTimer = window.setInterval(function () {
                remaining -= 1;
                if (remaining <= 0) {
                    clearLockTimer();
                    setEnterPinLocked(0);
                    return;
                }
                $lock.text('Too many failed attempts. Try again in ' + remaining + ' seconds.');
            }, 1000);
        }

        function openCreatePinModal() {
            setCreatePinError('');
            renderPinDocDetails();
            setRawPin($('#create_pin'), '');
            setRawPin($('#create_pin_confirm'), '');
            if (createPinModal) {
                createPinModal.show();
            }
        }

        function openEnterPinModal(lockedSeconds) {
            setEnterPinError('');
            resetReceivedInBehalfUI();
            renderPinDocDetails();
            setRawPin($('#enter_pin'), '');
            $('#btn_enter_pin_receive').prop('disabled', true);
            if (enterPinModal) {
                enterPinModal.show();
            }
            setEnterPinLocked(lockedSeconds || 0);
        }

        function applyPinOverlay($pinModalEl) {
            const parentModalEl = document.getElementById('receivedDocsModal');
            const parentDialog = parentModalEl ? parentModalEl.querySelector('.modal-dialog') : null;
            const pinDialog = $pinModalEl ? $pinModalEl.querySelector('.modal-dialog') : null;

            document.body.classList.add('pin-overlay-active');
            if (parentModalEl) {
                parentModalEl.setAttribute('aria-hidden', 'true');
                parentModalEl.setAttribute('inert', '');
            }

            if (pinDialog && parentDialog && parentModalEl && parentModalEl.classList.contains('show')) {
                const rect = parentDialog.getBoundingClientRect();
                $pinModalEl.classList.add('pin-modal-positioned');
                $pinModalEl.classList.toggle('pin-modal-small', pinDialog.classList.contains('modal-sm'));
                pinDialog.style.setProperty('--pin-left', rect.left + 'px');
                pinDialog.style.setProperty('--pin-top', rect.top + 'px');
                pinDialog.style.setProperty('--pin-left-width', rect.width + 'px');
                pinDialog.style.setProperty('--pin-left-height', rect.height + 'px');
                pinDialog.style.setProperty('--pin-parent-width', rect.width + 'px');
                pinDialog.style.setProperty('--pin-parent-height', rect.height + 'px');
                pinDialog.style.setProperty('--pin-center-x', (rect.left + (rect.width / 2)) + 'px');
                pinDialog.style.setProperty('--pin-center-y', (rect.top + (rect.height / 2)) + 'px');
            } else if ($pinModalEl) {
                $pinModalEl.classList.remove('pin-modal-positioned');
                $pinModalEl.classList.remove('pin-modal-small');
                if (pinDialog) {
                    pinDialog.style.removeProperty('--pin-left');
                    pinDialog.style.removeProperty('--pin-top');
                    pinDialog.style.removeProperty('--pin-left-width');
                    pinDialog.style.removeProperty('--pin-left-height');
                    pinDialog.style.removeProperty('--pin-parent-width');
                    pinDialog.style.removeProperty('--pin-parent-height');
                    pinDialog.style.removeProperty('--pin-center-x');
                    pinDialog.style.removeProperty('--pin-center-y');
                }
            }

            if (overlayResizeHandler) {
                window.removeEventListener('resize', overlayResizeHandler);
                window.removeEventListener('orientationchange', overlayResizeHandler);
            }
            overlayResizeHandler = function () {
                applyPinOverlay($pinModalEl);
            };
            window.addEventListener('resize', overlayResizeHandler, { passive: true });
            window.addEventListener('orientationchange', overlayResizeHandler, { passive: true });
        }

        function clearPinOverlay($pinModalEl) {
            const parentModalEl = document.getElementById('receivedDocsModal');
            const pinDialog = $pinModalEl ? $pinModalEl.querySelector('.modal-dialog') : null;

            document.body.classList.remove('pin-overlay-active');
            if (parentModalEl) {
                parentModalEl.removeAttribute('aria-hidden');
                parentModalEl.removeAttribute('inert');
            }
            if ($pinModalEl) {
                $pinModalEl.classList.remove('pin-modal-positioned');
                $pinModalEl.classList.remove('pin-modal-small');
            }
            if (pinDialog) {
                pinDialog.style.removeProperty('--pin-left');
                pinDialog.style.removeProperty('--pin-top');
                pinDialog.style.removeProperty('--pin-left-width');
                pinDialog.style.removeProperty('--pin-left-height');
                pinDialog.style.removeProperty('--pin-parent-width');
                pinDialog.style.removeProperty('--pin-parent-height');
                pinDialog.style.removeProperty('--pin-center-x');
                pinDialog.style.removeProperty('--pin-center-y');
            }

            if (overlayResizeHandler) {
                window.removeEventListener('resize', overlayResizeHandler);
                window.removeEventListener('orientationchange', overlayResizeHandler);
                overlayResizeHandler = null;
            }
        }

        function fetchPinStatusAndOpen(batchId) {
            activeBatchId = batchId;
            fetchBatchDocuments(batchId);
            $.ajax({
                url: pinStatusUrl,
                method: 'POST',
                dataType: 'json',
                data: { batch_id: batchId, _token: "{{ csrf_token() }}" },
                headers: { 'Accept': 'application/json' }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    toastr.error('Unable to load PIN status.');
                    return;
                }

                if (!resp.has_pin) {
                    openCreatePinModal();
                    return;
                }

                openEnterPinModal(resp.locked_seconds || 0);
            }).fail(function (xhr) {
                toastr.error(extractErrorMessage(xhr));
            });
        }

        $(document).on('click', '.js-receive-batch-btn', function () {
            const $btn = $(this);
            const batchId = parseInt($btn.data('batch-id'), 10);
            if (!batchId) return;
            activeDocDetails = null;
            fetchPinStatusAndOpen(batchId);
        });

        attachPinMaskHandlers($('#create_pin'));
        attachPinMaskHandlers($('#create_pin_confirm'));
        attachPinMaskHandlers($('#enter_pin'));
        attachPinMaskHandlers($('#behalf_create_pin'));
        attachPinMaskHandlers($('#behalf_create_pin_confirm'));

        function enforceCreatePinConfirmMatch() {
            const pin = digitsOnly(getRawPin($('#create_pin')));
            const confirm = digitsOnly(getRawPin($('#create_pin_confirm')));
            if (pin.length !== 4 || confirm.length !== 4) {
                return;
            }
            if (pin === confirm) {
                setCreatePinError('');
                return;
            }
            setCreatePinError('PIN does not match. Please re-enter Confirm PIN.');
            setRawPin($('#create_pin_confirm'), '');
            window.setTimeout(function () {
                const el = document.getElementById('create_pin_confirm');
                if (el) el.focus();
            }, 0);
        }

        $('#create_pin_confirm').on('keyup input paste', function () {
            enforceCreatePinConfirmMatch();
        });

        $('#create_pin').on('keyup input paste', function () {
            if (digitsOnly(getRawPin($('#create_pin_confirm'))).length === 4) {
                enforceCreatePinConfirmMatch();
            }
        });

        function enforceBehalfPinConfirmMatch() {
            const pin = digitsOnly(getRawPin($('#behalf_create_pin')));
            const confirm = digitsOnly(getRawPin($('#behalf_create_pin_confirm')));
            if (pin.length !== 4 || confirm.length !== 4) {
                return;
            }
            if (pin === confirm) {
                setBehalfRegisterError('');
                return;
            }
            setBehalfRegisterError('PIN does not match. Please re-enter Confirm PIN.');
            setRawPin($('#behalf_create_pin_confirm'), '');
            window.setTimeout(function () {
                const el = document.getElementById('behalf_create_pin_confirm');
                if (el) el.focus();
            }, 0);
        }

        $('#behalf_create_pin_confirm').on('keyup input paste', function () {
            enforceBehalfPinConfirmMatch();
        });

        $('#behalf_create_pin').on('keyup input paste', function () {
            if (digitsOnly(getRawPin($('#behalf_create_pin_confirm'))).length === 4) {
                enforceBehalfPinConfirmMatch();
            }
        });

        $(document).on('click', '.pin-dot-field', function () {
            const input = this.querySelector('input');
            if (input && !input.disabled) {
                input.focus();
            }
        });

        $('#createPinModal').on('show.bs.modal', function () {
            applyPinOverlay(this);
            window.setTimeout(function () {
                $('.modal-backdrop').last().addClass('pin-modal-backdrop');
            }, 0);
        });

        $('#createPinModal').on('shown.bs.modal', function () {
            window.setTimeout(function () {
                const el = document.getElementById('create_pin');
                if (el) el.focus();
            }, 50);
        });

        $('#enterPinModal').on('show.bs.modal', function () {
            applyPinOverlay(this);
            window.setTimeout(function () {
                $('.modal-backdrop').last().addClass('pin-modal-backdrop');
            }, 0);
        });

        $('#enterPinModal').on('shown.bs.modal', function () {
            window.setTimeout(function () {
                const el = document.getElementById('enter_pin');
                if (el) el.focus();
            }, 50);
        });

        $('#createPinModal').on('hidden.bs.modal', function () {
            setCreatePinError('');
            $('#btn_create_pin_save').prop('disabled', false);
            clearPinOverlay(this);
            $('#pin_doc_details_create').addClass('d-none');
        });

        $('#enterPinModal').on('hidden.bs.modal', function () {
            setEnterPinError('');
            setEnterPinLocked(0);
            resetReceivedInBehalfUI();
            clearPinOverlay(this);
            $('#pin_doc_details_enter').addClass('d-none');
            activeDocDetails = null;
        });

        $('#received_in_behalf_toggle').on('change', function () {
            setReceivedInBehalfError('');
            const checked = $(this).is(':checked');
            const $wrap = $('#received_in_behalf_select_wrap');
            const $select = $('#received_in_behalf_user_id');
            console.log('[PIN] received_in_behalf_toggle:', checked);
            if (checked) {
                $wrap.removeClass('d-none');
                initReceivedInBehalfSelect();
                updatePinSourceIndicator();
                syncBehalfPinUI();
                window.setTimeout(function () {
                    if (receivedInBehalfSelectReady) {
                        $select.trigger('focus');
                    }
                }, 0);
            } else {
                $wrap.addClass('d-none');
                if ($select.length) {
                    $select.val(null).trigger('change');
                }
                syncBehalfPinUI();
                updatePinSourceIndicator();
            }
        });

        $('#received_in_behalf_user_id').on('change', function () {
            setReceivedInBehalfError('');
            updatePinSourceIndicator();
            syncBehalfPinUI();
            const selectedId = parseInt(String($(this).val() || ''), 10) || 0;
            console.log('[PIN] received_in_behalf_user_id:', selectedId);
        });

        $('#reset_pin_link').on('click', function (e) {
            e.preventDefault();
            setReceivedInBehalfError('');
            setEnterPinError('');
            setBehalfRegisterError('');

            if (!activeBatchId) {
                toastr.error('Missing batch.');
                return;
            }

            const receivedInBehalf = $('#received_in_behalf_toggle').is(':checked');
            const receivedInBehalfUserId = parseInt(String($('#received_in_behalf_user_id').val() || ''), 10) || 0;
            if (receivedInBehalf && receivedInBehalfUserId <= 0) {
                setReceivedInBehalfError('Please select staff to reset.');
                $('#received_in_behalf_select_wrap').removeClass('d-none');
                initReceivedInBehalfSelect();
                return;
            }

            const $link = $('#reset_pin_link');
            const oldText = $link.text();
            $link.addClass('disabled').attr('aria-disabled', 'true').text('Resetting…');
            console.log('[PIN] reset_pin', receivedInBehalf ? 'selected_staff' : 'recipient', {
                batchId: activeBatchId,
                receivedInBehalfUserId: receivedInBehalfUserId
            });

            $.ajax({
                url: pinResetUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    batch_id: activeBatchId,
                    user_id: targetUserId || null,
                    received_in_behalf: receivedInBehalf ? 1 : 0,
                    received_in_behalf_user_id: receivedInBehalf ? receivedInBehalfUserId : null,
                    _token: "{{ csrf_token() }}"
                },
                headers: { 'Accept': 'application/json' }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    toastr.error('Unable to reset PIN.');
                    return;
                }
                toastr.success('PIN reset successfully.');
                setRawPin($('#enter_pin'), '');
                $('#btn_enter_pin_receive').prop('disabled', true);

                if (receivedInBehalf) {
                    behalfSelectedHasPin = false;
                    setBehalfRegisterVisible(true);
                    updatePinSourceIndicator();
                    window.setTimeout(function () {
                        const el = document.getElementById('behalf_create_pin');
                        if (el) el.focus();
                    }, 0);
                } else {
                    if (enterPinModal) {
                        enterPinModal.hide();
                    }
                    openCreatePinModal();
                }
            }).fail(function (xhr) {
                if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const first = Object.values(xhr.responseJSON.errors)[0];
                    toastr.error(Array.isArray(first) ? String(first[0] || '') : 'Validation failed.');
                    return;
                }
                toastr.error(extractErrorMessage(xhr));
            }).always(function () {
                $link.removeClass('disabled').removeAttr('aria-disabled').text(oldText);
            });
        });

        $('#btn_behalf_pin_register').on('click', function () {
            setBehalfRegisterError('');
            setReceivedInBehalfError('');

            const receivedInBehalf = $('#received_in_behalf_toggle').is(':checked');
            const receivedInBehalfUserId = parseInt(String($('#received_in_behalf_user_id').val() || ''), 10) || 0;
            if (!receivedInBehalf || receivedInBehalfUserId <= 0) {
                setReceivedInBehalfError('Please select staff to receive.');
                return;
            }

            const pin = digitsOnly(getRawPin($('#behalf_create_pin')));
            const pinConfirm = digitsOnly(getRawPin($('#behalf_create_pin_confirm')));
            if (pin.length !== 4 || pinConfirm.length !== 4) {
                setBehalfRegisterError('PIN and Confirm PIN must be exactly 4 digits.');
                return;
            }
            if (pin !== pinConfirm) {
                setBehalfRegisterError('PIN does not match. Please re-enter Confirm PIN.');
                setRawPin($('#behalf_create_pin_confirm'), '');
                return;
            }

            const $btn = $('#btn_behalf_pin_register');
            $btn.prop('disabled', true).text('Registering…');
            console.log('[PIN] register_pin_for_user', receivedInBehalfUserId);

            $.ajax({
                url: inboxPinCreateUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    user_id: receivedInBehalfUserId,
                    pin: pin,
                    pin_confirm: pinConfirm,
                    _token: "{{ csrf_token() }}"
                },
                headers: { 'Accept': 'application/json' }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    setBehalfRegisterError('Unable to register PIN.');
                    return;
                }
                toastr.success('PIN registered successfully.');
                behalfSelectedHasPin = true;
                refreshReceivedInBehalfSelectedPinState(receivedInBehalfUserId);
                setBehalfRegisterVisible(false);
                updatePinSourceIndicator();
                setRawPin($('#enter_pin'), '');
                $('#btn_enter_pin_receive').prop('disabled', true);
                window.setTimeout(function () {
                    const el = document.getElementById('enter_pin');
                    if (el) el.focus();
                }, 0);
            }).fail(function (xhr) {
                if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const first = Object.values(xhr.responseJSON.errors)[0];
                    setBehalfRegisterError(Array.isArray(first) ? String(first[0] || '') : 'Validation failed.');
                    return;
                }
                setBehalfRegisterError(extractErrorMessage(xhr));
            }).always(function () {
                $btn.prop('disabled', false).text('Register PIN');
            });
        });

        $('#btn_create_pin_save').on('click', function () {
            const pin = digitsOnly(getRawPin($('#create_pin')));
            const pinConfirm = digitsOnly(getRawPin($('#create_pin_confirm')));
            setCreatePinError('');

            if (!activeBatchId) {
                setCreatePinError('Missing batch.');
                return;
            }
            if (pin.length !== 4 || pinConfirm.length !== 4) {
                setCreatePinError('PIN and Confirm PIN must be exactly 4 digits.');
                return;
            }
            if (pin !== pinConfirm) {
                setCreatePinError('PIN does not match. Please re-enter Confirm PIN.');
                setRawPin($('#create_pin_confirm'), '');
                window.setTimeout(function () {
                    const el = document.getElementById('create_pin_confirm');
                    if (el) el.focus();
                }, 0);
                return;
            }

            $('#btn_create_pin_save').prop('disabled', true);
            $.ajax({
                url: pinCreateUrl,
                method: 'POST',
                dataType: 'json',
                data: { batch_id: activeBatchId, pin: pin, pin_confirm: pinConfirm, _token: "{{ csrf_token() }}" },
                headers: { 'Accept': 'application/json' }
            }).done(function (resp) {
                $('#btn_create_pin_save').prop('disabled', false);
                if (!resp || !resp.success) {
                    setCreatePinError('Unable to save PIN.');
                    return;
                }

                if (createPinModal) {
                    createPinModal.hide();
                }
                openEnterPinModal(0);
            }).fail(function (xhr) {
                $('#btn_create_pin_save').prop('disabled', false);
                if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const first = Object.values(xhr.responseJSON.errors)[0];
                    setCreatePinError(Array.isArray(first) ? String(first[0] || '') : 'Validation failed.');
                    return;
                }
                setCreatePinError(extractErrorMessage(xhr));
            });
        });

        $('#btn_enter_pin_receive').on('click', function () {
            const $pinInput = $('#enter_pin');
            const pin = digitsOnly(getRawPin($pinInput));
            setEnterPinError('');
            setReceivedInBehalfError('');

            if (!activeBatchId) {
                setEnterPinError('Missing batch.');
                setRawPin($pinInput, '');
                $('#btn_enter_pin_receive').prop('disabled', true);
                return;
            }
            if (pin.length !== 4) {
                setEnterPinError('PIN must be exactly 4 digits.');
                setRawPin($pinInput, '');
                $('#btn_enter_pin_receive').prop('disabled', true);
                return;
            }
            if ($pinInput.prop('disabled')) {
                return;
            }

            const receivedInBehalf = $('#received_in_behalf_toggle').is(':checked');
            const receivedInBehalfUserId = parseInt(String($('#received_in_behalf_user_id').val() || ''), 10) || 0;
            console.log('[PIN] matching_source:', receivedInBehalf ? 'selected_staff' : 'recipient', {
                receivedInBehalf: receivedInBehalf,
                receivedInBehalfUserId: receivedInBehalfUserId,
                batchId: activeBatchId
            });
            if (receivedInBehalf && receivedInBehalfUserId <= 0) {
                setReceivedInBehalfError('Please select staff to receive.');
                $('#received_in_behalf_select_wrap').removeClass('d-none');
                initReceivedInBehalfSelect();
                window.setTimeout(function () {
                    const $sel = $('#received_in_behalf_user_id');
                    if (receivedInBehalfSelectReady) {
                        $sel.trigger('focus');
                    }
                }, 0);
                return;
            }

            if (receivedInBehalf) {
                const hasPin = readSelectedBehalfHasPin();
                if (hasPin === false) {
                    setBehalfRegisterError('Selected staff has no PIN set. Please register a PIN first.');
                    setBehalfRegisterVisible(true);
                    setRawPin($pinInput, '');
                    $('#btn_enter_pin_receive').prop('disabled', true);
                    window.setTimeout(function () {
                        const el = document.getElementById('behalf_create_pin');
                        if (el) el.focus();
                    }, 0);
                    return;
                }
            }

            $('#btn_enter_pin_receive').prop('disabled', true);
            const url = pinReceiveUrlTemplate.replace('__BATCH__', String(activeBatchId));
            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: {
                    batch_id: activeBatchId,
                    pin: pin,
                    received_in_behalf: receivedInBehalf ? 1 : 0,
                    received_in_behalf_user_id: receivedInBehalf ? receivedInBehalfUserId : null,
                    _token: "{{ csrf_token() }}"
                },
                headers: { 'Accept': 'application/json' }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    setEnterPinError('Unable to receive batch.');
                    setRawPin($pinInput, '');
                    $('#btn_enter_pin_receive').prop('disabled', false);
                    return;
                }
                $('#enter_pin_sr').text('Batch received successfully.');
                const msg = (resp && typeof resp.message === 'string' && resp.message.trim() !== '') ? resp.message : 'Batch received successfully.';
                toastr.success(msg);
                if (enterPinModal) {
                    enterPinModal.hide();
                }
                loadReceivedDocs(receivedDocsBaseUrl);
            }).fail(function (xhr) {
                setRawPin($pinInput, '');
                if (xhr && xhr.status === 423 && xhr.responseJSON) {
                    const seconds = parseInt(xhr.responseJSON.locked_seconds || 30, 10);
                    setEnterPinError('');
                    setEnterPinLocked(isNaN(seconds) ? 30 : seconds);
                    return;
                }
                if (xhr && xhr.status === 422 && xhr.responseJSON) {
                    if (xhr.responseJSON.errors && xhr.responseJSON.errors.received_in_behalf_user_id) {
                        const first = xhr.responseJSON.errors.received_in_behalf_user_id;
                        setReceivedInBehalfError(Array.isArray(first) ? String(first[0] || '') : 'Validation failed.');
                        return;
                    }
                    const msg = typeof xhr.responseJSON.message === 'string' && xhr.responseJSON.message.trim() !== ''
                        ? xhr.responseJSON.message
                        : 'Incorrect PIN.';
                    setEnterPinError(msg);
                } else {
                    setEnterPinError(extractErrorMessage(xhr));
                }
                $('#btn_enter_pin_receive').prop('disabled', digitsOnly(getRawPin($('#enter_pin'))).length !== 4);
            });
        });

        $('#btn_received_docs').on('click', function () {
            if (receivedDocsModal) {
                receivedDocsModal.show();
            }
            loadReceivedDocs(receivedDocsBaseUrl);
        });

        $(document).on('submit', '#received_docs_filter_form', function (e) {
            e.preventDefault();
            const $form = $(this);
            const url = $form.attr('action') + '?' + $form.serialize();
            loadReceivedDocs(url);
        });

        $(document).on('click', '#received_docs_modal_body a', function (e) {
            const href = $(this).attr('href');
            if (!href || href === '#') return;
            if (href.indexOf(receivedDocsBaseUrl) !== 0) return;
            e.preventDefault();
            loadReceivedDocs(href);
        });

        $('#btn_create_batch').on('click', function () {
            const rows = selectedRows();
            if (!rows.length) {
                toastr.error('Please select at least one document.');
                return;
            }
            renderModalList(rows);
            $('#btn_modal_create').prop('disabled', false);
            if (createBatchModal) {
                createBatchModal.show();
            }
        });

        $('#btn_modal_create').on('click', function () {
            const rows = selectedRows();
            if (!rows.length) {
                toastr.error('Please select at least one document.');
                return;
            }

            const ids = rows.map(r => r.id);
            $('#btn_modal_create').prop('disabled', true);

            $.ajax({
                url: "{{ route('inbox.batch.create') }}",
                method: 'POST',
                dataType: 'json',
                data: { user_id: targetUserId, recipient_ids: ids, _token: "{{ csrf_token() }}" },
                headers: { 'Accept': 'application/json' }
            }).done(function (resp) {
                toastr.success('Batch created successfully.');
                if (createBatchModal) {
                    createBatchModal.hide();
                }
                window.location.reload();
            }).fail(function (xhr) {
                $('#btn_modal_create').prop('disabled', false);
                toastr.error(extractErrorMessage(xhr));
            });
        });

        updateSelectedUI();
    });
</script>
@endpush
@endsection
