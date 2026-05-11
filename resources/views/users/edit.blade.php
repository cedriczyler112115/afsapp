@extends('layouts.app')

@section('title', '4Ps AFS-IS - Edit User')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Edit User</span>
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('users.update', $user->id) }}" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Fullname</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">User Level</label>
                    <select name="level_id" class="form-select @error('level_id') is-invalid @enderror" required>
                        <option value="">Select</option>
                        @foreach($userLevels as $lvl)
                            <option value="{{ $lvl->id }}" {{ (string)old('level_id', $user->level_id) === (string)$lvl->id ? 'selected' : '' }}>{{ $lvl->level_name }}</option>
                        @endforeach
                    </select>
                    @error('level_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Division</label>
                    <select id="division_id" name="division_id" class="form-select @error('division_id') is-invalid @enderror" required>
                        <option value="">Select</option>
                        @foreach($divisions as $div)
                            <option value="{{ $div->id }}" {{ (string)old('division_id', $user->division_id) === (string)$div->id ? 'selected' : '' }}>{{ $div->division_name }}</option>
                        @endforeach
                    </select>
                    @error('division_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Section</label>
                    <select id="section_id" name="section_id" class="form-select @error('section_id') is-invalid @enderror" required>
                        <option value="">Select</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}" data-division="{{ $sec->division_id }}" {{ (string)old('section_id', $user->section_id) === (string)$sec->id ? 'selected' : '' }}>{{ $sec->section_name }}</option>
                        @endforeach
                    </select>
                    @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Province</label>
                    <select id="province" name="province" class="form-select @error('province') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach($provinces as $prov)
                            <option value="{{ $prov->prov_code }}" {{ (string)old('province', $user->province) === (string)$prov->prov_code ? 'selected' : '' }}>{{ $prov->prov_name }}</option>
                        @endforeach
                    </select>
                    @error('province')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Municipality</label>
                    <select id="municipality" name="municipality" class="form-select @error('municipality') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach($cities as $c)
                            <option value="{{ $c->city_code }}" data-province="{{ $c->prov_code }}" {{ (string)old('municipality', $user->municipality) === (string)$c->city_code ? 'selected' : '' }}>{{ $c->city_name }}</option>
                        @endforeach
                    </select>
                    @error('municipality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cluster</label>
                    <select name="cluster" class="form-select @error('cluster') is-invalid @enderror">
                        <option value="">Select</option>
                        <option value="1" {{ (string)old('cluster', $user->cluster) === '1' ? 'selected' : '' }}>1</option>
                        <option value="2" {{ (string)old('cluster', $user->cluster) === '2' ? 'selected' : '' }}>2</option>
                    </select>
                    @error('cluster')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Group</label>
                    <select name="group_id" class="form-select @error('group_id') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}" {{ (string)old('group_id', $user->group_id ?? '') === (string)$g->id ? 'selected' : '' }}>{{ $g->group_name }}</option>
                        @endforeach
                    </select>
                    @error('group_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="is_status" class="form-select @error('is_status') is-invalid @enderror">
                        <option value="1" {{ (string)old('is_status', $user->is_status ?? '1') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ (string)old('is_status', $user->is_status ?? '1') === '0' ? 'selected' : '' }}>Pending</option>
                    </select>
                    @error('is_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        function filterSections() {
            const divisionId = $('#division_id').val();
            const $section = $('#section_id');
            const current = $section.val();
            $section.find('option').each(function () {
                const $opt = $(this);
                const optDivision = $opt.data('division');
                if (!$opt.val()) return;
                if (!divisionId) {
                    $opt.prop('hidden', false);
                    return;
                }
                $opt.prop('hidden', String(optDivision) !== String(divisionId));
            });
            if (divisionId) {
                const selectedOpt = $section.find('option:selected');
                if (selectedOpt.length && selectedOpt.prop('hidden')) {
                    $section.val('');
                } else {
                    $section.val(current);
                }
            }
        }

        function filterCities() {
            const provCode = $('#province').val();
            const $city = $('#municipality');
            const current = $city.val();
            $city.find('option').each(function () {
                const $opt = $(this);
                const optProv = $opt.data('province');
                if (!$opt.val()) return;
                if (!provCode) {
                    $opt.prop('hidden', false);
                    return;
                }
                $opt.prop('hidden', String(optProv) !== String(provCode));
            });
            if (provCode) {
                const selectedOpt = $city.find('option:selected');
                if (selectedOpt.length && selectedOpt.prop('hidden')) {
                    $city.val('');
                } else {
                    $city.val(current);
                }
            }
        }

        $('#division_id').on('change', filterSections);
        $('#province').on('change', filterCities);
        filterSections();
        filterCities();
    });
</script>
@endpush
