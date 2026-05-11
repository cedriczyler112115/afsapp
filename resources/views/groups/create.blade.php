@extends('layouts.app')

@section('title', 'Library - Create Group')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Create Group</span>
        <a href="{{ route('groups.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        @if($errors->has('database'))
            <div class="alert alert-danger mb-3">{{ $errors->first('database') }}</div>
        @endif

        <form method="POST" action="{{ route('groups.store') }}" autocomplete="off">
            @csrf

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Group Name</label>
                    <input type="text" name="group_name" value="{{ old('group_name') }}" maxlength="50" class="form-control @error('group_name') is-invalid @enderror" required>
                    @error('group_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="1" {{ (string)old('status', '1') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ (string)old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

