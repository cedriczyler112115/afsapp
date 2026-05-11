<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">No</th>
                <th class="text-nowrap">Type</th>
                <th class="text-nowrap">Name</th>
                <th class="text-nowrap">Status</th>
                <th class="text-nowrap" width="220px">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sources as $source)
                <tr data-source-id="{{ $source->id }}">
                    <td>{{ ($sources->currentPage() - 1) * $sources->perPage() + $loop->iteration }}</td>
                    <td class="text-uppercase">{{ $source->source_type }}</td>
                    <td>{{ $source->name }}</td>
                    <td><span class="badge {{ (int)$source->is_active === 1 ? 'bg-success' : 'bg-secondary' }}">{{ (int)$source->is_active === 1 ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <a class="btn btn-primary me-2 btn-sm edit-source" href="javascript:void(0)" data-id="{{ $source->id }}" aria-label="Edit document source"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i></a>
                        <button type="button" class="btn btn-danger btn-sm delete-source" data-id="{{ $source->id }}" aria-label="Delete document source"><i class="bi bi-trash me-1" aria-hidden="true"></i></button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-secondary py-4">
                        <span class="d-inline-flex align-items-center flex-wrap gap-2 justify-content-center">
                            <span class="text-secondary">No result found</span>
                            <button
                                type="button"
                                class="btn btn-sm btn-link p-0 js-add-new-document-source"
                                data-prefill="{{ trim((string) request('search', '')) }}"
                                aria-label="Add new document source"
                            >
                                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add New
                            </button>
                        </span>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@php
    $from = $sources->firstItem() ?? 0;
    $to = $sources->lastItem() ?? 0;
    $total = $sources->total() ?? 0;
@endphp
<div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
    <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
    <div>{!! $sources->links() !!}</div>
</div>
