@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Create Schedule</h3>
</div>

<div class="page-content">
<section class="section">

<div class="card">
    <div class="card-header">New Schedule</div>

    <div class="card-body">

        {{-- Show validation errors --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('schedules.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label>Name</label>
                <input type="text"
                       name="name"
                       class="form-control"
                       value="{{ old('name') }}"
                       placeholder="e.g. Regular 8AM-5PM"
                       required>
            </div>

            <div class="mb-3">
                <label>Start Time</label>
                <input type="time"
                       name="start_time"
                       class="form-control"
                       value="{{ old('start_time') }}"
                       required>
            </div>

            <div class="mb-3">
                <label>Break Start</label>
                <input type="time"
                       name="break_start"
                       class="form-control"
                       value="{{ old('break_start') }}">
            </div>

            <div class="mb-3">
                <label>Break End</label>
                <input type="time"
                       name="break_end"
                       class="form-control"
                       value="{{ old('break_end') }}">
            </div>

            <div class="mb-3">
                <label>Lunch Start</label>
                <input type="time"
                       name="lunch_start"
                       class="form-control"
                       value="{{ old('lunch_start') }}">
            </div>

            <div class="mb-3">
                <label>Lunch End</label>
                <input type="time"
                       name="lunch_end"
                       class="form-control"
                       value="{{ old('lunch_end') }}">
            </div>

            <div class="mb-3">
                <label>End Time</label>
                <input type="time"
                       name="end_time"
                       class="form-control"
                       value="{{ old('end_time') }}"
                       required>
            </div>

            <button class="btn btn-success" type="submit">Save Schedule</button>
            <a href="{{ route('schedules.index') }}" class="btn btn-secondary">Cancel</a>
        </form>

    </div>
</div>

</section>
</div>
@endsection