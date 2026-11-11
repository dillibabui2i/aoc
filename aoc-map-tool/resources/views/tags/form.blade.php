@extends('layouts.myaccount')
@section('title', isset($tag) ? 'Edit Tag' : 'Create Tag')
@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="form-group col-md-6 head-title">
                <h1 class="h3 mb-2 text-gray-800">{{ isset($tag) ? 'Edit' : 'Create' }} Tag</h1>
                <a href="{{ route('managetags') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{!! implode('', $errors->all('<div>:message</div>')) !!}</div>
        @endif

        <form method="POST" action="{{ isset($tag) ? route('managetags.update', $tag) : route('managetags.store') }}">
            @csrf
            @if(isset($tag)) @method('PUT') @endif

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="name">Tag Name</label>
                    <input type="text" class="form-control" name="name" value="{{ old('name', $tag->name ?? '') }}" required>
                </div>

                <div class="form-group col-md-12">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description">{{ old('description', $tag->description ?? '') }}</textarea>
                </div>

                @if(isset($tag))
                    <div class="form-group col-md-12">
                        <label for="status">Status</label>
                        <select name="status" class="form-control">
                            <option value="enabled" {{ $tag->status === 'enabled' ? 'selected' : '' }}>Enabled</option>
                            <option value="disabled" {{ $tag->status === 'disabled' ? 'selected' : '' }}>Disabled</option>
                        </select>
                    </div>
                @endif
            </div>

            <button type="submit" class="btn btn-primary mt-3">Save</button>
        </form>
    </div>
@endsection
