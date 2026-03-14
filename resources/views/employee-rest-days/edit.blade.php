@extends('layouts.dashboard')

@section('content')
<div id="main">
    <header class="mb-3">
        <a href="#" class="burger-btn d-block d-xl-none">
            <i class="bi bi-justify fs-3"></i>
        </a>
    </header>

    <div class="page-heading">
        <h3>Edit Rest Day Schedule</h3>
        <p class="text-subtitle text-muted">
            Employee: <strong>{{ $employee->fullname }}</strong>
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
                    <h4 class="card-title">Select Rest Day Dates</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('employee-rest-days.update', $employee->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Rest Day Calendar</label>
                            <input
                                type="text"
                                id="rest_dates_picker"
                                class="form-control"
                                placeholder="Click calendar dates"
                            >
                            <small class="text-muted">
                                Click multiple dates in the calendar to assign rest days.
                            </small>
                        </div>

                        <div id="rest-dates-container"></div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <a href="{{ route('employee-rest-days.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const selectedDates = @json($selectedDates ?? []);
    const container = document.getElementById('rest-dates-container');

    function renderHiddenInputs(dates) {
        container.innerHTML = '';

        dates.forEach(function(date) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'rest_dates[]';
            input.value = date;
            container.appendChild(input);
        });
    }

    flatpickr("#rest_dates_picker", {
        mode: "multiple",
        dateFormat: "Y-m-d",
        defaultDate: selectedDates,
        onReady: function(selectedDatesObj, dateStr, instance) {
            renderHiddenInputs(instance.selectedDates.map(d => instance.formatDate(d, "Y-m-d")));
        },
        onChange: function(selectedDatesObj, dateStr, instance) {
            renderHiddenInputs(instance.selectedDates.map(d => instance.formatDate(d, "Y-m-d")));
        }
    });
</script>
@endpush