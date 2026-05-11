@extends('layouts.app')

@section('title', 'Returns')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Returns</h1>
        <a href="{{ route('returns.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Process Return</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Return History</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Borrowing Ref</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Return Date</th>
                            <th>Category</th>
                            <th>Remarks</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                            <tr>
                                <td>{{ $return->id }}</td>
                                <td>
                                    @if($return->borrowing)
                                        <a href="{{ route('borrowings.index', ['search' => $return->borrowing->id]) }}">#{{ $return->borrowing->id }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $return->item->item_name }}</td>
                                <td>{{ $return->quantity }}</td>
                                <td>{{ $return->return_date->format('Y-m-d H:i') }}</td>
                                <td>{{ $return->return_category }}</td>
                                <td>{{ $return->remarks }}</td>
                                <td>{{ $return->receivedBy->name ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No returns found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @php
                $from = $returns->firstItem() ?? 0;
                $to = $returns->lastItem() ?? 0;
                $total = $returns->total() ?? 0;
            @endphp
            <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
                <div>{{ $returns->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
