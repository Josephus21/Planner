@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Edit Schedule</h3>
</div>

<div class="page-content">
<section class="section">

<div class="card">
    <div class="card-header">
        Edit Schedule
    </div>

    <div class="card-body">

        <form action="{{ route('schedules.update', $schedule->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Name</label>
                <input type="text"
                       name="name"
                       class="form-control"
                       value="{{ $schedule->name }}"
                       required>
            </div>

            <div class="mb-3">
                <label>Time In</label>
                <input type="time"
                       name="time_in"
                       class="form-control"
                       value="{{ $schedule->time_in }}"
                       required>
            </div>

            <div class="mb-3">
                <label>Lunch Start</label>
                <input type="time"
                       name="lunch_start"
                       class="form-control"
                       value="{{ $schedule->lunch_start }}">
            </div>

            <div class="mb-3">
                <label>Lunch End</label>
                <input type="time"
                       name="lunch_end"
                       class="form-control"
                       value="{{ $schedule->lunch_end }}">
            </div>

            <div class="mb-3">
                <label>Time Out</label>
                <input type="time"
                       name="time_out"
                       class="form-control"
                       value="{{ $schedule->time_out }}"
                       required>
            </div>

            <button class="btn btn-success">Update Schedule</button>
            <a href="{{ route('schedules.index') }}" class="btn btn-secondary">
                Cancel
            </a>

        </form>

    </div>
</div>

</section>
</div>
@endsection