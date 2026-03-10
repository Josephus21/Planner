{{-- resources/views/project_categories/edit.blade.php --}}
@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Edit Project Category</h3>

    @if($errors->any())
        <div class="alert alert-danger mt-3">{{ $errors->first() }}</div>
    @endif

    <div class="card mt-3">
        <div class="card-body">
            <form action="{{ route('project-categories.update', $category->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Category Name</label>
                    <input type="text"
                           name="name"
                           class="form-control"
                           value="{{ old('name', $category->name) }}"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description (optional)</label>
                    <textarea name="description"
                              class="form-control"
                              rows="3">{{ old('description', $category->description) }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> Update
                    </button>
                    <a href="{{ route('project-categories.index') }}" class="btn btn-light">Back</a>
                    <a href="{{ route('project-categories.checklists', $category->id) }}"
                       class="btn btn-outline-secondary ms-auto">
                        <i class="bi bi-check2-square"></i> Manage Checklist
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection