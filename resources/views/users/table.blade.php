@php
    $sortBy = $sortBy ?? request('sort_by', 'id');
    $sortDir = $sortDir ?? request('sort_dir', 'desc');
    $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
    $canEdit = $canEdit ?? false;

    $sortIcon = function (string $key) use ($sortBy, $sortDir) {
        if ($sortBy !== $key) {
            return '';
        }
        return $sortDir === 'asc' ? ' ▲' : ' ▼';
    };
@endphp

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap"><a href="#" data-sort="id" class="text-decoration-none text-dark">Id{!! $sortIcon('id') !!}</a></th>
                <th class="text-nowrap"><a href="#" data-sort="name" class="text-decoration-none text-dark">Fullname{!! $sortIcon('name') !!}</a></th>
                <th class="text-nowrap"><a href="#" data-sort="email" class="text-decoration-none text-dark">Email Address{!! $sortIcon('email') !!}</a></th>
                <th class="text-nowrap"><a href="#" data-sort="level" class="text-decoration-none text-dark">User Level{!! $sortIcon('level') !!}</a></th>
                <th class="text-nowrap"><a href="#" data-sort="division" class="text-decoration-none text-dark">Division{!! $sortIcon('division') !!}</a></th>
                <th class="text-nowrap"><a href="#" data-sort="section" class="text-decoration-none text-dark">Section{!! $sortIcon('section') !!}</a></th>
                <th class="text-nowrap"><a href="#" data-sort="province" class="text-decoration-none text-dark">Province{!! $sortIcon('province') !!}</a></th>
                <th class="text-nowrap"><a href="#" data-sort="cluster" class="text-decoration-none text-dark">Cluster{!! $sortIcon('cluster') !!}</a></th>
                <th class="text-nowrap"><a href="#" data-sort="municipality" class="text-decoration-none text-dark">Municipality{!! $sortIcon('municipality') !!}</a></th>
                <th class="text-nowrap"><a href="#" data-sort="group" class="text-decoration-none text-dark">Group{!! $sortIcon('group') !!}</a></th>
                <th class="text-nowrap"><a href="#" data-sort="status" class="text-decoration-none text-dark">Status{!! $sortIcon('status') !!}</a></th>
                <th class="text-nowrap" width="120px">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->level_name ?? '' }}</td>
                    <td>{{ $user->division_name ?? '' }}</td>
                    <td>{{ $user->section_name ?? '' }}</td>
                    <td>{{ $user->province_name ?? ($user->province ?? '') }}</td>
                    <td>{{ $user->cluster ?? '' }}</td>
                    <td>{{ $user->municipality_name ?? ($user->municipality ?? '') }}</td>
                    <td>{{ $user->group_name ?? '' }}</td>
                    <td>
                        @php
                            $statusVal = property_exists($user, 'is_status') ? $user->is_status : null;
                        @endphp
                        @if($statusVal === null)
                            <span class="badge bg-secondary">N/A</span>
                        @else
                            <span class="badge {{ (int)$statusVal === 1 ? 'bg-success' : 'bg-warning text-dark' }}">{{ (int)$statusVal === 1 ? 'Active' : 'Pending' }}</span>
                        @endif
                    </td>
                    <td>
                        @if($canEdit)
                            <a class="btn btn-primary btn-sm" href="{{ route('users.edit', $user->id) }}"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                        @else
                            <button class="btn btn-secondary btn-sm" type="button" disabled><i class="bi bi-pencil-square me-1"></i>Edit</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center text-muted py-4">No users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@php
    $from = $users->firstItem() ?? 0;
    $to = $users->lastItem() ?? 0;
    $total = $users->total() ?? 0;
@endphp
<div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
    <div class="small text-muted">Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
    <div>{!! $users->links() !!}</div>
</div>
