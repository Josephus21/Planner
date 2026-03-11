@extends('layouts.dashboard')

@section('content')
@php
  use Carbon\Carbon;

  $hasSchedule = (bool) $schedule;

  $nowMnl = now('Asia/Manila');

  // Build today's schedule start datetime (Asia/Manila)
  $scheduleStart = null;
  if ($hasSchedule && !empty($schedule->start_time)) {
    $scheduleStart = Carbon::parse(
      $nowMnl->format('Y-m-d') . ' ' . $schedule->start_time,
      'Asia/Manila'
    );
  }

  // Rule: can only Time In starting 10 minutes before scheduled start time
  $timeInAllowed = $hasSchedule
    && $scheduleStart
    && $nowMnl->greaterThanOrEqualTo($scheduleStart->copy()->subMinutes(10));

  // Optional: disable break/lunch if not configured in schedule
  $hasBreak = $hasSchedule && $schedule->break_start && $schedule->break_end;
  $hasLunch = $hasSchedule && $schedule->lunch_start && $schedule->lunch_end;

  // Global: if no schedule today, ALL buttons disabled
  $punchDisabledAll = !$hasSchedule;
@endphp

<div class="page-heading">
  <h3>Dashboard</h3>
  <p class="text-subtitle text-muted">Welcome, {{ $employee->fullname }}</p>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      {{ $errors->first() }}
    </div>
  @endif

  <div id="location-status" class="alert alert-info d-none"></div>

  {{-- ===== SUMMARY CARDS ===== --}}
  <div class="row g-3 mb-3">
    {{-- Total Late Minutes --}}
    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <h6 class="text-muted mb-1">Total Late (Minutes)</h6>
            <h3 class="mb-0">{{ number_format((int) ($totalLateMinutes ?? 0)) }}</h3>
            <small class="text-muted">This month</small>
          </div>
          <div class="fs-2 text-warning">
            <i class="bi bi-clock-history"></i>
          </div>
        </div>
      </div>
    </div>

    {{-- Running Payroll Balance --}}
    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <h6 class="text-muted mb-1">Running Payroll Balance</h6>
            <h3 class="mb-0">
              ₱{{ number_format((float) ($runningPayrollBalance ?? 0), 2) }}
            </h3>
            <small class="text-muted">As of today (no deductions)</small>
          </div>
          <div class="fs-2 text-success">
            <i class="bi bi-cash-coin"></i>
          </div>
        </div>
      </div>
    </div>

    {{-- Total Absent --}}
    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <h6 class="text-muted mb-1">Total Absent</h6>
            <h3 class="mb-0">{{ number_format((int) ($totalAbsent ?? 0)) }}</h3>
            <small class="text-muted">This month</small>
          </div>
          <div class="fs-2 text-danger">
            <i class="bi bi-person-x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== CALENDAR (below the 3 cards) ===== --}}
  <div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0">My Schedule Calendar</h5>
      <small class="text-muted">Timezone: Asia/Manila</small>
    </div>
    <div class="card-body">
      <div id="scheduleCalendar"></div>
    </div>
  </div>

  {{-- ===== TODAY SCHEDULE CARD ===== --}}
  <div class="card">
    <div class="card-header">
      <h5>Today's Schedule ({{ now('Asia/Manila')->format('M d, Y') }})</h5>
    </div>
    <div class="card-body">

      @if(!$schedule)
        <div class="alert alert-warning">No schedule assigned for today.</div>
        <div class="alert alert-secondary mb-3">
          Attendance punch is disabled because you have no schedule today.
        </div>
      @else
        <p>
          <strong>{{ $schedule->name }}</strong><br>
          Work: {{ $schedule->start_time }} - {{ $schedule->end_time }}<br>

          @if($schedule->break_start && $schedule->break_end)
            Break: {{ $schedule->break_start }} - {{ $schedule->break_end }}<br>
          @endif

          @if($schedule->lunch_start && $schedule->lunch_end)
            Lunch: {{ $schedule->lunch_start }} - {{ $schedule->lunch_end }}<br>
          @endif
        </p>
      @endif

      <hr>

      <h6>Attendance Punch</h6>

      <div class="row g-2">
        {{-- TIME IN --}}
        <div class="col-md-4">
          <form method="POST" action="{{ route('attendance.punch') }}" class="attendance-punch-form">
            @csrf
            <input type="hidden" name="action" value="time_in">
            <input type="hidden" name="latitude" class="js-latitude">
            <input type="hidden" name="longitude" class="js-longitude">
            <input type="hidden" name="accuracy" class="js-accuracy">

            <button class="btn btn-success w-100 js-punch-btn"
              {{ ($punchDisabledAll || $log->time_in || !$timeInAllowed) ? 'disabled' : '' }}>
              Time In
            </button>

            <small class="text-muted d-block mt-1">
              {{ $log->time_in ? $log->time_in->format('h:i A') : '---' }}
            </small>

            @if($hasSchedule && !$log->time_in && !$timeInAllowed && $scheduleStart)
              <small class="text-danger d-block">
                You can Time In starting {{ $scheduleStart->copy()->subMinutes(10)->format('h:i A') }}
                (10 mins before start).
              </small>
            @endif
          </form>
        </div>

        {{-- BREAK OUT --}}
        <div class="col-md-4">
          <form method="POST" action="{{ route('attendance.punch') }}" class="attendance-punch-form">
            @csrf
            <input type="hidden" name="action" value="break_out">
            <input type="hidden" name="latitude" class="js-latitude">
            <input type="hidden" name="longitude" class="js-longitude">
            <input type="hidden" name="accuracy" class="js-accuracy">

            <button class="btn btn-warning w-100 js-punch-btn"
              {{ ($punchDisabledAll || !$hasBreak || $log->break_out) ? 'disabled' : '' }}>
              Break Out
            </button>

            <small class="text-muted d-block mt-1">
              {{ $log->break_out ? $log->break_out->format('h:i A') : '---' }}
            </small>

            @if($hasSchedule && !$hasBreak)
              <small class="text-muted d-block">Break is not configured for today.</small>
            @endif
          </form>
        </div>

        {{-- BREAK IN --}}
        <div class="col-md-4">
          <form method="POST" action="{{ route('attendance.punch') }}" class="attendance-punch-form">
            @csrf
            <input type="hidden" name="action" value="break_in">
            <input type="hidden" name="latitude" class="js-latitude">
            <input type="hidden" name="longitude" class="js-longitude">
            <input type="hidden" name="accuracy" class="js-accuracy">

            <button class="btn btn-warning w-100 js-punch-btn"
              {{ ($punchDisabledAll || !$hasBreak || $log->break_in) ? 'disabled' : '' }}>
              Break In
            </button>

            <small class="text-muted d-block mt-1">
              {{ $log->break_in ? $log->break_in->format('h:i A') : '---' }}
            </small>

            @if($hasSchedule && !$hasBreak)
              <small class="text-muted d-block">Break is not configured for today.</small>
            @endif
          </form>
        </div>

        {{-- LUNCH OUT --}}
        <div class="col-md-4">
          <form method="POST" action="{{ route('attendance.punch') }}" class="attendance-punch-form">
            @csrf
            <input type="hidden" name="action" value="lunch_out">
            <input type="hidden" name="latitude" class="js-latitude">
            <input type="hidden" name="longitude" class="js-longitude">
            <input type="hidden" name="accuracy" class="js-accuracy">

            <button class="btn btn-info w-100 js-punch-btn"
              {{ ($punchDisabledAll || !$hasLunch || $log->lunch_out) ? 'disabled' : '' }}>
              Lunch Out
            </button>

            <small class="text-muted d-block mt-1">
              {{ $log->lunch_out ? $log->lunch_out->format('h:i A') : '---' }}
            </small>

            @if($hasSchedule && !$hasLunch)
              <small class="text-muted d-block">Lunch is not configured for today.</small>
            @endif
          </form>
        </div>

        {{-- LUNCH IN --}}
        <div class="col-md-4">
          <form method="POST" action="{{ route('attendance.punch') }}" class="attendance-punch-form">
            @csrf
            <input type="hidden" name="action" value="lunch_in">
            <input type="hidden" name="latitude" class="js-latitude">
            <input type="hidden" name="longitude" class="js-longitude">
            <input type="hidden" name="accuracy" class="js-accuracy">

            <button class="btn btn-info w-100 js-punch-btn"
              {{ ($punchDisabledAll || !$hasLunch || $log->lunch_in) ? 'disabled' : '' }}>
              Lunch In
            </button>

            <small class="text-muted d-block mt-1">
              {{ $log->lunch_in ? $log->lunch_in->format('h:i A') : '---' }}
            </small>

            @if($hasSchedule && !$hasLunch)
              <small class="text-muted d-block">Lunch is not configured for today.</small>
            @endif
          </form>
        </div>

        {{-- TIME OUT --}}
        <div class="col-md-4">
          <form method="POST" action="{{ route('attendance.punch') }}" class="attendance-punch-form">
            @csrf
            <input type="hidden" name="action" value="time_out">
            <input type="hidden" name="latitude" class="js-latitude">
            <input type="hidden" name="longitude" class="js-longitude">
            <input type="hidden" name="accuracy" class="js-accuracy">

            <button class="btn btn-danger w-100 js-punch-btn"
              {{ ($punchDisabledAll || $log->time_out) ? 'disabled' : '' }}>
              Time Out
            </button>

            <small class="text-muted d-block mt-1">
              {{ $log->time_out ? $log->time_out->format('h:i A') : '---' }}
            </small>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
  <style>
    #scheduleCalendar {
      min-height: 650px;
    }
  </style>
@endpush

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const el = document.getElementById('scheduleCalendar');

      if (el) {
        const calendar = new FullCalendar.Calendar(el, {
          timeZone: 'Asia/Manila',
          initialView: 'dayGridMonth',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
          },
          navLinks: true,
          nowIndicator: true,
          height: 'auto',
          eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
          },
          events: "{{ route('employee.schedule.events') }}",
          eventDisplay: 'block',
          dayMaxEvents: true,
        });

        calendar.render();
      }

      const forms = document.querySelectorAll('.attendance-punch-form');
      const locationStatus = document.getElementById('location-status');

      forms.forEach(form => {
        form.addEventListener('submit', function (e) {
          e.preventDefault();

          const button = form.querySelector('.js-punch-btn');
          const actionInput = form.querySelector('input[name="action"]');
          const actionName = actionInput ? actionInput.value.replace('_', ' ').toUpperCase() : 'PUNCH';

          if (button) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = 'Getting location...';
          }

          if (locationStatus) {
            locationStatus.classList.remove('d-none', 'alert-danger', 'alert-success');
            locationStatus.classList.add('alert-info');
            locationStatus.innerHTML = 'Please wait. Getting your location for ' + actionName + '...';
          }

          if (!navigator.geolocation) {
            if (locationStatus) {
              locationStatus.classList.remove('alert-info');
              locationStatus.classList.add('alert-danger');
              locationStatus.innerHTML = 'Geolocation is not supported by this browser. Submitting without location.';
            }

            form.submit();
            return;
          }

          navigator.geolocation.getCurrentPosition(
            function (position) {
              const latInput = form.querySelector('.js-latitude');
              const lngInput = form.querySelector('.js-longitude');
              const accInput = form.querySelector('.js-accuracy');

              if (latInput) latInput.value = position.coords.latitude;
              if (lngInput) lngInput.value = position.coords.longitude;
              if (accInput) accInput.value = position.coords.accuracy;

              if (locationStatus) {
                locationStatus.classList.remove('alert-info', 'alert-danger');
                locationStatus.classList.add('alert-success');
                locationStatus.innerHTML = 'Location captured successfully. Submitting ' + actionName + '...';
              }

              form.submit();
            },
            function (error) {
              let message = 'Unable to get location. Submitting without GPS coordinates.';

              switch (error.code) {
                case error.PERMISSION_DENIED:
                  message = 'Location permission was denied. Submitting without GPS coordinates.';
                  break;
                case error.POSITION_UNAVAILABLE:
                  message = 'Location information is unavailable. Submitting without GPS coordinates.';
                  break;
                case error.TIMEOUT:
                  message = 'Location request timed out. Submitting without GPS coordinates.';
                  break;
              }

              if (locationStatus) {
                locationStatus.classList.remove('alert-info', 'alert-success');
                locationStatus.classList.add('alert-danger');
                locationStatus.innerHTML = message;
              }

              form.submit();
            },
            {
              enableHighAccuracy: true,
              timeout: 10000,
              maximumAge: 0
            }
          );
        });
      });
    });
  </script>
@endpush