@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Edit Rest Day Schedule</h3>
    <p class="text-subtitle text-muted">
        Employee: <strong>{{ $employee->first_name ?? '' }} {{ $employee->last_name ?? '' }}</strong>
    </p>
</div>

<div class="page-content">
<section class="section">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Select Rest Day(s)</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('employee-rest-days.update', $employee->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    @foreach($days as $day)
                        <div class="col-md-3 mb-3">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="rest_days[]"
                                    value="{{ $day }}"
                                    id="day_{{ $day }}"
                                    {{ in_array($day, $selectedDays) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="day_{{ $day }}">
                                    {{ ucfirst($day) }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('employee-rest-days.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
</div>
@endsection