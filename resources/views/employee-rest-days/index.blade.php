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
        <p class="text-subtitle text-muted">Manage employee weekly rest days.</p>
    </div>

    <div class="page-content">
        <section class="section">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

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
                    <h4 class="card-title">Employees</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="table1">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Company</th>
                                    <th>Rest Days</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $employee)
                                    <tr>
                                        <td>{{ $employee->fullname }}</td>
                                        <td>{{ $employee->company->name ?? '-' }}</td>
                                        <td>
                                            {{
                                                $employee->restDays
                                                    ->pluck('day_name')
                                                    ->map(fn($day) => ucfirst($day))
                                                    ->implode(', ') ?: 'No rest day assigned'
                                            }}
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