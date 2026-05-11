@extends('layouts.app')

@section('title', 'Create Unit of Measure')

@section('content')
<div class="card">
    <div class="card-header">
        Create New Unit of Measure
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Whoops!</strong> There were some problems with your input.<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
           
        <form action="{{ route('unit_of_measures.store') }}" method="POST">
            @csrf
          
             <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 mb-3">
                    <div class="form-group">
                        <strong>Name:</strong>
                        <input type="text" name="unit_name" class="form-control" placeholder="Name">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12 mb-3">
                    <div class="form-group">
                        <strong>Code:</strong>
                        <input type="text" name="unit_code" class="form-control" placeholder="Code (max 10)">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12 mb-3">
                    <div class="form-group">
                        <strong>Type:</strong>
                        <input type="text" name="unit_type" class="form-control" placeholder="Type (e.g., weight)">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12 mb-3">
                    <div class="form-group">
                        <strong>Description:</strong>
                        <textarea class="form-control" style="height:150px" name="description" placeholder="Description"></textarea>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12 mb-3">
                    <div class="form-group">
                        <strong>Active:</strong>
                        <select name="is_active" class="form-select">
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12 text-end">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle me-1"></i>Save</button>
                        <a class="btn btn-secondary btn-sm text-left" href="{{ route('unit_of_measures.index') }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
                </div>
            </div>
           
        </form>
    </div>
</div>
@endsection
