<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">No</th>
                <th class="text-center" style="width: 40px;"><input type="checkbox" style="width: 25px;height: 25px;" class="form-check-input form-check-lg" id="select-all"></th>
                <th class="text-nowrap">Receiver</th>
                <th class="text-nowrap">Item (Summary)</th>
                <th class="text-nowrap">Category</th>
                <th class="text-nowrap">Total Units</th>
                <th class="text-nowrap">Date Released</th>
                <th class="text-nowrap">Remarks</th>
                <th class="text-nowrap">Purpose</th>
                <th class="text-nowrap text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($issuances as $issuance)
            <tr>
                <td>{{ ($issuances->currentPage() - 1) * $issuances->perPage() + $loop->iteration }}</td>
                <td class="text-center">
                    @if(!$issuance->issuance_group_id)
                    <input type="checkbox" style="width: 25px;height: 25px;" class="form-check-input form-check-lg issuance-checkbox" value="{{ $issuance->id }}">
                    @endif
                </td>
                <td>{{ $issuance->receiver_name ?? 'N/A' }}</td>
                <td>
                    @if($issuance->itemUnits->isNotEmpty())
                        {{ $issuance->itemUnits->first()->item->item_name }}
                        @if($issuance->itemUnits->unique('item_id')->count() > 1)
                            <small class="text-muted">(+ others)</small>
                        @endif
                    @else
                        <span class="text-muted">No Items</span>
                    @endif
                </td>
                <td>
                    @if($issuance->itemUnits->isNotEmpty() && $issuance->itemUnits->first()->item->category)
                        {{ $issuance->itemUnits->first()->item->category->category_name }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    <span class="badge bg-success">{{ $issuance->itemUnits->count() }}</span>
                    @if($issuance->itemUnits->isNotEmpty() && $issuance->itemUnits->first()->item->unit)
                        <small class="text-muted ms-1">{{ Str::plural($issuance->itemUnits->first()->item->unit->unit_name, $issuance->itemUnits->count()) }}</small>
                    @endif
                </td>
                <td>{{ optional($issuance->date_issued)->format('Y-m-d h:i A') ?? '-' }}</td>
                <td>{{ Str::limit($issuance->remarks, 30) ?: '-' }}</td>
                <td>{{ Str::limit($issuance->issuanceGroup->purpose ?? '-', 30) }}</td>
                <td style="align-items: center !important;">
                    <button type="button" class="btn btn-sm btn-info text-white view-details" data-id="{{ $issuance->id }}">
                        <i class="bi bi-eye me-1"></i>View
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
                <td colspan="10" class="text-center">No Issuances Found</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="5" class="text-end fw-bold">Overall Total Units (Filtered):</td>
                <td class="fw-bold"><span class="badge bg-primary fs-6">{{ $overallTotalUnits ?? 0 }}</span></td>
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
