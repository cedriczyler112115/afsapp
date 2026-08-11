<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">No</th>
                <th class="text-center" style="width: 40px;"><input type="checkbox" class="form-check-input table-header-checkbox" id="select-all"></th>
                <th class="text-nowrap">SKU</th>
                <th class="text-nowrap">Item (Summary)</th>
                <th class="text-nowrap">Receiver</th>
                <th class="text-nowrap">Request No.</th>
                <th class="text-nowrap">Category</th>
                <th class="text-nowrap">Total Issued</th>
                <th class="text-nowrap">Date Released</th>
                <th class="text-nowrap">Remarks</th>
                <th class="text-nowrap">Purpose</th>
                <th class="text-nowrap text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @php
                $fullCodeGroups = [];

                foreach ($issuances as $issuance) {
                    $fullCodes = $issuance->stockTransactions
                        ->pluck('unit.full_code')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values();

                    $fullCodeGroups[] = $fullCodes->implode(' | ');
                }

                $fullCodeSpans = [];
                $count = count($fullCodeGroups);
                for ($i = 0; $i < $count; $i++) {
                    if ($i > 0 && $fullCodeGroups[$i] === $fullCodeGroups[$i - 1]) {
                        continue;
                    }

                    $span = 1;
                    for ($j = $i + 1; $j < $count; $j++) {
                        if ($fullCodeGroups[$j] !== $fullCodeGroups[$i]) {
                            break;
                        }
                        $span++;
                    }

                    $fullCodeSpans[$i] = $span;
                }
            @endphp
            @forelse ($issuances as $issuance)
            <tr>
                <td>{{ ($issuances->currentPage() - 1) * $issuances->perPage() + $loop->iteration }}</td>
                <td class="text-center">
                    @if(!$issuance->issuance_group_id)
                    <input type="checkbox" class="form-check-input issuance-checkbox table-data-checkbox" value="{{ $issuance->id }}">
                    @endif
                </td>
                @php
                    $currentFullCode = $issuance->primary_full_code ?? $issuance->stockTransactions->pluck('unit.full_code')->filter()->sort()->first();
                    $fullCodeKey = $loop->index;
                @endphp
                @if(isset($fullCodeSpans[$fullCodeKey]))
                    <td rowspan="{{ $fullCodeSpans[$fullCodeKey] }}" style="border-left: 1px solid var(--bs-border-color) !important; border-right: 1px solid var(--bs-border-color) !important; vertical-align: top;">
                        @php
                            $currentFullCodes = $issuance->stockTransactions
                                ->pluck('unit.full_code')
                                ->filter()
                                ->unique()
                                ->sort()
                                ->values();
                        @endphp
                        @if($currentFullCodes->isNotEmpty())
                            <div class="d-flex flex-column gap-1" style="max-width: 180px;">
                                @foreach($currentFullCodes as $fullCode)
                                    <span class="text-truncate" title="{{ $fullCode }}">{{ $fullCode }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                @endif
                <td>
                    @if($issuance->stockTransactions->isNotEmpty())
                        {{ $issuance->stockTransactions->first()->unit->item->item_name }}
                        @if($issuance->stockTransactions->unique('item_id')->count() > 1)
                            <small class="text-muted">(+ others)</small>
                        @endif
                    @else
                        <span class="text-muted">No Items</span>
                    @endif
                </td>
                <td>{{ $issuance->receiver_name ?? 'N/A' }}</td>
                <td class="text-nowrap">
                    @if($issuance->supplyRequestItem?->supplyRequest)
                        <a href="{{ route('supply-requests.show', $issuance->supplyRequestItem->supplyRequest) }}">{{ $issuance->supplyRequestItem->supplyRequest->request_no }}</a>
                        <div class="mt-1">
                            @if($issuance->received_at)
                                <span class="badge bg-success">Received</span>
                            @else
                                <span class="badge bg-warning">Awaiting Receipt</span>
                            @endif
                        </div>
                    @else
                        <span class="text-muted">Direct issuance</span>
                    @endif
                </td>
                <td>
                    @if($issuance->stockTransactions->isNotEmpty() && $issuance->stockTransactions->first()->unit->item->category)
                        {{ $issuance->stockTransactions->first()->unit->item->category->category_name }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    <span class="badge bg-success">{{ $issuance->stockTransactions->sum('quantity') }} PC/S</span>
                    <small class="text-muted ms-1">in {{ $issuance->stockTransactions->count() }} box(es)</small>
                </td>
                <td>{{ optional($issuance->date_issued)->format('Y-m-d h:i A') ?? '-' }}</td>
                <td>{{ Str::limit($issuance->remarks, 30) ?: '-' }}</td>
                <td>{{ Str::limit($issuance->issuanceGroup->purpose ?? '-', 30) }}</td>
                <td style="align-items: center !important;">
                    <button type="button" class="btn btn-sm btn-info text-white view-details" data-id="{{ $issuance->id }}" title="View" aria-label="View issuance details">
                        <i class="bi bi-eye"></i>
                    </button>
                    @if($issuance->issuance_group_id)
                    <a href="{{ route('stock-out.print', $issuance->issuance_group_id) }}" target="_blank" class="btn btn-sm btn-secondary text-white ms-1" title="Reprint Group">
                        <i class="bi bi-printer"></i>
                    </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="text-center">No Issuances Found</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="7" class="text-end fw-bold">Overall Total PC/S Issued (Filtered):</td>
                <td class="fw-bold"><span class="badge bg-primary fs-6">{{ number_format($overallTotalUnits ?? 0) }}</span></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>
</div>

@php
    $from = $issuances->firstItem() ?? 0;
    $to = $issuances->lastItem() ?? 0;
    $total = $issuances->total() ?? 0;
@endphp
<div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
    <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
    <div>{!! $issuances->links() !!}</div>
</div>
