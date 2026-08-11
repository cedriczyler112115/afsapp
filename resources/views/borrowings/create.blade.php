@extends('layouts.app')
@section('title', 'New Borrow Request')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><span class="fw-semibold">New Borrow Request</span><div class="small text-muted">Available stock units will be allocated after approval.</div></div>
        <a href="{{ route('borrowings.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form action="{{ route('borrowings.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Borrower <span class="text-danger">*</span></label>
                    <select name="borrower_id" class="form-select @error('borrower_id') is-invalid @enderror" required>
                        @foreach($users as $user)<option value="{{ $user->id }}" @selected((int) old('borrower_id', auth()->id()) === $user->id)>{{ $user->name }}</option>@endforeach
                    </select>
                    @error('borrower_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Available Item <span class="text-danger">*</span></label>
                    <select name="item_id" class="form-select @error('item_id') is-invalid @enderror" required>
                        <option value="">Select Item</option>
                        @foreach($items as $item)<option value="{{ $item->item_id }}" @selected((int) old('item_id') === $item->item_id)>{{ $item->item_name }} ({{ $item->available_units }} unit(s) / {{ $item->available_pieces }} PC/S)</option>@endforeach
                    </select>
                    @error('item_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Borrow By <span class="text-danger">*</span></label>
                    <select name="borrow_mode" class="form-select @error('borrow_mode') is-invalid @enderror" required>
                        <option value="UNIT" @selected(old('borrow_mode') === 'UNIT')>Item Unit</option>
                        <option value="PCS" @selected(old('borrow_mode') === 'PCS')>PC/S</option>
                    </select>
                    @error('borrow_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" min="1" step="1" value="{{ old('quantity', 1) }}" class="form-control @error('quantity') is-invalid @enderror" required>
                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Borrow Date <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="borrow_date" value="{{ old('borrow_date', now()->format('Y-m-d\TH:i')) }}" class="form-control @error('borrow_date') is-invalid @enderror" required>
                    @error('borrow_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expected Return <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="expected_return_date" value="{{ old('expected_return_date') }}" class="form-control @error('expected_return_date') is-invalid @enderror" required>
                    @error('expected_return_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Purpose <span class="text-danger">*</span></label>
                    <textarea name="purpose" rows="3" class="form-control @error('purpose') is-invalid @enderror" required>{{ old('purpose') }}</textarea>
                    @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3"><a href="{{ route('borrowings.index') }}" class="btn btn-secondary btn-sm">Cancel</a><button class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Submit Request</button></div>
        </form>
    </div>
</div>
@endsection
