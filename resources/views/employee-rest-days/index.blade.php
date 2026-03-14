@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Employee Rest Day Schedule</h3>
    <p class="text-subtitle text-muted">Manage weekly rest days per employee.</p>
</div>

<div class="page-content">
<section class="section">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Rest Day Schedule List</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Rest Days</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr>
                                <td>
                                    {{ $employee->first_name ?? '' }} {{ $employee->last_name ?? '' }}
                                </td>
                                <td>
                                    @php
                                        $days = $employee->restDays->pluck('day_name')
                                            ->map(fn($d) => ucfirst($d))
                                            ->implode(', ');
                                    @endphp
                                    {{ $days ?: 'No rest day assigned' }}
                                </td>
                                <td>
                                    <a href="{{ route('employee-rest-days.edit', $employee->id) }}" class="btn btn-sm btn-primary">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">No employees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
</div>
@endsection