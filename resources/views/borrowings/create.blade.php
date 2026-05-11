@extends('layouts.app')

@section('title', 'New Borrowing')

@section('content')
<div class="px-0 px-md-4">
    <h1 class="h3 mb-4 text-gray-800">New Borrowing</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('borrowings.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Borrower</label>
                        <select name="borrower_id" class="form-select @error('borrower_id') is-invalid @enderror" required>
                            <option value="">Select User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('borrower_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('borrower_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Item</label>
                        <select name="item_id" class="form-select @error('item_id') is-invalid @enderror" required>
                            <option value="">Select Item</option>
                            @foreach($items as $item)
                                <option value="{{ $item->item_id }}" {{ old('item_id') == $item->item_id ? 'selected' : '' }}>
                                    {{ $item->item_name }} (Stock: {{ $item->current_quantity }})
                                </option>
                            @endforeach
                        </select>
                        @error('item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mb-4 d-none" id="units-section">
                    <label class="form-label fw-bold">Select Specific Units (Optional)</label>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 50px;">
                                        <input type="checkbox" id="select-all-units" class="form-check-input">
                                    </th>
                                    <th>Serial</th>
                                    <th>Full Code</th>
                                    <th>QR Code</th>
                                </tr>
                            </thead>
                            <tbody id="units-table-body">
                                <!-- Populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Select specific units to borrow. The quantity will automatically update.</small>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 1) }}" min="1" required>
                        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Borrow Date</label>
                        <input type="datetime-local" name="borrow_date" class="form-control @error('borrow_date') is-invalid @enderror" value="{{ old('borrow_date', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('borrow_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Expected Return Date</label>
                        <input type="datetime-local" name="expected_return_date" class="form-control @error('expected_return_date') is-invalid @enderror" value="{{ old('expected_return_date') }}" required>
                        @error('expected_return_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Purpose</label>
                    <textarea name="purpose" class="form-control @error('purpose') is-invalid @enderror" rows="3">{{ old('purpose') }}</textarea>
                    @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Item Units Table -->


                <button type="submit" class="btn btn-primary">Submit Borrowing</button>
                <a href="{{ route('borrowings.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const itemSelect = document.querySelector('select[name="item_id"]');
        const unitsSection = document.getElementById('units-section');
        const unitsTableBody = document.getElementById('units-table-body');
        const quantityInput = document.querySelector('input[name="quantity"]');
        const selectAllCheckbox = document.getElementById('select-all-units');

        // Function to update quantity based on selected checkboxes
        function updateQuantity() {
            const selectedCount = document.querySelectorAll('.unit-checkbox:checked').length;
            if (selectedCount > 0) {
                quantityInput.value = selectedCount;
                quantityInput.setAttribute('readonly', true);
            } else {
                quantityInput.removeAttribute('readonly');
                // Optional: reset to 1 if user unchecks all
                // quantityInput.value = 1; 
            }
        }

        // Handle Item Change
        itemSelect.addEventListener('change', function() {
            const itemId = this.value;
            
            // Reset table
            unitsSection.classList.add('d-none');
            unitsTableBody.innerHTML = '';
            quantityInput.removeAttribute('readonly');
            if (selectAllCheckbox) selectAllCheckbox.checked = false;

            if (!itemId) return;

            // Fetch units
            fetch(`{{ url('borrowings/units') }}/${itemId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        unitsSection.classList.remove('d-none');
                        data.forEach(unit => {
                            const row = `
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="item_unit_ids[]" value="${unit.id}" class="unit-checkbox form-check-input">
                                    </td>
                                    <td>${unit.serial || '-'}</td>
                                    <td>${unit.full_code || '-'}</td>
                                    <td>${unit.qr_code || '-'}</td>
                                </tr>
                            `;
                            unitsTableBody.insertAdjacentHTML('beforeend', row);
                        });
                        
                        // Attach listeners to new checkboxes
                        document.querySelectorAll('.unit-checkbox').forEach(cb => {
                            cb.addEventListener('change', updateQuantity);
                        });
                    }
                })
                .catch(error => console.error('Error fetching units:', error));
        });

        // Handle Select All
        if(selectAllCheckbox){
             selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.unit-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                updateQuantity();
            });
        }
    });
</script>
@endsection
