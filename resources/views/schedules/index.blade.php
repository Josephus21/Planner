@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Schedules</h3>
</div>

<div class="page-content">
<section class="section">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Schedule List</span>
            <a href="{{ route('schedules.create') }}" class="btn btn-primary btn-sm">
                + Create Schedule
            </a>
        </div>

        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <table class="table table-bordered table-striped" id="table1">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Time In</th>
                        <th>Break Start</th>
                        <th>Break End</th>
                        <th>Lunch Start</th>
                        <th>Lunch End</th>
                        <th>Time Out</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($schedules as $schedule)
<tr>
  <td>{{ $loop->iteration }}</td>
  <td>{{ $schedule->name }}</td>
  <td>{{ $schedule->start_time }}</td>
  <td>{{ $schedule->break_start ?? '-' }}</td>
  <td>{{ $schedule->break_end ?? '-' }}</td>
  <td>{{ $schedule->lunch_start ?? '-' }}</td>
  <td>{{ $schedule->lunch_end ?? '-' }}</td>
  <td>{{ $schedule->end_time }}</td>

  <td>
    <a href="{{ route('schedules.edit', $schedule->id) }}" class="btn btn-warning btn-sm">Edit</a>

    <form action="{{ route('schedules.destroy', $schedule->id) }}" method="POST" style="display:inline;">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-danger btn-sm"
        onclick="return confirm('Delete this schedule?')">Delete</button>
    </form>
  </td>
</tr>
@empty
<tr>
  <td colspan="9" class="text-center">No schedules found.</td>
</tr>
@endforelse
                </tbody>
            </table>

        </div>
    </div>

</section>
</div>
@endsection