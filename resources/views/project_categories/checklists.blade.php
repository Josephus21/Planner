{{-- resources/views/project_categories/checklists.blade.php --}}
@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3>Quality Checklist</h3>
            <div class="text-muted">
                Category: <strong>{{ $category->name }}</strong>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('project-categories.edit', $category->id) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square"></i> Edit Category
            </a>
            <a href="{{ route('project-categories.index') }}" class="btn btn-light">
                Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mt-3">{{ $errors->first() }}</div>
    @endif

    {{-- Add checklist item --}}
    @can('project_categories.edit')
    <div class="card mt-3">
        <div class="card-header">Add Checklist Item</div>
        <div class="card-body">
            <form action="{{ route('checklists.store', $category->id) }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Item</label>
                        <input type="text" name="item" class="form-control" value="{{ old('item') }}"
                               required placeholder="e.g. Installed correctly / No scratches / Proper alignment">
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label">Sort</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="{{ old('sort_order', 0) }}" min="0">
                    </div>

                    <div class="col-12 col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="is_required"
                                   id="is_required"
                                   value="1"
                                   @checked(old('is_required'))>
                            <label class="form-check-label" for="is_required">Required</label>
                        </div>
                    </div>

                    <div class="col-12 col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="is_active"
                                   id="is_active"
                                   value="1"
                                   @checked(old('is_active', true))>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- List checklist items --}}
    <div class="card mt-3">
        <div class="card-header">Checklist Items</div>
        <div class="card-body">

            @if($category->checklists->count() === 0)
                <div class="text-muted">No checklist items yet.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>Item</th>
                            <th class="text-center" style="width:90px;">Sort</th>
                            <th class="text-center" style="width:110px;">Required</th>
                            <th class="text-center" style="width:100px;">Active</th>
                            <th class="text-end" style="width:260px;">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($category->checklists as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $item->item }}</td>
                                <td class="text-center">{{ (int)$item->sort_order }}</td>
                                <td class="text-center">
                                    @if($item->is_required)
                                        <span class="badge bg-warning text-dark">Yes</span>
                                    @else
                                        <span class="badge bg-light text-dark border">No</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('project_categories.edit')
                                    <button class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editChecklistModal{{ $item->id }}">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    @endcan

                                    @can('project_categories.delete')
                                    <form action="{{ route('checklists.destroy', $item->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this checklist item?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>

                            {{-- Edit Modal --}}
                            <div class="modal fade" id="editChecklistModal{{ $item->id }}" tabindex="-1"
                                 aria-labelledby="editChecklistModalLabel{{ $item->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <form action="{{ route('checklists.update', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')

                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editChecklistModalLabel{{ $item->id }}">
                                                    Edit Checklist Item
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-12 col-md-7">
                                                        <label class="form-label">Item</label>
                                                        <input type="text" name="item" class="form-control"
                                                               value="{{ old('item', $item->item) }}" required>
                                                    </div>

                                                    <div class="col-12 col-md-2">
                                                        <label class="form-label">Sort</label>
                                                        <input type="number" name="sort_order" class="form-control"
                                                               value="{{ old('sort_order', $item->sort_order) }}" min="0">
                                                    </div>

                                                    <div class="col-12 col-md-3 d-flex align-items-end gap-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="is_required" value="1"
                                                                   id="req{{ $item->id }}"
                                                                   @checked(old('is_required', $item->is_required))>
                                                            <label class="form-check-label" for="req{{ $item->id }}">
                                                                Required
                                                            </label>
                                                        </div>

                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="is_active" value="1"
                                                                   id="act{{ $item->id }}"
                                                                   @checked(old('is_active', $item->is_active))>
                                                            <label class="form-check-label" for="act{{ $item->id }}">
                                                                Active
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <small class="text-muted d-block mt-2">
                                                    Tip: Use sort order to control the checklist sequence.
                                                </small>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                                    Cancel
                                                </button>
                                                <button class="btn btn-primary">
                                                    <i class="bi bi-check2-circle"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection