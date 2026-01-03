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
                <h3>Payrolls</h3>
                <p class="text-subtitle text-muted">Handle payroll data</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Payrolls</li>
                        <li class="breadcrumb-item active" aria-current="page">New</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Create 
                </h5>
            </div>
            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            <form action="{{ route('payrolls.store') }}" method="POST"> 
                @csrf

                 <div class="mb-3">
                    <label for="" class="form-label">Employee</label>
                    <select name="employee_id" id="employee_id" class="form-control" required>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->fullname }}</option>
                        @endforeach
                    </select>
                    @error('employee_id')
                    <div class="invalid-feedback">{{$message}}</div>
                    @enderror
                </div>



               

                <div class="mb-3">
                    <label for="" class="form-label">Salary</label>
                    <input type="number" class="form-control" name="amount" required>
                    @error('amount')
                    <div class="invalid-feedback">{{$message}}</div>
                    @enderror
                </div>

               
                
                <div class="mb-3">
                    <label for="" class="form-label">Deduction</label>
                    <input type="number" class="form-control" name="deductions" required>
                    @error('deductions')
                    <div class="invalid-feedback">{{$message}}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="" class="form-label">Bonuses</label>
                    <input type="number" class="form-control" name="bonuses" required>
                    @error('bonuses')
                    <div class="invalid-feedback">{{$message}}</div>
                    @enderror
                </div>

                
                <div class="mb-3">
                    <label for="" class="form-label">Pay date</label>
                    <input type="date" class="form-control date" name="pay_date" required>
                    @error('pay_date')
                    <div class="invalid-feedback">{{$message}}</div>
                    @enderror
                </div>
                

                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="{{ route('payrolls.index') }}" class="btn btn-secondary">Back to list</a>
            </form>
                
            </div>
        </div>

    </section>
</div>


@endsection