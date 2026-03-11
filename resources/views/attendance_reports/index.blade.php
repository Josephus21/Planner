@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
  <h3>Attendance Reports</h3>
  <p class="text-subtitle text-muted">
    Period: <strong>{{ strtoupper($period) }}</strong> |
    Range: <strong>{{ $start->format('M d, Y') }}</strong> - <strong>{{ $end->format('M d, Y') }}</strong>

    @if(($viewScope ?? 'self') === 'all_companies')
  | <span class="badge bg-primary">Developer View: All Employees</span>
@elseif(($viewScope ?? 'self') === 'assigned_companies')
  | <span class="badge bg-success">Assigned Companies View</span>
@else
  | <span class="badge bg-secondary">My Logs Only</span>
@endif
  </p>
</div>

<div class="page-content">
<section class="section">

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('attendance.reports.index') }}" class="row g-2">
  <div class="col-md-3">
    <label class="form-label">Period</label>
    <select name="period" class="form-control">
      <option value="daily" {{ $period==='daily'?'selected':'' }}>Daily</option>
      <option value="weekly" {{ $period==='weekly'?'selected':'' }}>Weekly</option>
      <option value="monthly" {{ $period==='monthly'?'selected':'' }}>Monthly</option>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Date</label>
    <input type="date" name="date" value="{{ $date }}" class="form-control">
  </div>

  @if(($viewScope ?? 'self') === 'assigned_companies' || ($viewScope ?? 'self') === 'all_companies')
    <div class="col-md-3">
      <label class="form-label">Company</label>
      <select name="company_id" class="form-control">
        <option value="">All Companies</option>
        @foreach($assignedCompanies as $company)
          <option value="{{ $company->id }}"
            {{ (string)($selectedCompanyId ?? '') === (string)$company->id ? 'selected' : '' }}>
            {{ $company->name }}
          </option>
        @endforeach
      </select>
    </div>
  @endif

  <div class="col-md-2">
    <label class="form-label">Late grace (min)</label>
    <input type="number" name="late_grace" value="{{ $lateGraceMinutes }}" class="form-control" min="0">
  </div>

  <div class="col-md-2">
    <label class="form-label">Overbreak grace</label>
    <input type="number" name="overbreak_grace" value="{{ $overbreakGrace }}" class="form-control" min="0">
  </div>

  <div class="col-md-2">
    <label class="form-label">Overlunch grace</label>
    <input type="number" name="overlunch_grace" value="{{ $overlunchGrace }}" class="form-control" min="0">
  </div>

  <div class="col-md-12">
    <button class="btn btn-primary">Generate</button>
  </div>
</form>
    </div>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-md-2"><div class="card"><div class="card-body"><small>Total Logs</small><h5>{{ $summary['total_logs'] }}</h5></div></div></div>
    <div class="col-md-2"><div class="card"><div class="card-body"><small>Late</small><h5>{{ $summary['late'] }}</h5></div></div></div>
    <div class="col-md-2"><div class="card"><div class="card-body"><small>Overbreak</small><h5>{{ $summary['overbreak'] }}</h5></div></div></div>
    <div class="col-md-2"><div class="card"><div class="card-body"><small>Overlunch</small><h5>{{ $summary['overlunch'] }}</h5></div></div></div>
    <div class="col-md-2"><div class="card"><div class="card-body"><small>Undertime</small><h5>{{ $summary['undertime'] }}</h5></div></div></div>
    <div class="col-md-2"><div class="card"><div class="card-body"><small>Missing Punch</small><h5>{{ $summary['missing_punch'] }}</h5></div></div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5>Attendance Logs</h5>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-striped table-bordered align-middle">
        <thead>
          <tr>
            <th>Date</th>

           @if(($viewScope ?? 'self') !== 'self')
  <td>{{ $r->fullname }}</td>
  <td>{{ $r->company_name ?? '-' }}</td>
@endif

            <th>Schedule</th>
            <th>Time In</th>
            <th>Time In Location</th>
            <th>Late (min)</th>
            <th>Break (min)</th>
            <th>Lunch (min)</th>
            <th>Time Out</th>
            <th>Time Out Location</th>
            <th>Flags</th>
          </tr>
        </thead>
        <tbody>
          @forelse($report as $r)
            <tr>
              <td>{{ \Carbon\Carbon::parse($r->work_date)->format('M d, Y') }}</td>

              @if(($viewScope ?? 'self') !== 'self')
                <td>{{ $r->fullname }}</td>
              @endif

              <td>{{ $r->schedule_name ?? '-' }}</td>

              <td>
                {{ $r->time_in ? \Carbon\Carbon::parse($r->time_in)->format('h:i A') : '---' }}
              </td>

              <td>
                {{ $r->time_in_location ?? '---' }}
              </td>

              <td>
                {{ !is_null($r->late_minutes) ? $r->late_minutes : '---' }}
              </td>

              <td>{{ is_null($r->break_minutes) ? '---' : $r->break_minutes }}</td>
              <td>{{ is_null($r->lunch_minutes) ? '---' : $r->lunch_minutes }}</td>

              <td>
                {{ $r->time_out ? \Carbon\Carbon::parse($r->time_out)->format('h:i A') : '---' }}
              </td>

              <td>
                {{ $r->time_out_location ?? '---' }}
              </td>

              <td>
                @php
                  $flags = [];
                  if($r->is_late) $flags[] = 'LATE';
                  if($r->over_break) $flags[] = 'OVERBREAK';
                  if($r->over_lunch) $flags[] = 'OVERLUNCH';
                  if($r->is_undertime) $flags[] = 'UNDERTIME';
                  if(count($r->missing) > 0) $flags[] = 'MISSING: '.implode(', ', $r->missing);
                @endphp

                @if(count($flags) === 0)
                  <span class="badge bg-success">OK</span>
                @else
                  @foreach($flags as $f)
                    <span class="badge bg-danger">{{ $f }}</span>
                  @endforeach
                @endif
              </td>
            </tr>
          @empty
            @php
  $colspan = (($viewScope ?? 'self') !== 'self') ? 12 : 10;
@endphp
            <tr>
              <td colspan="{{ $colspan }}" class="text-center">No attendance logs found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</section>
</div>
@endsection