@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3>Vehicles</h3>
            <p class="text-subtitle text-muted mb-0">Manage cars/trucks for project selection.</p>
        </div>

        @can('vehicles.create')
            <a href="{{ route('vehicles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Vehicle
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mt-3">{{ $errors->first() }}</div>
    @endif

    <div class="card mt-3">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped" id="table1">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Plate No</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($vehicles as $v)
                        <tr>
                            <td>{{ $v->id }}</td>
                            <td>
                                <div class="fw-bold">{{ $v->name }}</div>
                                @if(!empty($v->description))
                                    <small class="text-muted">{{ $v->description }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $v->type === 'truck' ? 'primary' : 'info' }}">
                                    {{ strtoupper($v->type) }}
                                </span>
                            </td>
                            <td>{{ $v->plate_no ?: '—' }}</td>
                            <td>
                                @if($v->is_active)
                                    <span class="badge bg-success">ACTIVE</span>
                                @else
                                    <span class="badge bg-secondary">INACTIVE</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ optional($v->updated_at)->format('M d, Y h:i A') }}
                                </small>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    @can('vehicles.edit')
                                        <a href="{{ route('vehicles.edit', $v->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @endcan

                                    @can('vehicles.delete')
                                        <form action="{{ route('vehicles.destroy', $v->id) }}" method="POST"
                                              onsubmit="return confirm('Delete this vehicle?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No vehicles found.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection