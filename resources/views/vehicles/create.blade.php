@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Add Vehicle</h3>
    <p class="text-subtitle text-muted">Create a new car/truck record.</p>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('vehicles.store') }}" method="POST">
                @csrf

                <div class="row g-3">

                    <div class="col-12 col-md-6">
                        <label class="form-label">Vehicle Name</label>
                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ old('name') }}"
                               placeholder="e.g. L300 (GAE151), Hilux, Truck #3"
                               required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="truck" @selected(old('type','truck') === 'truck')>TRUCK</option>
                            <option value="car"   @selected(old('type') === 'car')>CAR</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Plate No (optional)</label>
                        <input type="text"
                               name="plate_no"
                               class="form-control"
                               value="{{ old('plate_no') }}"
                               placeholder="e.g. GAE151">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Status</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   @checked(old('is_active', 1))>
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
                               value="{{ old('description') }}"
                               placeholder="Any notes about this vehicle...">
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> Save
                    </button>
                    <a href="{{ route('vehicles.index') }}" class="btn btn-light">Back</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection