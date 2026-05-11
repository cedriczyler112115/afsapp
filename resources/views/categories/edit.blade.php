@extends('layouts.app')

@section('title', 'Edit Category')

@section('content')
<div class="card">
    <div class="card-header">
        Edit Category
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
      
        <form action="{{ route('categories.update',$category->category_id) }}" method="POST">
            @csrf
            @method('PUT')
       
             <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 mb-3">
                    <div class="form-group">
                        <strong>Name:</strong>
                        <input type="text" name="category_name" value="{{ $category->category_name }}" class="form-control" placeholder="Name">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12 mb-3">
                    <div class="form-group">
                        <strong>Description:</strong>
                        <textarea class="form-control" style="height:150px" name="description" placeholder="Description">{{ $category->description }}</textarea>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12 mb-3">
                    <div class="form-group">
                        <strong>Status:</strong>
                        <select name="status" class="form-select">
                            <option value="1" {{ (int)$category->status === 1 ? 'selected' : '' }}>1 - Active</option>
                            <option value="2" {{ (int)$category->status === 2 ? 'selected' : '' }}>2 - Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12 text-end">
                  <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle me-1"></i>Save</button>
                  <a class="btn btn-secondary btn-sm" href="{{ route('categories.index') }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
                </div>
            </div>
       
        </form>
    </div>
</div>
@endsection
