@extends('layouts.dashboard')

@section('content')
<div id="main">
    <header class="mb-3">
        <a href="#" class="burger-btn d-block d-xl-none">
            <i class="bi bi-justify fs-3"></i>
        </a>
    </header>

    <div class="page-heading">
        <h3>Rest Day Schedule</h3>
        <p class="text-subtitle text-muted">Manage employee rest day dates.</p>
    </div>

    <div class="page-content">
        <section class="section">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Employees</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="table1">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Company</th>
                                    <th>Rest Day Dates</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $employee)
                                    <tr>
                                        <td>{{ $employee->fullname }}</td>
                                        <td>{{ $employee->company->name ?? '-' }}</td>
                                        <td>
                                            @if($employee->restDayDates->count())
                                                {{ $employee->restDayDates->take(5)->map(fn($r) => \Carbon\Carbon::parse($r->rest_date)->format('M d, Y'))->implode(', ') }}
                                                @if($employee->restDayDates->count() > 5)
                                                    <br><small class="text-muted">and {{ $employee->restDayDates->count() - 5 }} more</small>
                                                @endif
                                            @else
                                                No rest day assigned
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('employee-rest-days.edit', $employee->id) }}" class="btn btn-sm btn-primary">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No employees found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection