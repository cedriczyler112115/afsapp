@extends('layouts.app')

@section('title', 'Process Return')

@section('content')
<div class="container-fluid px-4">
    <h1 class="h3 mb-4 text-gray-800">Process Return</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('returns.store') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label">Borrowing Reference (Optional)</label>
                    <select name="borrowing_id" id="borrowing_id" class="form-select @error('borrowing_id') is-invalid @enderror" onchange="autoFillBorrowing()">
                        <option value="">Select Borrowing</option>
                        @foreach($activeBorrowings as $borrowing)
                            <option value="{{ $borrowing->id }}" 
                                data-item-id="{{ $borrowing->item_id }}" 
                                data-quantity="{{ $borrowing->quantity }}"
                                data-unit-id="{{ $borrowing->item_unit_id ?? '' }}"
                                data-unit-serial="{{ $borrowing->itemUnit->serial ?? '' }}"
                                data-unit-code="{{ $borrowing->itemUnit->full_code ?? '' }}"
                                data-unit-qr="{{ $borrowing->itemUnit->qr_code ?? '' }}"
                                {{ (old('borrowing_id') == $borrowing->id || request('borrowing_id') == $borrowing->id) ? 'selected' : '' }}>
                                #{{ $borrowing->id }} - {{ $borrowing->item->item_name }} ({{ $borrowing->borrower->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('borrowing_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Unit Information Display -->
                <div id="unit-info-card" class="card mb-3 border-start border-3 d-none">
                    <div class="card-body py-2">
                        <h6 class="fw-bold mb-2"><i class="bi bi-upc-scan me-2"></i>Item Unit Details</h6>
                        <div class="row g-2 text-sm">
                            <div class="col-md-4">
                                <span class="text-muted d-block small">Serial Number</span>
                                <span class="fw-bold text-dark" id="display-serial">-</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block small">Full Code</span>
                                <span class="fw-bold text-dark" id="display-code">-</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block small">QR Code</span>
                                <span class="fw-bold text-dark" id="display-qr">-</span>
                            </div>
                        </div>
                        <input type="hidden" name="item_unit_id" id="item_unit_id">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Item</label>
                        <select name="item_id" id="item_id" class="form-select @error('item_id') is-invalid @enderror" required>
                            <option value="">Select Item</option>
                            @foreach($items as $item)
                                <option value="{{ $item->item_id }}" {{ old('item_id') == $item->item_id ? 'selected' : '' }}>{{ $item->item_name }}</option>
                            @endforeach
                        </select>
                        @error('item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 1) }}" min="1" required>
                        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Return Date</label>
                        <input type="datetime-local" name="return_date" class="form-control @error('return_date') is-invalid @enderror" value="{{ old('return_date', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('return_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Condition / Category</label>
                        <select name="return_category" class="form-select @error('return_category') is-invalid @enderror" required>
                            <option value="GOOD" {{ old('return_category') == 'GOOD' ? 'selected' : '' }}>Good / Usable</option>
                            <option value="DAMAGED" {{ old('return_category') == 'DAMAGED' ? 'selected' : '' }}>Damaged</option>
                            <option value="LOST" {{ old('return_category') == 'LOST' ? 'selected' : '' }}>Lost</option>
                        </select>
                        @error('return_category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="3">{{ old('remarks') }}</textarea>
                    @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Submit Return</button>
                <a href="{{ route('borrowings.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
    function autoFillBorrowing() {
        const select = document.getElementById('borrowing_id');
        const selectedOption = select.options[select.selectedIndex];
        
        const itemSelect = document.getElementById('item_id');
        const quantityInput = document.getElementById('quantity');
        
        // Unit Display Elements
        const unitInfoCard = document.getElementById('unit-info-card');
        const displaySerial = document.getElementById('display-serial');
        const displayCode = document.getElementById('display-code');
        const displayQr = document.getElementById('display-qr');
        const unitIdInput = document.getElementById('item_unit_id');
        
        if (selectedOption.value) {
            const itemId = selectedOption.getAttribute('data-item-id');
            const quantity = selectedOption.getAttribute('data-quantity');
            
            // Get unit info
            const unitId = selectedOption.getAttribute('data-unit-id');
            const unitSerial = selectedOption.getAttribute('data-unit-serial');
            const unitCode = selectedOption.getAttribute('data-unit-code');
            const unitQr = selectedOption.getAttribute('data-unit-qr');
            
            // Set values
            itemSelect.value = itemId;
            quantityInput.value = quantity;
            
            // Display Unit Info if available
            if (unitId) {
                unitIdInput.value = unitId;
                displaySerial.textContent = unitSerial || '-';
                displayCode.textContent = unitCode || '-';
                displayQr.textContent = unitQr || '-';
                unitInfoCard.classList.remove('d-none');
            } else {
                unitIdInput.value = '';
                unitInfoCard.classList.add('d-none');
            }
            
            // Lock fields
            // For select: disable interactions and style as readonly
            itemSelect.style.pointerEvents = 'none';
            itemSelect.style.backgroundColor = '#e9ecef'; // Bootstrap readonly color
            // Hide other options (optional, but good for "Only display...")
            Array.from(itemSelect.options).forEach(opt => {
                if (opt.value != itemId) {
                    opt.style.display = 'none';
                }
            });
            quantityInput.setAttribute('readonly', true);
            
        } else {
            // Unlock fields
            itemSelect.value = "";
            quantityInput.value = 1;
            
            unitIdInput.value = '';
            unitInfoCard.classList.add('d-none');
            
            itemSelect.style.pointerEvents = 'auto';
            itemSelect.style.backgroundColor = '';
            Array.from(itemSelect.options).forEach(opt => {
                opt.style.display = '';
            });
            quantityInput.removeAttribute('readonly');
        }
    }
    
    // Run on load if pre-selected
    document.addEventListener('DOMContentLoaded', function() {
        if(document.getElementById('borrowing_id').value) {
            autoFillBorrowing();
        }
    });
</script>
@endsection
