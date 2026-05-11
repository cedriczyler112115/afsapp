@extends('layouts.app')

@section('title', 'Incoming Document - Tracking')

@section('content')
<div id="page_alerts" class="position-fixed top-0 end-0 p-3" style="z-index: 1100;"></div>
@php
    $userId = (int) auth()->id();
    $isOwner = (int) $incomingDocument->received_by === $userId
        || (int) $incomingDocument->forwarded_to_user_id === $userId
        || (\Illuminate\Support\Facades\Schema::hasColumn('incoming_documents', 'created_by') && (int) $incomingDocument->created_by === $userId);
    $source = (string) request('from', session('incoming_documents.show_from', 'incoming'));
    $fromInbox = $source === 'inbox';
    $backUrl = $fromInbox ? route('inbox.index') : route('incoming-documents.index');
    $attachmentUrl = $incomingDocument->attachment_path ? asset('storage/'.$incomingDocument->attachment_path) : null;
    $attachmentExt = $incomingDocument->attachment_path ? strtolower((string) pathinfo((string) $incomingDocument->attachment_path, PATHINFO_EXTENSION)) : '';
@endphp
<div class="row g-3">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Document Details</span>
                <div class="d-flex align-items-center gap-2">
                    @if($isOwner)
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#forwardModal">
                            <i class="bi bi-forward-fill"></i> Forward
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="mb-2"><span class="text-secondary small fw-bold">Reference</span><div>{{ $incomingDocument->document_reference_number }}</div></div>
                <div class="mb-2"><span class="text-secondary small fw-bold">Date Received</span><div>{{ $incomingDocument->date_received ? $incomingDocument->date_received->format('F j, Y g:i A') : '' }}</div></div>
                <div class="mb-2">
                    <span class="text-secondary small fw-bold">From</span>
                    <div class="text-uppercase small text-secondary fw-bold">{{ $incomingDocument->document_from_type }}</div>
                    <div>{{ optional($incomingDocument->source)->name }}</div>
                </div>
                <div class="mb-2"><span class="text-secondary small fw-bold">Type</span><div>{{ optional($incomingDocument->type)->name }}</div></div>
                <div class="mb-2"><span class="text-secondary small fw-bold">DRN</span><div>{{ $incomingDocument->drn }}</div></div>
                <div class="mb-2"><span class="text-secondary small fw-bold">Subject</span><div>{{ $incomingDocument->subject }}</div></div>
                <div class="mb-2"><span class="text-secondary small fw-bold">Description</span><div class="small">{{ $incomingDocument->description }}</div></div>
                <div class="mb-2"><span class="text-secondary small fw-bold">Status</span><div class="fw-semibold"><span class="badge bg-success">{{ $incomingDocument->current_status }}</span></div></div>
                <div class="mb-2"><span class="text-secondary small fw-bold">Signed By</span><div>{{ $incomingDocument->signed_by }}</div></div>
                <div class="mb-2"><span class="text-secondary small fw-bold">Date Signed</span><div>{{ $incomingDocument->date_signed ? $incomingDocument->date_signed->format('Y-m-d') : '' }}</div></div>
                <div class="mb-2"><span class="text-secondary small fw-bold">Priority</span><div class="fw-semibold">{{ $incomingDocument->priority_level }}</div></div>
                <div class="mb-2"><span class="text-secondary small fw-bold">Deadline</span><div>{{ $incomingDocument->deadline_date ? $incomingDocument->deadline_date->format('Y-m-d') : '' }}</div></div>
                <div class="mb-2">
                    <span class="text-secondary small fw-bold">Days Left to Deadline</span>
                    <div id="deadline_days_left" data-deadline="{{ $incomingDocument->deadline_date ? $incomingDocument->deadline_date->format('Y-m-d') : '' }}"></div>
                </div>
                <div class="mb-2"><span class="text-secondary small fw-bold">Attachment</span>
                    <div>
                        @if($incomingDocument->attachment_path)
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#attachmentModal">
                                <i class="bi bi-paperclip"></i> View attachment
                            </button>
                        @else
                            <span class="text-secondary small">None</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    @if($isOwner)
                        <a href="{{ route('incoming-documents.edit', $incomingDocument) }}" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-pencil"></i> Edit</a>
                    @endif
                </div>
            </div>
        </div>

        @if($isOwner)
            <div class="modal fade" id="forwardModal" tabindex="-1" aria-labelledby="forwardModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" >
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="forwardModalLabel">Forward Document</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3">
                            <form action="{{ route('incoming-documents.forward', $incomingDocument) }}" method="POST" id="forwardForm">
                                @csrf
                                @if($errors->any())
                                    <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
                                @endif
                                @if(session('warning'))
                                    <div class="alert alert-warning mb-3">{{ session('warning') }}</div>
                                @endif
                                @if(session('success'))
                                    <div class="alert alert-success mb-3">{{ session('success') }}</div>
                                @endif
                                <div id="forward_ajax_alert"></div>

                                <div class="mb-3">
                                    <div class="small text-secondary mb-2 w-50" style="width: 100px !important;">Forward target</div>
                                    <x-oval-radio-group
                                        name="forward_to"
                                        :options="[
                                            ['value' => 'user', 'label' => 'User'],
                                            ['value' => 'group', 'label' => 'Group'],
                                        ]"
                                        :value="(string) old('forward_to', 'user')"
                                        aria-label="Forward target"
                                        size="sm"
                                        :required="true"
                                        id-prefix="forward_to"
                                    />
                                </div>
                                
                                <div class="mb-3" id="forward_user_wrap">
                                    <label class="form-label small mb-1">Forward to</label>
                                    <select name="forwarded_to_user_id" id="forwarded_to_user_id" class="form-select form-select-sm">
                                        <option value="">Select user</option>
                                        @foreach($users as $u)
                                            @continue((int) $u->id === (int) auth()->id())
                                            <option value="{{ $u->id }}" {{ (string) old('forwarded_to_user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3 d-none" id="forward_group_wrap">
                                    <label class="form-label small mb-1" for="forwarded_to_group_id">Group</label>
                                    <select name="forwarded_to_group_id" id="forwarded_to_group_id" class="form-select form-select-sm" data-initial="{{ old('forwarded_to_group_id') }}">
                                        <option value="">— Select Group —</option>
                                    </select>

                                    <div class="border rounded-2 p-2 mt-3">
                                        <div class="small text-secondary mb-2">Group routing</div>
                                        <x-oval-radio-group
                                            name="group_target_mode"
                                            :options="[
                                                ['value' => 'group', 'label' => 'Forward to Group'],
                                                ['value' => 'staff', 'label' => 'Select Multiple Staff'],
                                            ]"
                                            :value="(string) ((string) old('forward_staff_mode', '0') === '1' ? 'staff' : 'group')"
                                            aria-label="Group routing"
                                            size="sm"
                                            :required="true"
                                            id-prefix="group_target_mode"
                                        />
                                    </div>

                                    <input type="hidden" name="forward_staff_mode" id="forward_staff_mode" value="{{ old('forward_staff_mode', '0') }}">

                                    <div id="staff_picker" class="mt-3 d-none">
                                        <div class="border rounded-2 p-2">
                                            <div class="row g-6">
                                                <div class="col-6 col-lg-6">
                                                    <label class="form-label small mb-1" for="staff_search">Search staff</label>
                                                    <input type="text" id="staff_search" class="form-control form-control-sm" autocomplete="off" inputmode="search">

                                                    <div class="small text-secondary mt-1" id="staff_hint">Type at least 2 characters to search.</div>
                                                    <div class="alert alert-danger mt-2 d-none" id="staff_error" role="alert"></div>

                                                    <div class="mt-2">
                                                        <div class="list-group border rounded-2 overflow-auto" id="staff_results" role="listbox" aria-label="Staff results" style="max-height: 320px;"></div>
                                                        <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-2 d-none" id="staff_load_more">Load More</button>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-lg-5">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                        <div class="small fw-semibold" aria-live="polite">Selected: <span id="staff_selected_count">0</span> / <span id="staff_selected_limit">20</span></div><br>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="staff_clear_all">Clear All</button>
                                                    </div>
                                                    <div class="small text-secondary" style="margin-top: 33px !important">Selected staff will not appear in search results.</div>
                                                    <ul class="list-group border rounded-2 overflow-auto mt-2" id="staff_selected_list" aria-label="Selected staff" style="max-height: 320px;"></ul>
                                                </div>
                                            </div>
                                            <div id="staff_selected_inputs"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small mb-1">Remarks</label>
                                    <textarea name="forward_remarks" rows="3" class="form-control form-control-sm">{{ old('forward_remarks') }}</textarea>
                                </div>

                                <div class="d-flex gap-2 float-end" style="margin-bottom: 10px !important">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-send"></i> Forward</button>
                                    <br><br>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addGroupModal" tabindex="-1" aria-labelledby="addGroupModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addGroupModalLabel">Create Group</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addGroupForm" autocomplete="off" novalidate>
                                <div class="mb-3">
                                    <label class="form-label" for="add_group_name">Group Name</label>
                                    <input type="text" class="form-control" id="add_group_name" name="group_name" maxlength="50" required>
                                    <div class="invalid-feedback" data-add-group-feedback-for="group_name"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="add_group_status">Status</label>
                                    <select class="form-select" id="add_group_status" name="status" required>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                    <div class="invalid-feedback" data-add-group-feedback-for="status"></div>
                                </div>
                            </form>
                            <div class="alert alert-danger d-none mb-0" role="alert" id="addGroupGenericError"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="addGroupCancelBtn">Close</button>
                            <button type="button" class="btn btn-primary" id="addGroupSaveBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="addGroupSpinner"></span>
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Tracking History</span>
                <a href="{{ $backUrl }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
                <style>
                    .dt-timeline {
                        --dt-rail-x: 18px;
                        --dt-indicator-size: 28px;
                        --dt-gap: 12px;
                        position: relative;
                        margin: 0;
                        padding: 0;
                        list-style: none;
                    }
                    .dt-step {
                        position: relative;
                        display: grid;
                        grid-template-columns: var(--dt-rail-x) 1fr;
                        column-gap: var(--dt-gap);
                        padding: 10px 0;
                        animation: dtIn 260ms ease both;
                        animation-delay: var(--dt-delay, 0ms);
                    }
                    .dt-step::before {
                        content: "";
                        position: absolute;
                        left: calc(var(--dt-rail-x) / 2 - 1px);
                        top: 0;
                        bottom: 0;
                        width: 2px;
                        background: rgba(0, 0, 0, .08);
                    }
                    .dt-step:last-child::before { bottom: 50%; }
                    .dt-rail {
                        position: relative;
                        display: flex;
                        align-items: flex-start;
                        justify-content: center;
                        padding-top: 6px;
                    }
                    .dt-indicator {
                        width: var(--dt-indicator-size);
                        height: var(--dt-indicator-size);
                        border-radius: 999px;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 12px;
                        font-weight: 700;
                        letter-spacing: .2px;
                        border: 2px solid rgba(0, 0, 0, .08);
                        background: #fff;
                        color: rgba(0, 0, 0, .65);
                        box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
                        transition: transform 160ms ease, background-color 160ms ease, color 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
                    }
                    .dt-card {
                        border: 1px solid rgba(0, 0, 0, .08);
                        border-radius: .75rem;
                        padding: 12px 14px;
                        background: #fff;
                        transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
                    }
                    .dt-meta {
                        display: flex;
                        justify-content: space-between;
                        gap: 8px;
                        flex-wrap: wrap;
                    }
                    .dt-title {
                        display: flex;
                        align-items: baseline;
                        gap: 8px;
                        flex-wrap: wrap;
                    }
                    .dt-badge {
                        font-size: 11px;
                        font-weight: 700;
                        padding: 4px 8px;
                        border-radius: 999px;
                        letter-spacing: .25px;
                        border: 1px solid rgba(0, 0, 0, .08);
                        background: rgba(13, 110, 253, .08);
                        color: rgba(13, 110, 253, .95);
                    }
                    .dt-past .dt-indicator {
                        background: linear-gradient(135deg, rgba(108, 117, 125, .10), rgba(108, 117, 125, .02));
                        color: rgba(108, 117, 125, .95);
                    }
                    .dt-past .dt-card { opacity: .92; }
                    .dt-current .dt-indicator {
                        background: linear-gradient(135deg, rgba(13, 110, 253, 1), rgba(32, 201, 151, .95));
                        color: #fff;
                        border-color: rgba(108, 117, 128, .95);
                        transform: scale(1.12);
                    }
                    .dt-current .dt-card {
                        /* border-color: rgba(13, 110, 253, .40); */
                        /* box-shadow: 0 14px 34px rgba(13, 110, 253, .16); */
                        /* transform: scale(1.02); */
                    }
                    .dt-current .dt-indicator::after {
                        content: "";
                        position: absolute;
                        inset: -8px;
                        border-radius: 999px;
                        background: radial-gradient(circle, rgba(13, 110, 253, .22) 0%, rgba(13, 110, 253, 0) 70%);
                        animation: dtPulse 1.6s ease-in-out infinite;
                        pointer-events: none;
                    }
                    .dt-future { opacity: .75; }
                    .dt-future .dt-indicator {
                        background: linear-gradient(135deg, rgba(13, 110, 253, .06), rgba(0, 0, 0, 0));
                        color: rgba(13, 110, 253, .75);
                        border-style: dashed;
                    }
                    .dt-step:focus-within .dt-card {
                        outline: 0;
                        box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .20);
                        border-color: rgba(13, 110, 253, .35);
                    }
                    @keyframes dtPulse {
                        0% { transform: scale(.92); opacity: .65; }
                        50% { transform: scale(1.08); opacity: .22; }
                        100% { transform: scale(.92); opacity: .65; }
                    }
                    @keyframes dtIn {
                        from { opacity: 0; transform: translateY(6px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    @media (max-width: 576px) {
                        .dt-timeline { --dt-rail-x: 16px; --dt-indicator-size: 26px; --dt-gap: 10px; }
                        .dt-card { padding: 10px 12px; }
                        .dt-badge { font-size: 10px; }
                    }

                    .badge-pink {
                        background-color: #f8b4d9;
                        color: #6b0033;
                    }

                    .remark-card {
                        border: 1px solid var(--rc-border, #dee2e6);
                        background: var(--rc-bg, #f8f9fa);
                        color: var(--rc-fg, #212529);
                        border-radius: .75rem;
                        padding: 12px 14px;
                        /* box-shadow: 0 6px 16px rgba(0,0,0,.06); */
                        transition: box-shadow 160ms ease, transform 160ms ease;
                    }
                    /* .remark-card:hover {
                        box-shadow: 0 10px 26px rgba(0,0,0,.12);
                        transform: translateY(-2px);
                    } */
                    .remark-critical { --rc-bg: #fdecec; --rc-border: #f5c2c7; --rc-fg: #7a1d24; }
                    .remark-warning { --rc-bg: #fff8e6; --rc-border: #ffe69c; --rc-fg: #7a5a00; }
                    .remark-positive { --rc-bg: #e9f7ef; --rc-border: #bfe3c7; --rc-fg: #14532d; }
                    .remark-info { --rc-bg: #f8f9fa; --rc-border: #dee2e6; --rc-fg: #212529; }
                    .remark-head {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 10px;
                        flex-wrap: wrap;
                    }
                    .remark-title {
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                        font-weight: 700;
                    }
                    .remark-icon { font-size: 1rem; }
                    .remark-badges { display: inline-flex; gap: 6px; flex-wrap: wrap; }
                    .remark-body { margin-top: 8px; line-height: 1.4; }
                    .remark-chip {
                        display: inline-block;
                        padding: 2px 8px;
                        border-radius: 999px;
                        font-size: .75rem;
                        font-weight: 700;
                        border: 1px solid currentColor;
                    }
                    .chip-critical { color: #c1121f; background: #fff; }
                    .chip-warning { color: #b45309; background: #fff; }
                    .chip-positive { color: #15803d; background: #fff; }
                    .chip-info { color: #0d6efd; background: #fff; }

                    #editTrackingLogModal { z-index: 2005; }
                    .edit-log-backdrop.show { z-index: 2000; }
                </style>
                <h6 class="text-secondary text-uppercase small mb-2">trail starts here</h6>
                <div id="tracking_history">
                    @php
                        $statusFlow = $statuses ?? [];
                        $statusIndex = array_search($incomingDocument->current_status, $statusFlow, true);
                        $futureStatuses = $statusIndex === false ? [] : array_slice($statusFlow, $statusIndex + 1);
                        $stepNumber = 0;
                        $editableLogs = [];
                    @endphp

                    <ol class="dt-timeline" aria-label="Tracking timeline">
                        @forelse($logs as $log)
                            @php
                                $stepNumber++;
                                $isActive = $loop->last;
                                $stateClass = $isActive ? 'dt-current' : 'dt-past';
                                $canEditLog = ((bool) ($isAdminUser ?? false)) || ((int) ($log->user_id ?? 0) === $userId);
                                $editMode = null;
                                $editRemarks = (string) ($log->remarks ?? '');
                                $editUpdateText = '';
                                $decodedForEdit = null;
                                $jsonOkForEdit = false;
                                if (is_string($log->remarks) && trim($log->remarks) !== '') {
                                    $decodedForEdit = json_decode($log->remarks, true);
                                    $jsonOkForEdit = json_last_error() === JSON_ERROR_NONE;
                                }

                                if ((string) ($log->action_type ?? '') === 'UPDATED' && $jsonOkForEdit && is_array($decodedForEdit) && ($decodedForEdit['kind'] ?? null) === 'manual_update_v1') {
                                    $editMode = 'manual_update';
                                    $editUpdateText = (string) ($decodedForEdit['update_text'] ?? '');
                                } elseif (! $jsonOkForEdit) {
                                    $editMode = 'remarks';
                                }

                                if ($canEditLog && $editMode !== null) {
                                    $editableLogs[(int) $log->id] = [
                                        'mode' => $editMode,
                                        'remarks' => $editRemarks,
                                        'update_text' => $editUpdateText,
                                    ];
                                }
                            @endphp
                            <li class="dt-step {{ $stateClass }}" style="--dt-delay: {{ min($loop->index * 35, 250) }}ms" aria-label="Step {{ $stepNumber }}" @if($isActive) aria-current="step" @endif>
                                <div class="dt-rail" aria-hidden="true">
                                    <span class="dt-indicator">{{ $stepNumber }}</span>
                                </div>
                                <div class="dt-card">
                                    <div class="dt-meta">
                                        <div class="dt-title">
                                            <span class="dt-badge">{{ $log->action_type }}</span>
                                            @if($isActive)
                                                <span class="badge text-bg-primary">Current</span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                            <div class="text-secondary small">{{ $log->action_timestamp ? $log->action_timestamp->format('F j, Y g:i A') : '' }}</div>
                                            @if($canEditLog && $editMode !== null)
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 js-edit-tracking-log" data-log-id="{{ (int) $log->id }}" aria-label="Edit log">
                                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="small text-secondary mt-1">
                                        {{ optional($log->user)->name }}
                                        @if($log->status_from || $log->status_to)
                                            · {{ $log->status_from }} → {{ $log->status_to }}
                                        @endif
                                    </div>
                                    @if($log->relatedUser)
                                        <div class="small mt-1">To: {{ mb_strtoupper((string) $log->relatedUser->name, 'UTF-8') }}</div>
                                    @endif
                                    @if($log->relatedSource)
                                        <div class="small mt-1">To: {{ strtoupper($log->relatedSource->source_type) }} - {{ $log->relatedSource->name }}</div>
                                    @endif
                                    @if($log->remarks)
                                        @php
                                            $updateChanges = null;
                                            $manualUpdate = null;
                                            $forwardMeta = null;
                                            $inboxReceiveMeta = null;
                                            if ($log->action_type === 'UPDATED') {
                                                $decoded = json_decode($log->remarks, true);
                                                if (is_array($decoded)) {
                                                    if (($decoded['kind'] ?? null) === 'manual_update_v1') {
                                                        $manualUpdate = $decoded;
                                                    } else {
                                                        $updateChanges = $decoded;
                                                    }
                                                }
                                            }
                                            if ($log->action_type === 'FORWARDED') {
                                                $decoded = json_decode($log->remarks, true);
                                                if (is_array($decoded) && ($decoded['kind'] ?? null) === 'forward_recipients_v1') {
                                                    $forwardMeta = $decoded;
                                                }
                                            }
                                            if ($log->action_type === 'INBOX_RECEIVED') {
                                                $decoded = json_decode($log->remarks, true);
                                                if (is_array($decoded) && ($decoded['kind'] ?? null) === 'inbox_received_v1') {
                                                    $inboxReceiveMeta = $decoded;
                                                }
                                            }
                                        @endphp

                                        @if($manualUpdate !== null)
                                            @php
                                                $rf = is_array($manualUpdate['return_from'] ?? null) ? $manualUpdate['return_from'] : [];
                                                $rfName = (string) ($rf['name'] ?? '');
                                                $rfType = (string) ($rf['source_type'] ?? '');
                                                $updateText = (string) ($manualUpdate['update_text'] ?? '');
                                                $party = (string) ($manualUpdate['party'] ?? 'from');
                                                $rfLabel = $party === 'to' ? 'Forward to:' : 'Returned from:';
                                            @endphp
                                            @php
                                                $sev = 'info';
                                                $t = mb_strtolower($updateText, 'UTF-8');
                                                if (str_contains($t, 'urgent') || str_contains($t, 'immediate') || str_contains($t, 'critical') || str_contains($t, 'error') || str_contains($t, 'fail')) { $sev = 'critical'; }
                                                elseif (str_contains($t, 'warn') || str_contains($t, 'delay') || str_contains($t, 'pending')) { $sev = 'warning'; }
                                                elseif (str_contains($t, 'success') || str_contains($t, 'completed') || str_contains($t, 'done') || str_contains($t, 'ok')) { $sev = 'positive'; }
                                                $icon = $sev === 'critical' ? 'bi-exclamation-octagon-fill' : ($sev === 'warning' ? 'bi-exclamation-triangle-fill' : ($sev === 'positive' ? 'bi-check-circle-fill' : 'bi-info-circle-fill'));
                                                $chip = $sev === 'critical' ? 'chip-critical' : ($sev === 'warning' ? 'chip-warning' : ($sev === 'positive' ? 'chip-positive' : 'chip-info'));
                                                $card = $sev === 'critical' ? 'remark-critical' : ($sev === 'warning' ? 'remark-warning' : ($sev === 'positive' ? 'remark-positive' : 'remark-info'));
                                            @endphp
                                            <div class="remark-card {{ $card }} mt-2" role="region" aria-label="Manual update">
                                                <div class="remark-head">
                                                    <div class="remark-title">
                                                        <i class="remark-icon bi {{ $icon }}" aria-hidden="true"></i>
                                                        <span>Manual Update</span>
                                                    </div>
                                                    <div class="remark-badges">
                                                        <span class="remark-chip {{ $chip }}">{{ strtoupper($sev) }}</span>
                                                        @if(trim($rfName) !== '')
                                                            <span class="remark-chip chip-info">{{ strtoupper((string) $rfType) }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="remark-body">
                                                    @if(trim($rfName) !== '')
                                                        <div class="small">{{ $rfLabel }} <span class="badge text-bg-primary">{{ strtoupper((string) $rfType) }} - {{ $rfName }}</span></div>
                                                    @endif
                                                    @if(trim($updateText) !== '')
                                                        <div class="mt-2">{!! nl2br(e($updateText)) !!}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($updateChanges !== null)
                                            <div class="remark-card remark-info mt-2" role="region" aria-label="Field changes">
                                                <div class="remark-head">
                                                    <div class="remark-title">
                                                        <i class="remark-icon bi bi-arrow-left-right" aria-hidden="true"></i>
                                                        <span>Field Changes</span>
                                                    </div>
                                                    <div class="remark-badges">
                                                        <span class="remark-chip chip-info">INFO</span>
                                                    </div>
                                                </div>
                                                <div class="remark-body">
                                                    @if(count($updateChanges) === 0)
                                                        <div class="small text-secondary">No field changes detected.</div>
                                                    @else
                                                        @foreach($updateChanges as $c)
                                                            <div class="d-flex flex-wrap gap-2 align-items-start py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                                <div class="badge text-bg-secondary">{{ $c['field'] ?? '' }}</div>
                                                                <div class="small">
                                                                    <span class="text-secondary">from</span>
                                                                    <span class="font-monospace">{{ array_key_exists('old', $c ?? []) ? json_encode($c['old'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'null' }}</span>
                                                                    <span class="text-secondary mx-1">to</span>
                                                                    <span class="font-monospace">{{ array_key_exists('new', $c ?? []) ? json_encode($c['new'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'null' }}</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($forwardMeta !== null)
                                            @php
                                                $recipients = is_array($forwardMeta['recipients'] ?? null) ? $forwardMeta['recipients'] : [];
                                                $addedCount = is_array($forwardMeta['added_user_ids'] ?? null) ? count($forwardMeta['added_user_ids']) : null;
                                                $dupCount = is_array($forwardMeta['duplicate_user_ids'] ?? null) ? count($forwardMeta['duplicate_user_ids']) : null;
                                                $modeLabel = ($forwardMeta['mode'] ?? '') === 'staff' ? 'Multiple Staff' : 'Group';
                                                $groupName = is_array($forwardMeta['group'] ?? null) ? ($forwardMeta['group']['name'] ?? '') : '';
                                                $note = (string) ($forwardMeta['note'] ?? '');
                                                $recipientCount = count($recipients);
                                                $recipientCountLabel = $recipientCount.' '.strtoupper(\Illuminate\Support\Str::plural('recipient', $recipientCount));
                                            @endphp
                                            <div class="remark-card remark-info mt-2" role="region" aria-label="Forwarded">
                                                <div class="remark-head">
                                                    <div class="remark-title">
                                                        <i class="remark-icon bi bi-send-fill" aria-hidden="true"></i>
                                                        <span>Forwarded to {{ $modeLabel }}</span>
                                                    </div>
                                                    <div class="remark-badges">
                                                        <span class="remark-chip chip-info">{{ $recipientCountLabel }}</span>
                                                    </div>
                                                </div>
                                                <div class="remark-body">
                                                    @if($groupName !== '')
                                                        <div class="small">Group: {{ $groupName }}</div>
                                                    @endif
                                                    @if($addedCount !== null || $dupCount !== null)
                                                        <div class="small text-secondary">
                                                            @if($addedCount !== null) Added: {{ $addedCount }} @endif
                                                            @if($dupCount !== null) · Already forwarded: {{ $dupCount }} @endif
                                                        </div>
                                                    @endif
                                                    @if($note !== '')
                                                        <div class="small mt-1">Remarks: {{ $note }}</div>
                                                    @endif
                                                    @if(count($recipients) === 0)
                                                        <div class="small text-secondary mt-2">No recipients.</div>
                                                    @else
                                                        <details class="mt-2">
                                                            <summary class="small">View recipients</summary>
                                                            <div class="mt-2 d-flex flex-wrap gap-1">
                                                                @foreach($recipients as $r)
                                                                    <span class="badge text-bg-primary">{{ mb_strtoupper((string) ($r['name'] ?? ''), 'UTF-8') }}</span>
                                                                @endforeach
                                                            </div>
                                                        </details>
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($inboxReceiveMeta !== null)
                                            @php
                                                $recipientUserName = (string) ($inboxReceiveMeta['recipient_user_name'] ?? '');
                                                $receivedInBehalf = (string) ($inboxReceiveMeta['received_in_behalf'] ?? '');
                                                $behalfKey = ((int) ($log->incoming_document_id ?? 0)).':'.((int) ($log->related_user_id ?? 0));
                                                $receivedInBehalfNameFromRecipients = is_array($receivedInBehalfNames ?? null)
                                                    ? (string) ($receivedInBehalfNames[$behalfKey] ?? '')
                                                    : '';
                                                $displayBehalfName = trim($receivedInBehalfNameFromRecipients) !== ''
                                                    ? $receivedInBehalfNameFromRecipients
                                                    : $receivedInBehalf;
                                                $documentTypeName = (string) ($inboxReceiveMeta['document_type_name'] ?? '');
                                            @endphp
                                            <div class="remark-card remark-positive mt-2" role="region" aria-label="Inbox received">
                                                <div class="remark-head">
                                                    <div class="remark-title">
                                                        <i class="remark-icon bi bi-inbox-fill" aria-hidden="true"></i>
                                                        <span>Received in Inbox</span>
                                                    </div>
                                                    <div class="remark-badges">
                                                        <span class="remark-chip chip-positive">RECEIVED</span>
                                                    </div>
                                                </div>
                                                <div class="remark-body">
                                                    @if($recipientUserName !== '')
                                                        <div class="small text-secondary">Recipient: {{ mb_strtoupper($recipientUserName, 'UTF-8') }}</div>
                                                    @endif
                                                    @if(trim($displayBehalfName) !== '')
                                                        <div class="small text-secondary">
                                                            Received in behalf:
                                                            <span class="badge badge-pink">{{ mb_strtoupper($displayBehalfName, 'UTF-8') }}</span>
                                                        </div>
                                                    @endif
                                                    @if($documentTypeName !== '')
                                                        <div class="small text-secondary">Type: {{ mb_strtoupper($documentTypeName, 'UTF-8') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            @php
                                                $plain = trim((string) $log->remarks);
                                                $sev = 'info';
                                                $t = mb_strtolower($plain, 'UTF-8');
                                                if ($plain !== '') {
                                                    if (str_contains($t, 'urgent') || str_contains($t, 'immediate') || str_contains($t, 'critical') || str_contains($t, 'error') || str_contains($t, 'fail')) { $sev = 'critical'; }
                                                    elseif (str_contains($t, 'warn') || str_contains($t, 'delay') || str_contains($t, 'pending')) { $sev = 'warning'; }
                                                    elseif (str_contains($t, 'success') || str_contains($t, 'completed') || str_contains($t, 'done') || str_contains($t, 'ok')) { $sev = 'positive'; }
                                                }
                                                $icon = $sev === 'critical' ? 'bi-exclamation-octagon-fill' : ($sev === 'warning' ? 'bi-exclamation-triangle-fill' : ($sev === 'positive' ? 'bi-check-circle-fill' : 'bi-card-text'));
                                                $chip = $sev === 'critical' ? 'chip-critical' : ($sev === 'warning' ? 'chip-warning' : ($sev === 'positive' ? 'chip-positive' : 'chip-info'));
                                                $card = $sev === 'critical' ? 'remark-critical' : ($sev === 'warning' ? 'remark-warning' : ($sev === 'positive' ? 'remark-positive' : 'remark-info'));
                                            @endphp
                                            <div class="remark-card {{ $card }} mt-2" role="region" aria-label="Remarks">
                                                <div class="remark-head">
                                                    <div class="remark-title">
                                                        <i class="remark-icon bi {{ $icon }}" aria-hidden="true"></i>
                                                        <span>Remarks</span>
                                                    </div>
                                                    <div class="remark-badges">
                                                        <span class="remark-chip {{ $chip }}">{{ strtoupper($sev) }}</span>
                                                    </div>
                                                </div>
                                                <div class="remark-body">{!! nl2br(e($plain)) !!}</div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </li>
                        @empty
                            @php
                                $stepNumber = 1;
                            @endphp
                            <li class="dt-step dt-current" aria-current="step" aria-label="Step {{ $stepNumber }}">
                                <div class="dt-rail" aria-hidden="true">
                                    <span class="dt-indicator">{{ $stepNumber }}</span>
                                </div>
                                <div class="dt-card">
                                    <div class="dt-meta">
                                        <div class="dt-title">
                                            <span class="dt-badge">NO LOGS</span>
                                            <span class="badge text-bg-primary">Current</span>
                                        </div>
                                    </div>
                                    <div class="small text-secondary mt-1">No history logs yet.</div>
                                </div>
                            </li>
                        @endforelse

                        @if(count($futureStatuses) > 0)
                            @php
                                $stepNumber++;
                                $nextStatus = (string) ($futureStatuses[0] ?? '');
                            @endphp
                            <li class="dt-step dt-future" style="--dt-delay: {{ min(($stepNumber - 1) * 35, 250) }}ms" aria-label="Upcoming step {{ $stepNumber }}">
                                <div class="dt-rail" aria-hidden="true">
                                    <span class="dt-indicator">{{ $stepNumber }}</span>
                                </div>
                                <div class="dt-card">
                                    <div class="dt-meta">
                                        <div class="dt-title">
                                            <span class="dt-badge">Waiting for next step</span>
                                            @if(($canAddUpdate ?? false) === true || (bool) ($isAdminUser ?? false))
                                                <button type="button" class="btn btn-sm btn-secondary ms-2" id="btnAddUpdate">Add Update</button>
                                            @endif
                                        </div>
                                    </div>
                                    {{-- @if($nextStatus !== '')
                                        <div class="small text-secondary mt-1">Next: {{ $nextStatus }}</div>
                                    @endif --}}
                                </div>
                            </li>
                        @endif
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

@if(($canAddUpdate ?? false) === true || (bool) ($isAdminUser ?? false))
    <div class="modal fade" id="addUpdateModal" tabindex="-1" aria-labelledby="addUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUpdateModalLabel">Add Update</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addUpdateForm">
                        @csrf
                        <div class="mb-3">
                            <div class="row g-2 align-items-start">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Forwarded To <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Document From <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                                </div>
                                <div class="col-12">
                                    <div class="row g-2">
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex flex-column gap-2" role="radiogroup" aria-label="Document To">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="manual_update_party_choice" id="update_to_section" value="to_section" required>
                                                    <label class="form-check-label" for="update_to_section">Section</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="manual_update_party_choice" id="update_to_staff" value="to_staff">
                                                    <label class="form-check-label" for="update_to_staff">Staff</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex flex-column gap-2" role="radiogroup" aria-label="Document From">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="manual_update_party_choice" id="update_from_section" value="from_section" checked>
                                                    <label class="form-check-label" for="update_from_section">Section</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="manual_update_party_choice" id="update_from_staff" value="from_staff">
                                                    <label class="form-check-label" for="update_from_staff">Staff</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="manual_update_party" id="manual_update_party" value="from">
                                    <input type="hidden" name="document_from_type" id="manual_update_type" value="section">
                                </div>
                            <div class="text-danger small mt-1 d-none" data-feedback-for="document_from_type"></div>
                        </div>
                        <div class="divider my-3"></div>
                        <div class="mb-3">
                            <label for="return_from_document_source_id" class="form-label"><span id="return_from_label_text">Returned from</span> <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                            <select name="return_from_document_source_id" id="return_from_document_source_id" class="form-select" required>
                                <option value="">Select returned from</option>
                                @foreach($returnFromSources as $s)
                                    <option value="{{ $s->id }}" data-type="{{ $s->source_type }}">{{ strtoupper($s->source_type) }} - {{ $s->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback d-block" data-feedback-for="return_from_document_source_id"></div>
                        </div>

                        <div class="mb-0">
                            <label for="update_text" class="form-label">Type your update in this document <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                            <textarea name="update_text" id="update_text" rows="6" class="form-control" required></textarea>
                            <div class="invalid-feedback d-block" data-feedback-for="update_text"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSaveUpdate">Save</button>
                </div>
            </div>
        </div>
    </div>
@endif

@if($incomingDocument->attachment_path)
    <div class="modal fade" id="attachmentModal" tabindex="-1" aria-labelledby="attachmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" id="attachmentModalDialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attachmentModalLabel">Attachment Preview</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="attachmentToggleFullscreen">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <a class="btn btn-sm btn-outline-primary" href="{{ $attachmentUrl }}" target="_blank" rel="noopener">Download</a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-2 p-md-3">
                    @if($attachmentExt === 'pdf')
                        <embed src="{{ $attachmentUrl }}" type="application/pdf" style="width: 100%; height: 75vh;">
                    @elseif(in_array($attachmentExt, ['png','jpg','jpeg','gif','webp','bmp','svg'], true))
                        <img src="{{ $attachmentUrl }}" alt="Attachment" class="img-fluid w-100" style="max-height: 75vh; object-fit: contain;">
                    @else
                        <iframe src="{{ $attachmentUrl }}" title="Attachment" style="width: 100%; height: 75vh; border: 0;"></iframe>
                        <div class="small text-secondary mt-2">If the preview does not load, use Download.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

<div class="modal fade" id="editTrackingLogModal" tabindex="-1" aria-labelledby="editTrackingLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTrackingLogModalLabel">Edit Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editTrackingLogForm" autocomplete="off" novalidate>
                    <input type="hidden" id="edit_log_id" name="log_id">
                    <div class="mb-3" id="edit_update_text_wrap">
                        <label class="form-label" for="edit_update_text">Update Text</label>
                        <textarea class="form-control" id="edit_update_text" name="update_text" rows="6"></textarea>
                        <div class="invalid-feedback" data-edit-log-feedback-for="update_text"></div>
                    </div>
                    <div class="mb-3 d-none" id="edit_remarks_wrap">
                        <label class="form-label" for="edit_remarks">Remarks</label>
                        <textarea class="form-control" id="edit_remarks" name="remarks" rows="6"></textarea>
                        <div class="invalid-feedback" data-edit-log-feedback-for="remarks"></div>
                    </div>
                </form>
                <div class="alert alert-danger d-none mb-0" role="alert" id="editLogGenericError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="editLogCancelBtn">Cancel</button>
                <button type="button" class="btn btn-primary" id="editLogSaveBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="editLogSpinner"></span>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const GROUPS_URL = "{{ route('incoming-documents.lookups.groups') }}";
        const STAFF_URL = "{{ route('incoming-documents.lookups.staff') }}";
        const STAFF_LIMIT = 20;
        const GROUP_STORE_URL = "{{ route('groups.store') }}";
        const EDITABLE_LOGS = @json($editableLogs ?? []);
        const UPDATE_LOG_URL_TEMPLATE = @json(route('incoming-documents.logs.update', [$incomingDocument->id, '__LOG_ID__']));

        let staffQuery = '';
        let staffOffset = 0;
        let staffHasMore = false;
        let staffTimer = null;
        const selectedStaff = new Map();

        const $forwardModal = $('#forwardModal');
        const editLogModalEl = document.getElementById('editTrackingLogModal');
        if (editLogModalEl && editLogModalEl.parentElement !== document.body) {
            document.body.appendChild(editLogModalEl);
        }
        const editLogModal = editLogModalEl ? new bootstrap.Modal(editLogModalEl, { backdrop: 'static', keyboard: false }) : null;
        const $editLogForm = $('#editTrackingLogForm');
        const $editLogError = $('#editLogGenericError');
        const $editLogSpinner = $('#editLogSpinner');
        const $editLogSaveBtn = $('#editLogSaveBtn');
        const $editLogCancelBtn = $('#editLogCancelBtn');

        const clearEditLogErrors = function () {
            $editLogError.addClass('d-none').text('');
            $editLogForm.find('.is-invalid').removeClass('is-invalid');
            $editLogForm.find('[data-edit-log-feedback-for]').text('');
        };

        const setEditLogInvalid = function (name, message) {
            const $field = $editLogForm.find('[name="' + name + '"]');
            $field.addClass('is-invalid');
            $editLogForm.find('[data-edit-log-feedback-for="' + name + '"]').text(message || '');
        };

        const setEditLogSaving = function (saving) {
            $editLogSpinner.toggleClass('d-none', !saving);
            $editLogSaveBtn.prop('disabled', saving);
            $editLogCancelBtn.prop('disabled', saving);
            $editLogForm.find('input,select,textarea,button').prop('disabled', saving);
        };

        const openEditTrackingLogModal = function (logId) {
            if (!editLogModal) return;
            const info = EDITABLE_LOGS && Object.prototype.hasOwnProperty.call(EDITABLE_LOGS, logId) ? EDITABLE_LOGS[logId] : null;
            if (!info) return;

            clearEditLogErrors();
            $('#edit_log_id').val(String(logId));
            $editLogForm.data('mode', String(info.mode || ''));

            if (String(info.mode) === 'manual_update') {
                $('#edit_update_text_wrap').removeClass('d-none');
                $('#edit_remarks_wrap').addClass('d-none');
                $('#edit_update_text').val(String(info.update_text || ''));
                $('#edit_remarks').val('');
            } else {
                $('#edit_update_text_wrap').addClass('d-none');
                $('#edit_remarks_wrap').removeClass('d-none');
                $('#edit_remarks').val(String(info.remarks || ''));
                $('#edit_update_text').val('');
            }

            editLogModal.show();
        };

        if (editLogModalEl) {
            editLogModalEl.addEventListener('show.bs.modal', function () {
                window.setTimeout(function () {
                    $('.modal-backdrop').last().addClass('edit-log-backdrop');
                }, 0);
            });
            editLogModalEl.addEventListener('shown.bs.modal', function () {
                const mode = String($editLogForm.data('mode') || '');
                if (mode === 'manual_update') {
                    $('#edit_update_text').trigger('focus');
                } else {
                    $('#edit_remarks').trigger('focus');
                }
            });
            editLogModalEl.addEventListener('hidden.bs.modal', function () {
                setEditLogSaving(false);
                clearEditLogErrors();
                $editLogForm.removeData('mode');
                if ($editLogForm.length) {
                    $editLogForm[0].reset();
                }
            });
        }

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

        const pulseSelect2Selection = function ($selectEl) {
            const $container = $selectEl.next('.select2-container');
            const $sel = $container.find('.select2-selection');
            if (!$sel.length) return;

            $sel.addClass('border border-success');
            window.setTimeout(function () {
                $sel.removeClass('border border-success');
            }, 1600);
        };

        function initForwardUserSelect2() {
            const $select = $('#forwarded_to_user_id');
            if (!$select.length) return;
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select user',
                allowClear: true,
                dropdownParent: $forwardModal.length ? $forwardModal : $(document.body),
            });
        }

        function initForwardGroupSelect2() {
            const $select = $('#forwarded_to_group_id');
            if (!$select.length) return;
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select group',
                allowClear: true,
                dropdownParent: $forwardModal.length ? $forwardModal : $(document.body),
                escapeMarkup: function (markup) { return markup; },
                language: {
                    noResults: function () {
                        return '<span class="d-inline-flex align-items-center flex-wrap gap-2">' +
                            '<span class="text-secondary">No result found</span>' +
                            '<button type="button" class="btn btn-sm btn-link p-0 select2-add-new-group" aria-label="Add new group" tabindex="0">' +
                            '<i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add New</button>' +
                            '</span>';
                    },
                },
            });
        }

        function setError(message) {
            if (!message) {
                $('#staff_error').addClass('d-none').text('');
                return;
            }
            $('#staff_error').removeClass('d-none').text(message);
        }

        const $addUpdateModal = $('#addUpdateModal');
        const addUpdateModalInstance = $addUpdateModal.length ? new bootstrap.Modal($addUpdateModal[0]) : null;

        const addGroupModalEl = document.getElementById('addGroupModal');
        const addGroupModal = addGroupModalEl ? new bootstrap.Modal(addGroupModalEl, { backdrop: 'static', keyboard: false }) : null;
        const $addGroupForm = $('#addGroupForm');
        const $addGroupError = $('#addGroupGenericError');
        const $addGroupSpinner = $('#addGroupSpinner');
        const $addGroupSaveBtn = $('#addGroupSaveBtn');
        const $addGroupCancelBtn = $('#addGroupCancelBtn');

        const clearAddGroupErrors = function () {
            $addGroupError.addClass('d-none').text('');
            $addGroupForm.find('.is-invalid').removeClass('is-invalid');
            $addGroupForm.find('[data-add-group-feedback-for]').text('');
        };

        const setAddGroupInvalid = function (name, message) {
            const $field = $addGroupForm.find('[name="' + name + '"]');
            $field.addClass('is-invalid');
            $addGroupForm.find('[data-add-group-feedback-for="' + name + '"]').text(message || '');
        };

        const setAddGroupSaving = function (saving) {
            $addGroupSpinner.toggleClass('d-none', !saving);
            $addGroupSaveBtn.prop('disabled', saving);
            $addGroupCancelBtn.prop('disabled', saving);
            $addGroupForm.find('input,select,textarea,button').prop('disabled', saving);
        };

        const openAddGroupModal = function (prefillName) {
            if (!addGroupModal) return;
            clearAddGroupErrors();
            $('#add_group_status').val('1');
            $('#add_group_name').val(prefillName || '');
            addGroupModal.show();
        };

        const attachNewGroupToForwardForm = function (payload) {
            const id = payload && payload.id ? String(payload.id) : '';
            const name = payload && (payload.group_name || payload.name) ? String(payload.group_name || payload.name) : '';
            if (!id || !name) return;

            const $select = $('#forwarded_to_group_id');
            if ($select.find('option[value="' + id + '"]').length === 0) {
                $select.append(new Option(name, id, false, false));
            }
            $select.val(id).trigger('change');
            pulseSelect2Selection($select);
        };

        if (addGroupModalEl) {
            addGroupModalEl.addEventListener('shown.bs.modal', function () {
                $('#add_group_name').trigger('focus');
            });
            addGroupModalEl.addEventListener('hidden.bs.modal', function () {
                setAddGroupSaving(false);
                clearAddGroupErrors();
                if ($addGroupForm.length) {
                    $addGroupForm[0].reset();
                }
            });
        }

        $(document).on('click', '.select2-add-new-group', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const term = $('.select2-container--open .select2-search__field').val() || '';
            $('#forwarded_to_group_id').select2('close');
            openAddGroupModal(String(term).trim());
        });

        $addGroupSaveBtn.on('click', function () {
            clearAddGroupErrors();

            const groupName = String($('#add_group_name').val() || '').trim();
            const status = String($('#add_group_status').val() || '').trim();

            let ok = true;
            if (groupName === '') {
                ok = false;
                setAddGroupInvalid('group_name', 'Group name is required.');
            }
            if (status !== '0' && status !== '1') {
                ok = false;
                setAddGroupInvalid('status', 'Status is required.');
            }
            if (!ok) return;

            setAddGroupSaving(true);
            $.ajax({
                url: GROUP_STORE_URL,
                type: 'POST',
                dataType: 'json',
                data: { group_name: groupName, status: status },
                headers: { 'Accept': 'application/json' },
                success: function (resp) {
                    toastr.success(resp && resp.success ? resp.success : 'Saved.');
                    attachNewGroupToForwardForm(resp && resp.data ? resp.data : null);
                    fetchGroups();
                    if (addGroupModal) addGroupModal.hide();
                },
                error: function (xhr) {
                    const statusCode = xhr && xhr.status ? xhr.status : 0;
                    if (statusCode === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (k) {
                            if (!errors[k] || !errors[k].length) return;
                            setAddGroupInvalid(k, errors[k][0]);
                        });
                        toastr.error('Please fix the highlighted fields.');
                        return;
                    }
                    $addGroupError.removeClass('d-none').text('Failed to save. Please try again.');
                    toastr.error('Failed to save group.');
                },
                complete: function () {
                    setAddGroupSaving(false);
                },
            });
        });

        $(document).on('click', '.js-edit-tracking-log', function () {
            const id = String($(this).data('log-id') || '').trim();
            if (!id) return;
            openEditTrackingLogModal(id);
        });

        $editLogSaveBtn.on('click', function () {
            clearEditLogErrors();
            const logId = String($('#edit_log_id').val() || '').trim();
            const mode = String($editLogForm.data('mode') || '').trim();
            if (!logId || !mode) return;

            const url = String(UPDATE_LOG_URL_TEMPLATE).replace('__LOG_ID__', logId);
            const payload = {};
            if (mode === 'manual_update') {
                payload.update_text = String($('#edit_update_text').val() || '').trim();
                if (payload.update_text === '') {
                    setEditLogInvalid('update_text', 'Update text is required.');
                    return;
                }
            } else {
                payload.remarks = String($('#edit_remarks').val() || '');
            }

            setEditLogSaving(true);
            $.ajax({
                url: url,
                type: 'PUT',
                dataType: 'json',
                data: payload,
                headers: { 'Accept': 'application/json' },
                success: function (resp) {
                    toastr.success(resp && resp.success ? resp.success : 'Saved.');
                    if (editLogModal) editLogModal.hide();
                    window.location.reload();
                },
                error: function (xhr) {
                    const status = xhr && xhr.status ? xhr.status : 0;
                    if (status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (k) {
                            if (!errors[k] || !errors[k].length) return;
                            setEditLogInvalid(k, errors[k][0]);
                        });
                        toastr.error('Please fix the highlighted fields.');
                        return;
                    }
                    if (status === 403) {
                        toastr.error('You are not allowed to edit this log.');
                        return;
                    }
                    $editLogError.removeClass('d-none').text('Failed to save. Please try again.');
                    toastr.error('Failed to update log.');
                },
                complete: function () {
                    setEditLogSaving(false);
                },
            });
        });

        function setAddUpdateFieldError(name, message) {
            const $feedback = $addUpdateModal.find('[data-feedback-for="' + name + '"]');
            if ($feedback.length) {
                $feedback.text(message || '');
            }
        }

        function clearAddUpdateErrors() {
            setAddUpdateFieldError('document_from_type', '');
            setAddUpdateFieldError('return_from_document_source_id', '');
            setAddUpdateFieldError('update_text', '');
        }

        function getAddUpdatePartyState() {
            const choice = String($addUpdateModal.find('input[name="manual_update_party_choice"]:checked').val() || '').trim();
            const parts = choice.split('_');
            const party = parts[0] === 'to' ? 'to' : 'from';
            const type = parts[1] === 'staff' ? 'staff' : 'section';
            return { party: party, type: type };
        }

        function syncAddUpdatePartyState() {
            const st = getAddUpdatePartyState();
            $('#manual_update_party').val(st.party);
            $('#manual_update_type').val(st.type);

            const label = st.party === 'to' ? 'Forward to' : 'Returned from';
            const placeholder = st.party === 'to' ? 'Select forward to' : 'Select returned from';
            $('#return_from_label_text').text(label);
            $('#return_from_document_source_id').data('placeholder', placeholder);
        }

        const allReturnFromOptions = [];
        $('#return_from_document_source_id option').each(function () {
            const val = $(this).attr('value');
            const type = $(this).data('type');
            if (!val || !type) return;
            allReturnFromOptions.push({ value: val, type: type, text: $(this).text() });
        });

        function initReturnFromSelect2() {
            const select = $('#return_from_document_source_id');
            if (!select.length) return;
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }
            const placeholder = String(select.data('placeholder') || 'Select returned from');
            select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: placeholder,
                allowClear: true,
                dropdownParent: $addUpdateModal.length ? $addUpdateModal : $(document.body),
            });
        }

        function rebuildReturnFromOptions() {
            const type = String($('#manual_update_type').val() || '').trim();
            const placeholder = String($('#return_from_document_source_id').data('placeholder') || 'Select returned from');
            const selected = $('#return_from_document_source_id').val();
            const select = $('#return_from_document_source_id');

            select.empty();
            select.append(new Option(placeholder, '', false, false));

            let stillSelected = false;
            allReturnFromOptions.forEach(function (opt) {
                if (opt.type !== type) return;
                const isSelected = selected && String(selected) === String(opt.value);
                if (isSelected) stillSelected = true;
                select.append(new Option(opt.text, opt.value, false, isSelected));
            });

            if (!stillSelected) {
                select.val('');
            }

            initReturnFromSelect2();
        }

        $(document).on('click', '#btnAddUpdate', function () {
            if (!addUpdateModalInstance) return;
            clearAddUpdateErrors();
            $addUpdateModal.find('#update_text').val('');
            $addUpdateModal.find('#update_from_section').prop('checked', true).trigger('change');
            syncAddUpdatePartyState();
            rebuildReturnFromOptions();
            addUpdateModalInstance.show();
        });

        $addUpdateModal.on('change', 'input[name="manual_update_party_choice"]', function () {
            setAddUpdateFieldError('document_from_type', '');
            syncAddUpdatePartyState();
            rebuildReturnFromOptions();
        });
        $addUpdateModal.on('change', '#return_from_document_source_id', function () {
            setAddUpdateFieldError('return_from_document_source_id', '');
        });
        $addUpdateModal.on('input', '#update_text', function () {
            setAddUpdateFieldError('update_text', '');
        });

        $(document).on('click', '#btnSaveUpdate', function () {
            clearAddUpdateErrors();

            const fromType = String($('#manual_update_type').val() || '').trim();
            const party = String($('#manual_update_party').val() || '').trim();
            const returnFrom = $('#return_from_document_source_id').val();
            const updateText = String($addUpdateModal.find('#update_text').val() || '').trim();
            const returnFromLabel = party === 'to' ? 'Forward to' : 'Returned from';

            let ok = true;
            if (!fromType) {
                ok = false;
                setAddUpdateFieldError('document_from_type', 'Document from type is required.');
            }
            if (!returnFrom) {
                ok = false;
                setAddUpdateFieldError('return_from_document_source_id', returnFromLabel + ' is required.');
            }
            if (!updateText) {
                ok = false;
                setAddUpdateFieldError('update_text', 'Update text is required.');
            }
            if (!ok) return;

            const $btn = $('#btnSaveUpdate');
            $btn.prop('disabled', true);

            $.ajax({
                url: "{{ route('incoming-documents.add-update', $incomingDocument) }}",
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: "{{ csrf_token() }}",
                    manual_update_party: party,
                    document_from_type: fromType,
                    return_from_document_source_id: returnFrom,
                    update_text: updateText,
                },
            }).done(function () {
                window.location.reload();
            }).fail(function (xhr) {
                $btn.prop('disabled', false);
                if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    if (errs.document_from_type && errs.document_from_type[0]) {
                        setAddUpdateFieldError('document_from_type', errs.document_from_type[0]);
                    }
                    if (errs.return_from_document_source_id && errs.return_from_document_source_id[0]) {
                        setAddUpdateFieldError('return_from_document_source_id', errs.return_from_document_source_id[0]);
                    }
                    if (errs.update_text && errs.update_text[0]) {
                        setAddUpdateFieldError('update_text', errs.update_text[0]);
                    }
                    return;
                }
                setAddUpdateFieldError('update_text', 'Failed to save update.');
            });
        });

        function updateSelectedUI() {
            $('#staff_selected_count').text(String(selectedStaff.size));
            $('#staff_selected_limit').text(String(STAFF_LIMIT));

            const $list = $('#staff_selected_list');
            $list.empty();

            const $inputs = $('#staff_selected_inputs');
            $inputs.empty();

            selectedStaff.forEach((v, id) => {
                $inputs.append(`<input type="hidden" name="forwarded_to_user_ids[]" value="${id}">`);

                const safeText = $('<div>').text(v.full_name).html();
                const safeMeta = $('<div>').text([v.email, v.department].filter(Boolean).join(' · ')).html();

                $list.append(`
                    <li class="list-group-item list-group-item-success d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${safeText}</div>
                            <div class="small text-secondary">${safeMeta}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger staff-remove" data-id="${id}" aria-label="Remove ${safeText}">Remove</button>
                    </li>
                `);
            });

            $('#staff_clear_all').prop('disabled', selectedStaff.size === 0);
        }

        function renderStaffResults(items, append) {
            const $results = $('#staff_results');
            if (!append) {
                $results.empty();
            }

            const visibleItems = (items || []).filter((it) => !selectedStaff.has(it.id));

            if (visibleItems.length === 0 && !append) {
                $results.append('<div class="list-group-item text-secondary small">No results.</div>');
                return;
            }

            visibleItems.forEach((it) => {
                const isSelected = selectedStaff.has(it.id);
                const safeText = $('<div>').text(it.full_name).html();
                const safeMeta = $('<div>').text([it.email, it.department].filter(Boolean).join(' · ')).html();

                $results.append(`
                    <button type="button" class="list-group-item list-group-item-action staff-add d-flex justify-content-between align-items-start gap-2" data-id="${it.id}" ${isSelected ? 'disabled' : ''}>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${safeText}</div>
                            <div class="small text-secondary staff-meta">${safeMeta}</div>
                        </div>
                        <div class="small ${isSelected ? 'text-secondary' : 'text-primary'} staff-action">${isSelected ? 'Selected' : 'Add'}</div>
                    </button>
                `);
            });
        }

        function fetchStaff(reset) {
            const q = staffQuery;
            if (q.length < 2) {
                staffOffset = 0;
                staffHasMore = false;
                renderStaffResults([], false);
                $('#staff_load_more').addClass('d-none');
                return;
            }

            if (reset) {
                staffOffset = 0;
                staffHasMore = false;
                renderStaffResults([], false);
            }

            $.ajax({
                url: STAFF_URL,
                type: "GET",
                data: { q: q, offset: staffOffset },
                success: function(resp) {
                    if (!resp || resp.success !== true) {
                        setError(resp && resp.message ? resp.message : 'Unable to load staff.');
                        return;
                    }
                    setError('');
                    renderStaffResults(resp.items || [], reset === false && staffOffset > 0);
                    staffHasMore = !!resp.has_more;
                    staffOffset = resp.next_offset ?? staffOffset;
                    $('#staff_load_more').toggleClass('d-none', !staffHasMore);
                },
                error: function() {
                    setError('Unable to load staff.');
                }
            });
        }

        function fetchGroups() {
            const $select = $('#forwarded_to_group_id');
            const initial = String($select.data('initial') ?? '').trim();
            const current = String($select.val() ?? '').trim() || initial;
            initForwardGroupSelect2();
            $.ajax({
                url: GROUPS_URL,
                type: "GET",
                success: function(resp) {
                    const groups = resp && resp.success ? (resp.groups || []) : [];
                    const validIds = new Set(groups.map(g => String(g.id)));

                    $select.empty();
                    $select.append('<option value="">— Select Group —</option>');
                    groups.forEach((g) => {
                        const safeName = $('<div>').text(g.name ?? g.group_name ?? '').html();
                        $select.append(`<option value="${g.id}">${safeName}</option>`);
                    });

                    if (current && validIds.has(String(current))) {
                        $select.val(String(current));
                    } else {
                        $select.val('');
                    }
                    $select.data('initial', '');
                    $select.trigger('change.select2');
                },
                error: function() {
                    $select.empty();
                    $select.append('<option value="">— Select Group —</option>');
                    $select.val('').trigger('change.select2');
                }
            });
        }

        function setGroupStaffMode(enabled) {
            $('#forward_staff_mode').val(enabled ? '1' : '0');
            $('#staff_picker').toggleClass('d-none', !enabled);
            $('#forwarded_to_group_id').prop('disabled', enabled);
            if (enabled) {
                $('#forwarded_to_group_id').val('');
                $('#forwarded_to_group_id').trigger('change.select2');
                if ($('#forwardModal').hasClass('show')) {
                    $('#staff_search').trigger('focus');
                }
            } else {
                setError('');
            }
        }

        function toggleForwardTarget() {
            const to = $('input[name="forward_to"]:checked').val();
            if (to === 'user') {
                $('#forward_user_wrap').removeClass('d-none');
                $('#forward_group_wrap').addClass('d-none');
                $('input[name="group_target_mode"][value="group"]').prop('checked', true).trigger('change');
                setGroupStaffMode(false);
            } else {
                $('#forward_user_wrap').addClass('d-none');
                $('#forward_group_wrap').removeClass('d-none');
                fetchGroups();
                const mode = $('input[name="group_target_mode"]:checked').val();
                setGroupStaffMode(mode === 'staff');
            }
        }

        $('input[name="forward_to"]').on('change', toggleForwardTarget);
        $('input[name="group_target_mode"]').on('change', function() {
            const mode = $('input[name="group_target_mode"]:checked').val();
            setGroupStaffMode(mode === 'staff');
            fetchGroups();
        });

        $('#staff_search').on('input', function() {
            staffQuery = $(this).val().trim();
            if (staffTimer) {
                clearTimeout(staffTimer);
            }
            staffTimer = setTimeout(function() {
                fetchStaff(true);
            }, 300);
        });

        $(document).on('click', '.staff-add', function() {
            const id = parseInt($(this).data('id'), 10);
            if (selectedStaff.has(id)) {
                return;
            }
            if (selectedStaff.size >= STAFF_LIMIT) {
                setError(`You can select up to ${STAFF_LIMIT} staff members.`);
                return;
            }

            const $btn = $(this);
            const fullName = $btn.find('.fw-semibold').text();
            const meta = $btn.find('.staff-meta').text();
            const parts = meta.split('·').map(s => s.trim()).filter(Boolean);
            const email = parts[0] || '';
            const department = parts.slice(1).join(' · ');

            selectedStaff.set(id, { id: id, full_name: fullName, email: email, department: department });
            updateSelectedUI();
            $btn.remove();
        });

        $(document).on('click', '.staff-remove', function() {
            const id = parseInt($(this).data('id'), 10);
            selectedStaff.delete(id);
            updateSelectedUI();
            $(`.staff-add[data-id="${id}"]`).prop('disabled', false);
            if (staffQuery.length >= 2) {
                fetchStaff(true);
            }
        });

        $('#staff_clear_all').on('click', function() {
            selectedStaff.clear();
            updateSelectedUI();
            $('.staff-add').prop('disabled', false);
            setError('');
            if (staffQuery.length >= 2) {
                fetchStaff(true);
            }
        });

        $('#staff_load_more').on('click', function() {
            if (!staffHasMore) {
                return;
            }
            fetchStaff(false);
        });

        $('#forwardModal').on('shown.bs.modal', function() {
            const to = $('input[name="forward_to"]:checked').val();
            if (to === 'group' && $('#forward_staff_mode').val() === '1') {
                $('#staff_search').trigger('focus');
                return;
            }
            if (to === 'group') {
                $('#forwarded_to_group_id').trigger('focus');
                return;
            }
            $('#forwarded_to_user_id').trigger('focus');
        });

        initForwardUserSelect2();
        bindSelect2AddNewKeyboard('#forwarded_to_group_id', '.select2-add-new-group', openAddGroupModal);

        updateSelectedUI();
        toggleForwardTarget();

        const shouldOpenForwardModal = @json((bool) (old('forward_to') || old('forwarded_to_user_id') || old('forwarded_to_group_id') || old('forward_staff_mode')));
        const hasErrors = @json($errors->any());
        if (hasErrors && shouldOpenForwardModal) {
            const modalEl = document.getElementById('forwardModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        }

        $('#forwardForm').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $submit = $form.find('button[type="submit"]');
            const $alerts = $('#forward_ajax_alert');
            $alerts.empty();
            $submit.prop('disabled', true);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                headers: { 'Accept': 'application/json' },
                success: function(resp) {
                    const msgs = [];
                    if (resp && resp.message) {
                        const safe = $('<div>').text(resp.message).html();
                        msgs.push(`<div class="alert alert-success mb-3">${safe}</div>`);
                    }
                    if (resp && resp.warning) {
                        const safeW = $('<div>').text(resp.warning).html();
                        msgs.push(`<div class="alert alert-warning mb-3">${safeW}</div>`);
                    }
                    $alerts.html(msgs.join(''));
                    $submit.prop('disabled', false);

                    const added = Array.isArray(resp && resp.added_user_ids) ? resp.added_user_ids.length : 0;
                    if (added > 0) {
                        const modalEl = document.getElementById('forwardModal');
                        if (modalEl) {
                            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                        }
                        $.get(window.location.href).done(function(html) {
                            const doc = $('<div>').html(html);
                            const $newHist = doc.find('#tracking_history');
                            if ($newHist.length) {
                                $('#tracking_history').replaceWith($newHist);
                            }
                        });
                        setTimeout(() => { try { $alerts.empty(); } catch (e) {} }, 3000);
                    } else {
                        const $pageAlerts = $('#page_alerts');
                        const text = resp && resp.message ? resp.message : 'No staff were forwarded.';
                        const safeText = $('<div>').text(text).html();
                        const $alert = $(`<div class="alert alert-warning alert-dismissible fade show shadow" role="alert">${safeText}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`);
                        $pageAlerts.append($alert);
                        setTimeout(() => { try { $alert.alert('close'); } catch (e) {} }, 5000);
                        setTimeout(() => { try { $alerts.empty(); } catch (e) {} }, 3000);
                    }
                },
                error: function(xhr) {
                    let text = 'Unable to forward document.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.errors) {
                        const keys = Object.keys(xhr.responseJSON.errors);
                        if (keys.length > 0) {
                            text = xhr.responseJSON.errors[keys[0]][0];
                        }
                    }
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        text = xhr.responseJSON.message;
                    }
                    const safe = $('<div>').text(text).html();
                    $alerts.html(`<div class="alert alert-danger mb-3">${safe}</div>`);
                    const $pageAlerts = $('#page_alerts');
                    const $alert = $(`<div class="alert alert-danger alert-dismissible fade show shadow" role="alert">${safe}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`);
                    $pageAlerts.append($alert);
                    setTimeout(() => { try { $alert.alert('close'); } catch (e) {} }, 5000);
                    setTimeout(() => { try { $alerts.empty(); } catch (e) {} }, 3000);
                    $submit.prop('disabled', false);
                }
            });
        });

        function formatDaysLeft(days) {
            const abs = Math.abs(days);
            const unit = abs === 1 ? 'day' : 'days';
            if (days === 0) return 'Due today';
            if (days > 0) return `${days} ${unit} left`;
            return `${days} ${unit} overdue`;
        }

        function updateDaysLeft() {
            const $el = $('#deadline_days_left');
            if (!$el.length) return;

            const deadline = String($el.data('deadline') || '').trim();
            if (!deadline) {
                $el.text('');
                return;
            }

            const deadlineDate = new Date(`${deadline}T00:00:00`);
            if (Number.isNaN(deadlineDate.getTime())) {
                $el.text('');
                return;
            }

            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const msPerDay = 24 * 60 * 60 * 1000;
            const diffDays = Math.round((deadlineDate.getTime() - today.getTime()) / msPerDay);

            const text = formatDaysLeft(diffDays);
            $el.text(text);
            $el.toggleClass('text-danger', diffDays < 0);
            $el.toggleClass('text-success', diffDays > 0);
            $el.toggleClass('text-secondary', diffDays === 0);
        }

        updateDaysLeft();
        setInterval(updateDaysLeft, 60 * 1000);

        $('#attachmentToggleFullscreen').on('click', function() {
            const $dialog = $('#attachmentModalDialog');
            if (!$dialog.length) return;
            $dialog.toggleClass('modal-fullscreen');
        });
    });
</script>
@endpush
