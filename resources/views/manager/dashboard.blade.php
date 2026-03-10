@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Dashboard</h3>
    <p class="text-subtitle text-muted">Manager overview of job orders and planning</p>

    {{-- TOP CARDS --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">TOTAL JOB ORDERS</h6>
                    <h3 class="mb-0">{{ number_format($totalJobOrders) }}</h3>
                    <small class="text-muted">All fetched job orders</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">PLANNED JOB ORDERS</h6>
                    <h3 class="mb-0">{{ number_format($plannedJobOrders) }}</h3>
                    <small class="text-muted">Job orders already scheduled in project plans</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">PENDING JOB ORDERS</h6>
                    <h3 class="mb-0">{{ number_format($pendingJobOrders) }}</h3>
                    <small class="text-muted">Not yet scheduled in planning</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">TOTAL PROJECT PLANS</h6>
                    <h3 class="mb-0">{{ number_format($totalProjects) }}</h3>
                    <small class="text-muted">All created project plans</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">ON-GOING PROJECTS</h6>
                    <h3 class="mb-0">{{ number_format($ongoingProjects) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">PENDING PROJECTS</h6>
                    <h3 class="mb-0">{{ number_format($pendingProjects) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">DUE PROJECTS</h6>
                    <h3 class="mb-0">{{ number_format($dueProjects) }}</h3>
                    <small class="text-muted">End date today/past & not done</small>
                </div>
            </div>
        </div>
    </div>

    {{-- CALENDAR --}}
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Planning Calendar</h5>
        </div>
        <div class="card-body">
            <div id="managerCalendar"></div>
        </div>
    </div>

    {{-- RECENT JOB ORDERS --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Job Orders</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>JO #</th>
                            <th>SO #</th>
                            <th>Customer</th>
                            <th>Description</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Delivery Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentJobOrders as $row)
                            <tr>
                                <td>{{ $row->jo_no ?? '-' }}</td>
                                <td>{{ $row->so_no ?? '-' }}</td>
                                <td>{{ $row->customer_name ?? '-' }}</td>
                                <td style="min-width:280px;">{{ $row->description ?? '-' }}</td>
                                <td>{{ $row->location ?? '-' }}</td>
                                <td>
                                    @if($row->job_type === 'LFP')
                                        <span class="badge bg-primary">LFP</span>
                                    @elseif($row->job_type === 'DPOD')
                                        <span class="badge bg-success">DPOD</span>
                                    @else
                                        <span class="badge bg-secondary">-</span>
                                    @endif
                                </td>
                                <td>{{ optional($row->delivery_date)->format('Y-m-d') ?? '-' }}</td>
                                <td>
                                    <div>{{ $row->status ?? '-' }}</div>
                                    <small class="text-muted">{{ $row->sub_status ?? '' }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No job orders found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<style>
  #managerCalendar { max-width: 100%; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('managerCalendar');

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
        events: '{{ route('manager.dashboard.events') }}',
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