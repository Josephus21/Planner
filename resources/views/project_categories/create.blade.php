{{-- resources/views/project_categories/create.blade.php --}}
@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Create Project Category</h3>

    @if($errors->any())
        <div class="alert alert-danger mt-3">{{ $errors->first() }}</div>
    @endif

    <div class="card mt-3">
        <div class="card-body">
            <form action="{{ route('project-categories.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Category Name</label>
                    <input type="text"
                           name="name"
                           class="form-control"
                           value="{{ old('name') }}"
                           required
                           placeholder="e.g. Signage, Installation, Printing">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description (optional)</label>
                    <textarea name="description"
                              class="form-control"
                              rows="3"
                              placeholder="Short description">{{ old('description') }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> Save
                    </button>
                    <a href="{{ route('project-categories.index') }}" class="btn btn-light">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection