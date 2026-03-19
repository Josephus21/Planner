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
              <label class="form-label">Primary Company</label>

              <select name="company_id"
                      class="form-control @error('company_id') is-invalid @enderror"
                      required>
                <option value="">Select Primary Company</option>

                @foreach($companies as $company)
                  <option value="{{ $company->id }}"
                    {{ (string)old('company_id', $employee->company_id) === (string)$company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                  </option>
                @endforeach
              </select>

              @error('company_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Assigned Companies</label>

              @php
                $selectedCompanies = old(
                    'company_ids',
                    isset($employee) ? $employee->companies->pluck('id')->toArray() : []
                );
              @endphp

              <div class="row">
                @foreach($companies as $company)
                  <div class="col-md-4 mb-2">
                    <div class="form-check">
                      <input type="checkbox"
                             class="form-check-input"
                             name="company_ids[]"
                             value="{{ $company->id }}"
                             id="company_{{ $company->id }}"
                             {{ in_array($company->id, $selectedCompanies) ? 'checked' : '' }}>

                      <label class="form-check-label" for="company_{{ $company->id }}">
                        {{ $company->name }}
                      </label>
                    </div>
                  </div>
                @endforeach
              </div>
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
              <label class="form-label">Salary Type</label>
              <select name="salary_type"
                      id="salary_type"
                      class="form-control @error('salary_type') is-invalid @enderror"
                      required>
                <option value="">Select Salary Type</option>
                <option value="monthly" {{ old('salary_type', $employee->salary_type) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="daily" {{ old('salary_type', $employee->salary_type) === 'daily' ? 'selected' : '' }}>Daily</option>
              </select>
              @error('salary_type')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label" id="salary-label">Salary</label>
              <input type="number"
                     step="0.01"
                     min="0"
                     class="form-control @error('salary') is-invalid @enderror"
                     name="salary"
                     id="salary"
                     value="{{ old('salary', $employee->salary) }}"
                     required>
              <small class="text-muted" id="salary-help">Enter employee salary amount.</small>
              @error('salary')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <hr>
<h5 class="mb-2">Deductions</h5>
<p class="text-muted mb-3">Select deductions for this employee and configure how they will be deducted.</p>

<div class="table-responsive">
  <table class="table table-bordered">
    <thead>
      <tr>
        <th style="width: 25%">Deduction Type</th>
        <th style="width: 55%">Setup</th>
        <th style="width: 20%" class="text-center">Active</th>
      </tr>
    </thead>
    <tbody>
    @forelse($deductionTypes as $dt)
      @php
        $existing = $existingDeductionMap->get($dt->id);

        $enabledOld = old("deductions.{$dt->id}.enabled");
        $enabled = is_null($enabledOld)
            ? (bool) $existing
            : (bool) $enabledOld;

        $amount = old("deductions.{$dt->id}.amount", $existing->amount ?? '');
        $totalAmount = old("deductions.{$dt->id}.total_amount", $existing->total_amount ?? '');
        $installmentTerms = old("deductions.{$dt->id}.installment_terms", $existing->installment_terms ?? '');
        $remainingTerms = old("deductions.{$dt->id}.remaining_terms", $existing->remaining_terms ?? '');
        $remainingBalance = old("deductions.{$dt->id}.remaining_balance", $existing->remaining_balance ?? '');
        $payrollPeriodId = old("deductions.{$dt->id}.payroll_period_id", $existing->payroll_period_id ?? '');

        $isActiveOld = old("deductions.{$dt->id}.is_active");
        $isActive = is_null($isActiveOld)
            ? (bool)($existing->is_active ?? true)
            : (bool)$isActiveOld;
      @endphp

      <tr>
        <td>
          <div class="form-check">
            <input class="form-check-input deduction-toggle"
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
          {{-- Default amount field --}}
          <div class="mb-2">
            <label class="form-label mb-1">Amount per payroll</label>
            <input type="number"
                   step="0.01"
                   min="0"
                   class="form-control"
                   name="deductions[{{ $dt->id }}][amount]"
                   value="{{ $amount }}"
                   placeholder="0.00">
          </div>

          {{-- Loan / Installment --}}
          @if(in_array($dt->code, ['LOAN', 'INST']))
            <div class="row">
              <div class="col-md-6 mb-2">
                <label class="form-label mb-1">Total Amount</label>
                <input type="number"
                       step="0.01"
                       min="0"
                       class="form-control"
                       name="deductions[{{ $dt->id }}][total_amount]"
                       value="{{ $totalAmount }}"
                       placeholder="e.g. 12000.00">
              </div>

              <div class="col-md-6 mb-2">
                <label class="form-label mb-1">Payment Terms (No. of Payrolls)</label>
                <input type="number"
                       min="1"
                       class="form-control"
                       name="deductions[{{ $dt->id }}][installment_terms]"
                       value="{{ $installmentTerms }}"
                       placeholder="e.g. 12">
              </div>

              <div class="col-md-6 mb-2">
                <label class="form-label mb-1">Remaining Terms</label>
                <input type="number"
                       min="0"
                       class="form-control"
                       name="deductions[{{ $dt->id }}][remaining_terms]"
                       value="{{ $remainingTerms }}"
                       placeholder="e.g. 12">
              </div>

              <div class="col-md-6 mb-2">
                <label class="form-label mb-1">Remaining Balance</label>
                <input type="number"
                       step="0.01"
                       min="0"
                       class="form-control"
                       name="deductions[{{ $dt->id }}][remaining_balance]"
                       value="{{ $remainingBalance }}"
                       placeholder="e.g. 12000.00">
              </div>
            </div>

            <small class="text-muted">
              Example: total amount 12,000 / 12 payrolls / 1,000 per payroll.
            </small>
          @endif

          {{-- Scheduled deductions --}}
          @if(in_array($dt->code, ['CA', 'INS', 'OTH']))
            <div class="mb-2 mt-2">
              <label class="form-label mb-1">Deduct On Payroll Period</label>
              <select name="deductions[{{ $dt->id }}][payroll_period_id]" class="form-control">
                <option value="">Select payroll period</option>
                @foreach($payrollPeriods as $period)
                  <option value="{{ $period->id }}"
                    {{ (string)$payrollPeriodId === (string)$period->id ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::parse($period->date_from)->format('M d, Y') }}
                    -
                    {{ \Carbon\Carbon::parse($period->date_to)->format('M d, Y') }}
                  </option>
                @endforeach
              </select>
            </div>
            <small class="text-muted">
              This deduction will apply only to the selected payroll period.
            </small>
          @endif

          {{-- Recurring info --}}
          @if(in_array($dt->code, ['SSS', 'PHIC', 'PAGIBIG']))
            <small class="text-muted d-block mt-2">
              @if($dt->code === 'SSS')
                Automatically deducted on the second payroll of the month.
              @elseif(in_array($dt->code, ['PHIC', 'PAGIBIG']))
                Automatically deducted on the first payroll of the month.
              @endif
            </small>
          @endif
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

            <button type="submit" class="btn btn-primary">Update Employee</button>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back to list</a>
          </form>

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

        </div>
      </div>
    </section>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const salaryType = document.getElementById('salary_type');
    const salaryLabel = document.getElementById('salary-label');
    const salaryHelp = document.getElementById('salary-help');
    const salaryInput = document.getElementById('salary');

    function updateSalaryUI() {
        if (!salaryType || !salaryLabel || !salaryHelp || !salaryInput) return;

        if (salaryType.value === 'monthly') {
            salaryLabel.textContent = 'Monthly Salary';
            salaryHelp.textContent = 'Enter the employee monthly salary.';
            salaryInput.placeholder = 'e.g. 18000.00';
        } else if (salaryType.value === 'daily') {
            salaryLabel.textContent = 'Daily Rate';
            salaryHelp.textContent = 'Enter the employee daily rate.';
            salaryInput.placeholder = 'e.g. 650.00';
        } else {
            salaryLabel.textContent = 'Salary';
            salaryHelp.textContent = 'Enter employee salary amount.';
            salaryInput.placeholder = '';
        }
    }

    if (salaryType) {
        salaryType.addEventListener('change', updateSalaryUI);
    }

    updateSalaryUI();
});
</script>
@endsection