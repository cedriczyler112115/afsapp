<div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 140px;">Transaction&nbsp;Type</th>
                <th style="width: 180px;">Created by</th>
                <th style="width: 140px;">Reference No.</th>
                <th style="width: 110px;">Date&nbsp;Received</th>
                <th style="width: 230px;">DRN/FETS/ICS/DV/PAR&nbsp;NO.</th>
                <th style="width: 280px;">From</th>
                
                <th style="width: 250px;">Type</th>
                <th>Subject</th>
                <th style="width: 110px;">Status</th>
                <th style="width: 150px;">Forwarded To</th>
                <th style="width: 120px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $doc)
                <tr>
                    @php
                        $txId = (int) ($doc->transaction_type ?? 0);
                        $txLabel = $txId === 2 ? 'OUTGOING' : ($txId === 1 ? 'INCOMING' : '-');
                    @endphp
                    <td class="text-nowrap">{{ $txLabel }}</td>
                    <td class="text-nowrap">{{ optional($doc->createdByUser)->name ?? '-' }}</td>
                    <td class="text-nowrap fw-semibold">{{ $doc->document_reference_number }}</td>
                    <td class="text-nowrap">{{ $doc->date_received ? $doc->date_received->format('j M Y g:i A') : '' }}</td>
                    <td class="text-nowrap">{{ $doc->drn }}</td>
                    <td>
                        <div class="small text-uppercase text-secondary">{{ $doc->document_from_type }} : <b>{{ optional($doc->source)->name }}</b></div>

                    </td>
                    <td>{{ optional($doc->type)->name }}</td>
                    <td>{{ $doc->subject }}</td>
                    <td class="text-nowrap">
                        @php
                            $totalRecipients = (int) ($doc->total_recipients_count ?? 0);
                            $receivedRecipients = (int) ($doc->received_recipients_count ?? 0);

                            $badge = 'secondary';
                            if ($doc->current_status === 'RECEIVED') $badge = 'success';
                            if ($doc->current_status === 'FORWARDED') $badge = 'primary';
                            if ($doc->current_status === 'ARCHIVED') $badge = 'dark';

                            $statusLabel = (string) $doc->current_status;
                            if ($totalRecipients > 0) {
                                $plural = strtoupper(\Illuminate\Support\Str::plural('recipient', $totalRecipients));
                                $statusLabel = $receivedRecipients . '/' . $totalRecipients . ' ' . $plural . ' RECEIVED';

                                if ($receivedRecipients >= $totalRecipients) $badge = 'success';
                                if ($receivedRecipients < $totalRecipients) $badge = 'warning';
                            }

                            $recipientNames = collect($doc->forwardedRecipients ?? [])
                                ->map(fn ($r) => $r && $r->user ? mb_strtoupper((string) $r->user->name, 'UTF-8') : null)
                                ->filter()
                                ->values();
                            $popoverHtml = $recipientNames->map(fn ($n) => e($n))->implode('<br>');
                        @endphp
                        <span
                            class="badge bg-{{ $badge }}"
                            @if($popoverHtml !== '')
                                data-bs-toggle="popover"
                                data-bs-trigger="hover focus"
                                data-bs-placement="top"
                                data-bs-title="Recipients"
                                data-bs-html="true"
                                data-bs-content="{!! $popoverHtml !!}"
                            @endif
                        >{{ $statusLabel }}</span>
                    </td>
                    <td class="small">
                        @php
                            $totalRecipients = isset($totalRecipients) ? $totalRecipients : (int) ($doc->total_recipients_count ?? 0);
                            $recipientLabel = $totalRecipients . ' ' . strtoupper(\Illuminate\Support\Str::plural('recipient', $totalRecipients));
                        @endphp
                        @if($doc->date_forwarded || $doc->current_status === 'FORWARDED' || $totalRecipients > 0)
                            <span
                                class="badge bg-primary text-nowrap" 
                                @if($popoverHtml !== '')
                                    data-bs-toggle="popover"
                                    data-bs-trigger="hover focus"
                                    data-bs-placement="top"
                                    data-bs-title="Recipients"
                                    data-bs-html="true"
                                    data-bs-content="{!! $popoverHtml !!}"
                                @endif
                            >{{ $recipientLabel }}</span>
                        @else
                            <div class="text-secondary">-</div>
                        @endif

                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('incoming-documents.show', ['incoming_document' => $doc->id, 'from' => 'incoming']) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        @php
                            $userId = (int) auth()->id();
                            $canEdit = (int) $doc->received_by === $userId
                                || (int) $doc->forwarded_to_user_id === $userId
                                || (\Illuminate\Support\Facades\Schema::hasColumn('incoming_documents', 'created_by') && (int) $doc->created_by === $userId);
                        @endphp
                        @if($canEdit)
                            <a href="{{ route('incoming-documents.edit', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center text-secondary py-4">No incoming documents found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@php
    $from = $documents->firstItem() ?? 0;
    $to = $documents->lastItem() ?? 0;
    $total = $documents->total() ?? 0;
@endphp
<div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
    <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
    <div>{!! $documents->links() !!}</div>
</div>
