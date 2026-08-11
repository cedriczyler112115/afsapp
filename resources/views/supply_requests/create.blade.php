@extends('layouts.app')

@section('title', 'New Supply Request')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Create Supplies Request</span>
        <a href="{{ route('supply-requests.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('supply-requests.store') }}" id="supplyRequestForm">
            @csrf
            @if($errors->any())
                <div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label">Purpose <span class="text-danger">*</span></label>
                    <textarea name="purpose" rows="2" class="form-control @error('purpose') is-invalid @enderror" required>{{ old('purpose') }}</textarea>
                    @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date Needed</label>
                    <input type="date" name="needed_at" min="{{ now()->toDateString() }}" value="{{ old('needed_at') }}" class="form-control @error('needed_at') is-invalid @enderror">
                    @error('needed_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div><h6 class="mb-0">Requested Items</h6><small class="text-muted">Only printed items with available stock are listed.</small></div>
                <button type="button" id="addItem" class="btn btn-outline-primary btn-sm" @disabled($items->isEmpty())><i class="bi bi-plus-lg me-1"></i>Add Item</button>
            </div>
            @if($items->isEmpty())
                <div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle me-1"></i>No printed inventory is currently available for request.</div>
            @endif
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle" id="requestItems">
                    <thead class="table-light"><tr><th style="min-width:300px">Available Item</th><th style="width:120px">Issue By</th><th style="width:140px">Available</th><th style="width:140px">Quantity</th><th>Notes</th><th style="width:50px"></th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <div id="itemError" class="text-danger small mb-3 d-none">Add at least one requested item.</div>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('supply-requests.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
                <button class="btn btn-primary btn-sm" @disabled($items->isEmpty())><i class="bi bi-send me-1"></i>Submit Request</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    let rowIndex = 0;
    const itemOptions = @json($itemOptions);
    const oldItems = @json(old('items', []));
    function addRow(existing = {}) {
        const index = rowIndex++;
        const options = itemOptions.map(item => `<option value="${item.id}" data-pieces="${item.pieces}" data-boxes="${item.boxes}" ${String(existing.item_id || '') === String(item.id) ? 'selected' : ''}>${$('<div>').text(`${item.text} - ${item.boxes} box(es) / ${item.pieces} PC/S`).html()}</option>`).join('');
        $('#requestItems tbody').append(`<tr>
            <td><select name="items[${index}][item_id]" class="form-select form-select-sm request-item" required><option value="">Select Item</option>${options}</select></td>
            <td><select name="items[${index}][issue_mode]" class="form-select form-select-sm issue-mode"><option value="PCS" ${existing.issue_mode !== 'BOX' ? 'selected' : ''}>PC/S</option><option value="BOX" ${existing.issue_mode === 'BOX' ? 'selected' : ''}>Box</option></select></td>
            <td class="text-center fw-semibold available-quantity">-</td>
            <td><input type="number" name="items[${index}][quantity]" value="${existing.quantity || ''}" min="1" step="1" class="form-control form-control-sm request-quantity" required><div class="invalid-feedback quantity-error"></div></td>
            <td><input name="items[${index}][notes]" value="${$('<div>').text(existing.notes || '').html()}" maxlength="500" class="form-control form-control-sm" placeholder="Optional"></td>
            <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bi bi-trash"></i></button></td>
        </tr>`);
        $('#requestItems tbody tr:last .request-item').trigger('change');
        $('#itemError').addClass('d-none');
    }
    $('#addItem').on('click', addRow);
    $(document).on('change', '.request-item, .issue-mode', function () {
        const row = $(this).closest('tr');
        const option = row.find('.request-item :selected');
        const mode = row.find('.issue-mode').val();
        const available = parseInt(mode === 'BOX' ? option.data('boxes') : option.data('pieces')) || 0;
        const hasItem = Boolean(row.find('.request-item').val());
        row.find('.available-quantity').text(hasItem ? `${available.toLocaleString()} ${mode === 'BOX' ? 'box(es)' : 'PC/S'}` : '-');
        row.find('.request-quantity').attr('max', hasItem ? available : '').trigger('input');
    });
    $(document).on('input', '.request-quantity', function () {
        const input = $(this);
        const maximum = parseInt(input.attr('max')) || 0;
        const value = parseInt(input.val()) || 0;
        const hasItem = Boolean(input.closest('tr').find('.request-item').val());
        const invalid = value > 0 && hasItem && (maximum < 1 || value > maximum);
        input.toggleClass('is-invalid', invalid);
        const mode = input.closest('tr').find('.issue-mode').val();
        input.siblings('.quantity-error').text(invalid ? `Maximum available quantity is ${maximum.toLocaleString()} ${mode === 'BOX' ? 'box(es)' : 'PC/S'}.` : '');
    });
    $(document).on('click', '.remove-item', function () { $(this).closest('tr').remove(); });
    $('#supplyRequestForm').on('submit', function (event) {
        if (!$('#requestItems tbody tr').length) { event.preventDefault(); $('#itemError').removeClass('d-none'); }
        const values = $('.request-item').map((_, el) => el.value).get().filter(Boolean);
        if (new Set(values).size !== values.length) { event.preventDefault(); $('#itemError').text('The same item cannot be requested twice.').removeClass('d-none'); }
        if ($('.request-quantity.is-invalid').length) { event.preventDefault(); $('#itemError').text('Correct quantities that exceed available inventory.').removeClass('d-none'); }
    });
    if (oldItems.length) oldItems.forEach(addRow); else if (itemOptions.length) addRow();
});
</script>
@endpush
