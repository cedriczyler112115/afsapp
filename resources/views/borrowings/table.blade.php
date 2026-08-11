<div class="table-responsive">
    <table class="table table-bordered table-hover" width="100%" cellspacing="0" style="zoom: 80%">
        <thead>
            <tr>
                <th align="center" valign="middle">No.</th>
                <th align="center" valign="middle">Request No.</th>
                <th align="center" valign="middle">Borrower</th>
                <th align="center" valign="middle">Item Description</th>
                <th align="center" valign="middle">Serial / Code / Qr Code</th>
                <th align="center" valign="middle">Qty</th>
                <th align="center" valign="middle">Borrow By</th>
                <th align="center" valign="middle">Borrow Date</th>
                <th align="center" valign="middle">Released by</th>
                <th align="center" valign="middle">Received Date</th>
                <th align="center" valign="middle">Expected Return</th>
                <th align="center" valign="middle">Return Date</th>
                <th align="center" valign="middle">Days Overdue<br><font size=1>(Working days)</font></th>
                <th align="center" valign="middle">Return Condition</th>
                <th align="center" valign="middle">Received by</th>
                <th align="center" valign="middle">Status</th>
                <th align="center" valign="middle">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($borrowings as $borrowing)
                <tr @if($borrowing->status === 'REQUESTED') style="--bs-table-bg:#fff1f2" @endif>
                    <td>{{ ($borrowings->currentPage() - 1) * $borrowings->perPage() + $loop->iteration }}</td>
                    <td class="text-nowrap">{{ $borrowing->request_no ?? '-' }}</td>
                    <td>{{ $borrowing->borrower->name }}</td>
                    <td>{{ $borrowing->item->item_name }}</td>
                    <td>
                        @if($borrowing->itemUnit)
                            <table class="table-borderless table-sm m-0">
                                @if($borrowing->itemUnit->serial)
                                    <tr>
                                        <td class="text-end text-muted small p-0 pe-2">Serial:</td>
                                        <td class="p-0">{{ $borrowing->itemUnit->serial }}</td>
                                    </tr>
                                @endif
                                @if($borrowing->itemUnit->full_code)
                                    <tr>
                                        <td class="text-end text-muted small p-0 pe-2">Code:</td>
                                        <td class="p-0">{{ $borrowing->itemUnit->full_code }}</td>
                                    </tr>
                                @endif
                                @if($borrowing->itemUnit->qr_code)
                                    <tr>
                                        <td class="text-end text-muted small p-0 pe-2">QR:</td>
                                        <td class="p-0">{{ $borrowing->itemUnit->qr_code }}</td>
                                    </tr>
                                @endif
                            </table>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ number_format($borrowing->quantity) }} {{ $borrowing->borrow_mode === 'PCS' ? 'PC/S' : 'unit' }}</td>
                    <td>{{ $borrowing->borrow_mode === 'PCS' ? 'PC/S' : 'Item Unit' }}</td>
                    <td>{{ $borrowing->borrow_date->format('Y-m-d') }}</td>
                    <td>{{ $borrowing->issuedBy->name ?? 'N/A' }}</td>
                    <td>{{ $borrowing->received_at?->format('Y-m-d h:i A') ?? '-' }}</td>
                    <td>
                        {{ $borrowing->expected_return_date->format('Y-m-d') }}
                        @if($borrowing->status == 'BORROWED' && $borrowing->expected_return_date < now())
                            <span class="badge bg-danger">Overdue</span>
                        @endif
                    </td>
                    <td>
                        @if($borrowing->returns->isNotEmpty())
                            {{ $borrowing->returns->sortByDesc('created_at')->first()->return_date->format('Y-m-d') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @php
                            $lapsedDays = 0;
                            $isOverdue = false;
                            if ($borrowing->status == 'RETURNED') {
                                $lastReturn = $borrowing->returns->sortByDesc('created_at')->first();
                                if ($lastReturn && $lastReturn->return_date > $borrowing->expected_return_date) {
                                    $lapsedDays = $borrowing->expected_return_date->diffInWeekdays($lastReturn->return_date);
                                    $isOverdue = true;
                                }
                            } elseif ($borrowing->status == 'BORROWED' || $borrowing->status == 'OVERDUE') {
                                if (now() > $borrowing->expected_return_date) {
                                    $lapsedDays = $borrowing->expected_return_date->diffInWeekdays(now());
                                    $isOverdue = true;
                                }
                            }
                        @endphp
                        @if($isOverdue && $lapsedDays > 0)
                            <span class="text-danger fw-bold">{{ (int)$lapsedDays }} {{ (int)$lapsedDays == 1 ? 'day' : 'days' }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($borrowing->returns->isNotEmpty())
                            {{ $borrowing->returns->sortByDesc('created_at')->first()->return_category }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($borrowing->returns->isNotEmpty())
                            {{ $borrowing->returns->sortByDesc('created_at')->first()->receivedBy->name ?? 'N/A' }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($borrowing->status == 'REQUESTED')
                            <span class="badge bg-warning">Requested</span>
                        @elseif($borrowing->status == 'RELEASED')
                            <span class="badge bg-info">Awaiting Receipt</span>
                        @elseif($borrowing->status == 'BORROWED')
                            <span class="badge bg-primary">Borrowed</span>
                        @elseif($borrowing->status == 'RETURN_PENDING')
                            <span class="badge bg-warning">Return Pending</span>
                        @elseif($borrowing->status == 'RETURNED')
                            <span class="badge bg-success">Returned</span>
                        @elseif($borrowing->status == 'OVERDUE')
                            <span class="badge bg-danger">Overdue</span>
                        @elseif($borrowing->status == 'CANCELLED')
                            <span class="badge bg-secondary">Cancelled</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1 align-items-center flex-wrap">
                        @if($borrowing->status === 'REQUESTED' && $canProcess)
                            <form method="POST" action="{{ route('borrowings.approve', $borrowing) }}">@csrf<button class="btn btn-primary btn-sm" title="Approve and allocate stock"><i class="bi bi-check-circle"></i></button></form>
                        @endif
                        @if($borrowing->status === 'REQUESTED' && ($borrowing->borrower_id === auth()->id() || $canProcess))
                            <form method="POST" action="{{ route('borrowings.cancel', $borrowing) }}">@csrf<button class="btn btn-outline-danger btn-sm" title="Cancel"><i class="bi bi-x-circle"></i></button></form>
                        @endif
                        @if($borrowing->status === 'RELEASED' && $borrowing->borrower_id === auth()->id())
                            <form method="POST" action="{{ route('borrowings.receive', $borrowing) }}">@csrf<button class="btn btn-success btn-sm text-nowrap"><i class="bi bi-box-arrow-in-down me-1"></i>Receive</button></form>
                        @endif
                        @if(in_array($borrowing->status, ['BORROWED','OVERDUE']) && $borrowing->borrower_id === auth()->id())
                            <form method="POST" action="{{ route('borrowings.request-return', $borrowing) }}">@csrf<button class="btn btn-warning btn-sm text-nowrap"><i class="bi bi-arrow-return-left me-1"></i>Return</button></form>
                        @endif
                        @if($borrowing->status === 'RETURN_PENDING' && $canProcess)
                            <form method="POST" action="{{ route('borrowings.confirm-return', $borrowing) }}" class="d-flex gap-1">@csrf
                                <select name="return_category" class="form-select form-select-sm" style="width:95px"><option value="GOOD">Good</option><option value="DAMAGED">Damaged</option><option value="LOST">Lost</option></select>
                                <button class="btn btn-success btn-sm text-nowrap" title="Confirm returned units"><i class="bi bi-check2-circle"></i></button>
                            </form>
                        @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="text-center">No borrowings found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@php
    $from = $borrowings->firstItem() ?? 0;
    $to = $borrowings->lastItem() ?? 0;
    $total = $borrowings->total() ?? 0;
@endphp
<div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
    <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
    <div>{{ $borrowings->links() }}</div>
</div>
