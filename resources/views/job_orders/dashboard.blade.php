@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Dashboard</h3>
    <p class="text-subtitle text-muted">Job order planning overview</p>

    {{-- TOP CARDS --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">TOTAL JOB ORDERS</h6>
                    <h3 class="mb-0">{{ number_format($totalJobOrders) }}</h3>
                    <small class="text-muted"></small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">ON-GOING PROJECT</h6>
                    <h3 class="mb-0">{{ number_format($ongoingProjects) }}</h3>
                    <small class="text-muted"></small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">PENDING JOB ORDERS</h6>
                    <h3 class="mb-0">{{ number_format($pendingJobOrders) }}</h3>
                    <small class="text-muted"></small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">DUE PROJECTS</h6>
                    <h3 class="mb-0">{{ number_format($dueProjects) }}</h3>
                    <small class="text-muted"></small>
                </div>
            </div>
        </div>
    </div>

    {{-- CALENDAR --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Project Plan Calendar</h5>
        </div>
        <div class="card-body">
            <div id="plannerCalendar"></div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<style>
  #plannerCalendar { max-width: 100%; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('plannerCalendar');

    const calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        height: 650,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: {
            dayGridMonth: 'month',
            timeGridWeek: 'week',
            timeGridDay: 'day',
            listWeek: 'list'
        },
        events: '{{ route('job-orders.dashboard.events') }}',
        eventClick: function(info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        }
    });

    calendar.render();
});
</script>
@endpush
@endsection