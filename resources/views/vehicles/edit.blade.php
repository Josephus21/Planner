@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Edit Vehicle</h3>
    <p class="text-subtitle text-muted">Update car/truck details.</p>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('vehicles.update', $vehicle->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">

                    <div class="col-12 col-md-6">
                        <label class="form-label">Vehicle Name</label>
                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ old('name', $vehicle->name) }}"
                               required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="truck" @selected(old('type', $vehicle->type) === 'truck')>TRUCK</option>
                            <option value="car"   @selected(old('type', $vehicle->type) === 'car')>CAR</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Plate No (optional)</label>
                        <input type="text"
                               name="plate_no"
                               class="form-control"
                               value="{{ old('plate_no', $vehicle->plate_no) }}">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Status</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   @checked(old('is_active', $vehicle->is_active))>
                            <label class="form-check-label" for="is_active">
                                Active (available for selection)
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description (optional)</label>
                        <input type="text"
                               name="description"
                               class="form-control"
                               value="{{ old('description', $vehicle->description) }}">
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> Update
                    </button>
                    <a href="{{ route('vehicles.index') }}" class="btn btn-light">Back</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection