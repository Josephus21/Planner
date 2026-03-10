{{-- resources/views/project_categories/index.blade.php --}}
@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3>Project Categories</h3>
            <p class="text-muted mb-0">Manage categories and their quality checklists.</p>
        </div>

        @can('project_categories.create')
        <a href="{{ route('project-categories.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Category
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    <div class="card mt-3">
        <div class="card-body">
            @if($categories->count() === 0)
                <div class="text-muted">No categories yet.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th class="text-center" style="width:150px;">Checklist Items</th>
                            <th style="width:260px;" class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($categories as $i => $cat)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $cat->name }}</td>
                                <td class="text-muted">
                                    {{ $cat->description ?: '—' }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        {{ (int)($cat->checklists_count ?? 0) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('project-categories.checklists', $cat->id) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-check2-square"></i> Checklist
                                    </a>

                                    @can('project_categories.edit')
                                    <a href="{{ route('project-categories.edit', $cat->id) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    @endcan

                                    @can('project_categories.delete')
                                    <form action="{{ route('project-categories.destroy', $cat->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this category? This will also delete its checklist items.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection