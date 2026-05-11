@php
    $currentSort = $sort ?? (string) request('sort', 'date_created');
    $currentDir = $dir ?? (string) request('dir', 'desc');
    $toggleDir = function (string $col) use ($currentSort, $currentDir) {
        return $currentSort === $col && strtolower($currentDir) === 'asc' ? 'desc' : 'asc';
    };
    $sortUrl = function (string $col) use ($toggleDir) {
        return route('inbox.batch.received', array_merge(request()->all(), ['sort' => $col, 'dir' => $toggleDir($col)]));
    };
@endphp

<form id="received_docs_filter_form" action="{{ route('inbox.batch.received') }}" method="GET" class="row g-2 align-items-end mb-2">
    <div class="col-md-6">
        <label for="received_search" class="form-label small mb-1">Search</label>
        <input type="text" id="received_search" name="search" class="form-control form-control-sm" value="{{ $search ?? '' }}" placeholder="Batch ID / Batch name / Document number / Subject / Type / Origin office" autocomplete="off">
    </div>
    <div class="col-md-3">
        <label for="received_status" class="form-label small mb-1">Status</label>
        <select id="received_status" name="status" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="0" {{ (string) ($status ?? '') === '0' ? 'selected' : '' }}>Not received</option>
            <option value="1" {{ (string) ($status ?? '') === '1' ? 'selected' : '' }}>Received by batch</option>
        </select>
    </div>
    <div class="col-md-3">
        <label for="received_per_page" class="form-label small mb-1">Show</label>
        <select id="received_per_page" name="per_page" class="form-select form-select-sm">
            @foreach([10, 25, 50, 100] as $n)
                <option value="{{ $n }}" {{ (int) ($perPage ?? 10) === (int) $n ? 'selected' : '' }}>{{ $n }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-sm btn-primary" type="submit">
            <i class="bi bi-search"></i> Search
        </button>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('inbox.batch.received') }}">
            <i class="bi bi-arrow-counterclockwise"></i> Reset
        </a>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 160px;"><a href="{{ $sortUrl('batch_id') }}" class="text-decoration-none">Receive</a></th>
                <th style="width: 100px;"><a href="{{ $sortUrl('batch_name') }}" class="text-decoration-none">Batch Name</a></th>
                <th style="width: 100px;"><a href="{{ $sortUrl('status') }}" class="text-decoration-none">Status</a></th>
                <th style="width: 100px;"><a href="{{ $sortUrl('date_created') }}" class="text-decoration-none">Batch Date</a></th>
                <th><a href="{{ $sortUrl('document_number') }}" class="text-decoration-none">Documents</a></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr data-batch-id="{{ $row->batch_id }}">
                    <td class="text-nowrap">
                        @if(($pinEnabled ?? false) && (int) $row->batch_status !== 1)
                            @php
                                $firstDoc = null;
                                if (is_array($row->documents ?? null)) {
                                    $firstDoc = $row->documents[0] ?? null;
                                } elseif (($row->documents ?? null) instanceof \Illuminate\Support\Collection) {
                                    $firstDoc = ($row->documents ?? collect())->first();
                                }
                            @endphp
                            <button
                                type="button"
                                class="btn btn-sm btn-primary js-receive-batch-btn"
                                data-batch-id="{{ $row->batch_id }}"
                                aria-label="Receive batch {{ $row->batch_id }}"
                            >
                                Receive
                            </button>
                        @else
                            <span class="fw-semibold">{{ $row->batch_id }}</span>
                        @endif
                    </td>
                    <td class="text-nowrap">{{ $row->batch_name }}</td>
                    <td class="text-nowrap">
                        @if((int) $row->batch_status === 1)
                            <span class="badge bg-success">Received by batch</span>
                        @else
                            <span class="badge bg-secondary">Not received</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if($row->batch_date_created)
                            {{ \Carbon\Carbon::parse($row->batch_date_created)->format('F j, Y g:i A') }}
                        @endif
                    </td>
                    <td>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <div class="small text-muted">
                                {{ (int) ($row->documents_count ?? 0) }} document{{ (int) ($row->documents_count ?? 0) === 1 ? '' : 's' }}
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 160px;">Document No.</th>
                                        <th style="width: 220px;">DRN</th>
                                        <th style="width: 160px;">Type</th>
                                        <th style="width: 130px;">Transaction Type</th>
                                        <th>Subject</th>
                                        <th style="width: 220px;">Origin Office</th>
                                        <th style="width: 160px;">Doc Status</th>
                                        <th style="width: 190px;">Forwarded</th>
                                        <th style="width: 190px;">Received</th>
                                        <th style="width: 200px;">Received By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($row->documents ?? []) as $doc)
                                        <tr>
                                            <td class="text-nowrap fw-semibold">{{ $doc->document_number ?? '-' }}</td>
                                            <td class="text-nowrap">{{ $doc->drn ?? '-' }}</td>
                                            <td class="text-nowrap">{{ $doc->type ?? '-' }}</td>
                                            <td class="text-nowrap">
                                                @php
                                                    $txId = (int) ($doc->transaction_type ?? 0);
                                                    $txLabel = $txId === 2 ? 'OUTGOING' : ($txId === 1 ? 'INCOMING' : '-');
                                                @endphp
                                                {{ $txLabel }}
                                            </td>
                                            <td>{{ $doc->subject ?? '-' }}</td>
                                            <td>{{ $doc->origin_office ?? '-' }}</td>
                                            <td class="text-nowrap">{{ $doc->document_status ?? '-' }}</td>
                                            <td class="text-nowrap">
                                                @if($doc->date_forwarded)
                                                    {{ \Carbon\Carbon::parse($doc->date_forwarded)->format('F j, Y g:i A') }}
                                                @endif
                                            </td>
                                            <td class="text-nowrap">
                                                @if($doc->date_received)
                                                    {{ \Carbon\Carbon::parse($doc->date_received)->format('F j, Y g:i A') }}
                                                @endif
                                            </td>
                                            <td class="text-nowrap">{{ $doc->received_by_name ? mb_strtoupper((string) $doc->received_by_name, 'UTF-8') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No documents found for this batch.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No batch records found.</td>
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
