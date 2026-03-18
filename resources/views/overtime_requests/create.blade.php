@extends('layouts.dashboard')

@section('content')
<div id="main">
    <div class="page-heading">
        <h3>Request Overtime</h3>
        <p class="text-subtitle text-muted">Submit overtime dates and hours for approval.</p>
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('overtime-requests.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Optional title">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="3" required>{{ old('reason') }}</textarea>
                        </div>

                        <div id="ot-rows">
                            <div class="row ot-row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="dates[0][ot_date]" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="dates[0][start_time]" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="dates[0][end_time]" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Break (mins)</label>
                                    <input type="number" min="0" step="1" name="dates[0][break_minutes]" class="form-control" value="0">
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-light-secondary mb-3" id="add-ot-row">
                            Add Another Date
                        </button>

                        <div>
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                            <a href="{{ route('overtime-requests.index') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let index = 1;
    const container = document.getElementById('ot-rows');
    const btn = document.getElementById('add-ot-row');

    btn.addEventListener('click', function () {
        const html = `
            <div class="row ot-row mb-3">
                <div class="col-md-4">
                    <input type="date" name="dates[${index}][ot_date]" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <input type="time" name="dates[${index}][start_time]" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <input type="time" name="dates[${index}][end_time]" class="form-control" required>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <input type="number" min="0" step="1" name="dates[${index}][break_minutes]" class="form-control" value="0">
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        index++;
    });
});
</script>
@endpush
@endsection