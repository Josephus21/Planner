@extends('layouts.dashboard')

@section('content')
<div id="main">
    <header class="mb-3">
        <a href="#" class="burger-btn d-block d-xl-none">
            <i class="bi bi-justify fs-3"></i>
        </a>
    </header>

    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Holidays</h3>
                    <p class="text-subtitle text-muted">Edit holiday for payroll and calendar reference</p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('holidays.index') }}">Holidays</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <section class="section">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Edit Holiday</h5>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('holidays.update', $holiday->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Holiday Name</label>
                            <input
                                type="text"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $holiday->name) }}"
                                placeholder="e.g. New Year's Day"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Holiday Date</label>
                            <input
                                type="date"
                                name="holiday_date"
                                class="form-control @error('holiday_date') is-invalid @enderror"
                                value="{{ old('holiday_date', $holiday->holiday_date ? $holiday->holiday_date->format('Y-m-d') : '') }}"
                                required
                            >
                            @error('holiday_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select
                                name="type"
                                class="form-select @error('type') is-invalid @enderror"
                                required
                            >
                                <option value="">Select Type</option>
                                <option value="regular" {{ old('type', $holiday->type) === 'regular' ? 'selected' : '' }}>
                                    Regular
                                </option>
                                <option value="special" {{ old('type', $holiday->type) === 'special' ? 'selected' : '' }}>
                                    Special
                                </option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <select
                                name="company_id"
                                class="form-select @error('company_id') is-invalid @enderror"
                            >
                                <option value="">All Companies</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ (string) old('company_id', $holiday->company_id) === (string) $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Leave blank if this holiday applies to all companies.</small>
                            @error('company_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input type="hidden" name="is_recurring" value="0">
                            <input
                                type="checkbox"
                                name="is_recurring"
                                value="1"
                                class="form-check-input"
                                id="is_recurring"
                                {{ old('is_recurring', $holiday->is_recurring) ? 'checked' : '' }}
                            >
                            <label for="is_recurring" class="form-check-label">
                                Recurring every year
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input type="hidden" name="is_active" value="0">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="form-check-input"
                                id="is_active"
                                {{ old('is_active', $holiday->is_active) ? 'checked' : '' }}
                            >
                            <label for="is_active" class="form-check-label">
                                Active
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea
                                name="notes"
                                rows="4"
                                class="form-control @error('notes') is-invalid @enderror"
                                placeholder="Optional notes..."
                            >{{ old('notes', $holiday->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Holiday</button>
                            <a href="{{ route('holidays.index') }}" class="btn btn-secondary">Back to list</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection