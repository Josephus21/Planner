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
          <p class="text-subtitle text-muted">Handle Employee data</p>
        </div>
        <div class="col-12 col-md-6 order-md-2 order-first">
          <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
              <li class="breadcrumb-item" aria-current="page">Employees</li>
              <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>

    <section class="section">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title">Edit Employee</h5>
        </div>

        <div class="card-body">

          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          @php
            // Map existing deductions by deduction_type_id for easy lookup
            // Ensure controller loads: $employee->load('deductions');
            $existingDeductionMap = ($employee->deductions ?? collect())->keyBy('deduction_type_id');
          @endphp

          <form action="{{ route('employees.update', $employee->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
              <label class="form-label">Fullname</label>
              <input type="text"
                     class="form-control @error('fullname') is-invalid @enderror"
                     name="fullname"
                     value="{{ old('fullname', $employee->fullname) }}"
                     required>
              @error('fullname')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email"
                     class="form-control @error('email') is-invalid @enderror"
                     name="email"
                     value="{{ old('email', $employee->email) }}"
                     required>
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Phone number</label>
              <input type="text"
                     class="form-control @error('phone_number') is-invalid @enderror"
                     name="phone_number"
                     value="{{ old('phone_number', $employee->phone_number) }}"
                     required>
              @error('phone_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea name="address"
                        id="address"
                        class="form-control @error('address') is-invalid @enderror">{{ old('address', $employee->address) }}</textarea>
              @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Birth date</label>
              <input type="date"
                     class="form-control @error('birth_date') is-invalid @enderror"
                     name="birth_date"
                     value="{{ old('birth_date', $employee->birth_date) }}"
                     required>
              @error('birth_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>



            <div class="mb-3">
              <label class="form-label">Hire date</label>
              <input type="date"
                     class="form-control @error('hire_date') is-invalid @enderror"
                     name="hire_date"
                     value="{{ old('hire_date', $employee->hire_date) }}"
                     required>
              @error('hire_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Department</label>
              <select name="department_id"
                      class="form-control @error('department_id') is-invalid @enderror"
                      required>
                <option value="">Select a Department</option>
                @foreach ($departments as $department)
                  <option value="{{ $department->id }}"
                    {{ (string)old('department_id', $employee->department_id) === (string)$department->id ? 'selected' : '' }}>
                    {{ $department->name }}
                  </option>
                @endforeach
              </select>
              @error('department_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Role</label>
              <select name="role_id"
                      class="form-control @error('role_id') is-invalid @enderror"
                      required>
                <option value="">Select a Role</option>
                @foreach ($roles as $role)
                  <option value="{{ $role->id }}"
                    {{ (string)old('role_id', $employee->role_id) === (string)$role->id ? 'selected' : '' }}>
                    {{ $role->title }}
                  </option>
                @endforeach
              </select>
              @error('role_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="status"
                      class="form-control @error('status') is-invalid @enderror"
                      required>
                <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active</option>
              </select>
              @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Salary</label>
              <input type="number"
                     class="form-control @error('salary') is-invalid @enderror"
                     name="salary"
                     value="{{ old('salary', $employee->salary) }}"
                     required>
              @error('salary')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- ===================== NEW: DEDUCTIONS ===================== --}}
            <hr>
            <h5 class="mb-2">Deductions</h5>
            <p class="text-muted mb-3">Select deductions for this employee and set the amount per payroll period.</p>

            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th style="width: 45%">Deduction Type</th>
                    <th style="width: 35%">Amount (per payroll)</th>
                    <th style="width: 20%" class="text-center">Active</th>
                  </tr>
                </thead>
                <tbody>
                @forelse($deductionTypes as $dt)
                  @php
                    $existing = $existingDeductionMap->get($dt->id);

                    // whether enabled
                    $enabledOld = old("deductions.{$dt->id}.enabled");
                    $enabled = is_null($enabledOld)
                        ? (bool) $existing
                        : (bool) $enabledOld;

                    // amount
                    $amountOld = old("deductions.{$dt->id}.amount");
                    $amount = !is_null($amountOld)
                        ? $amountOld
                        : ($existing->amount ?? '');

                    // active
                    $isActiveOld = old("deductions.{$dt->id}.is_active");
                    $isActive = is_null($isActiveOld)
                        ? (bool)($existing->is_active ?? true)
                        : (bool)$isActiveOld;
                  @endphp

                  <tr>
                    <td>
                      <div class="form-check">
                        <input class="form-check-input"
                               type="checkbox"
                               id="ded_{{ $dt->id }}"
                               name="deductions[{{ $dt->id }}][enabled]"
                               value="1"
                               {{ $enabled ? 'checked' : '' }}>
                        <label class="form-check-label" for="ded_{{ $dt->id }}">
                          {{ $dt->name }}
                        </label>
                      </div>
                    </td>

                    <td>
                      <input type="number"
                             step="0.01"
                             min="0"
                             class="form-control"
                             name="deductions[{{ $dt->id }}][amount]"
                             value="{{ $amount }}"
                             placeholder="0.00">
                      <small class="text-muted">Example: 500.00</small>
                    </td>

                    <td class="text-center">
                      <input type="hidden" name="deductions[{{ $dt->id }}][is_active]" value="0">
                      <input type="checkbox"
                             name="deductions[{{ $dt->id }}][is_active]"
                             value="1"
                             {{ $isActive ? 'checked' : '' }}>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted">
                      No deduction types found. Please add types first.
                    </td>
                  </tr>
                @endforelse
                </tbody>
              </table>
            </div>
            {{-- ===================== END DEDUCTIONS ===================== --}}

            <button type="submit" class="btn btn-primary">Update Employee</button>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back to list</a>
          </form>

          {{-- ===================== SCHEDULE ASSIGNMENT (your existing part) ===================== --}}
          <hr>
          <h5>Schedule Assignment</h5>

          @if($currentScheduleAssignment && $currentScheduleAssignment->schedule)
            <div class="alert alert-info">
              Current: <strong>{{ $currentScheduleAssignment->schedule->name }}</strong>
              ({{ $currentScheduleAssignment->schedule->start_time }} - {{ $currentScheduleAssignment->schedule->end_time }})
            </div>
          @endif

          <form action="{{ route('employees.assignSchedule', $employee->id) }}" method="POST">
            @csrf

            <div class="row">
              <div class="col-md-6">
                <label class="form-label">Schedule</label>
                <select name="schedule_id" class="form-control" required>
                  <option value="">Select Schedule</option>
                  @foreach($schedules as $s)
                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->start_time }}-{{ $s->end_time }})</option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Effective From</label>
                <input type="date" name="effective_from" class="form-control" value="{{ date('Y-m-d') }}" required>
              </div>

              <div class="col-md-3">
                <label class="form-label">Effective To (optional)</label>
                <input type="date" name="effective_to" class="form-control">
              </div>
            </div>

            <button class="btn btn-primary mt-3">Apply Schedule</button>
          </form>
          {{-- ===================== END SCHEDULE ASSIGNMENT ===================== --}}

        </div>
      </div>
    </section>
  </div>
</div>
@endsection