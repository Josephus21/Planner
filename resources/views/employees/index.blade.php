@extends('layouts.dashboard')

@section('content')
            <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>
            
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Employees</h3>
                <p class="text-subtitle text-muted">Handle employees data or profile</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Employees</li>
                        <li class="breadcrumb-item active" aria-current="page">Index</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Data
                </h5>
            </div>
            <div class="card-body">

            <div class="d-flex">
                <a href="{{ route('employees.create') }}" class="btn btn-primary mb-3 ms-auto">New Employee</a>
            </div>
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    
                @endif
                <table class="table table-striped" id="table1">
                    <thead>
                        <tr>
                            <th>Fullname</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Salary</th>
                            <th>Option</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                        <tr>
                            <td>{{ $employee->fullname }}</td>
                            <td>{{ $employee->email }}</td>                            <td>{{ $employee->hire_date }}</td>
                            <td>{{ $employee->department->name }}</td>
                            <td>{{ $employee->role->title }}</td>
                            <td>
                                @if($employee->status == 'inactive')
                                    <span class="text-warning">{{ $employee->status }}</span>
                                @elseif($employee->status == 'active')
                                    <span class="text-success">{{ $employee->status }}</span>
                                @else
                                    <span class="text-info">{{ $employee->status }}</span>
                                @endif
                            </td>

                            <td>
                                {{ $employee->salary }}
                            </td>
                            <td>
                                <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-info btn-sm">View</a>
                                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this employee?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </section>
</div>


@endsection