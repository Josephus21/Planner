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
              <li class="breadcrumb-item active" aria-current="page">New</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>

    <section class="section">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title">Create</h5>
        </div>

        <div class="card-body">
          <form action="{{ route('employees.store') }}" method="POST">
            @csrf

            <div class="mb-3">
              <label class="form-label">Fullname</label>
              <input type="text"
                     class="form-control @error('fullname') is-invalid @enderror"
                     name="fullname"
                     value="{{ old('fullname') }}"
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
                     value="{{ old('email') }}"
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
                     value="{{ old('phone_number') }}"
                     required>
              @error('phone_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea name="address"
                        id="address"
                        class="form-control @error('address') is-invalid @enderror"
                        placeholder="Enter address...">{{ old('address') }}</textarea>
              @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Birth date</label>
              <input type="date"
                     class="form-control @error('birth_date') is-invalid @enderror"
                     name="birth_date"
                     value="{{ old('birth_date') }}"
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
                          {{ (string) old('company_id', '') === (string) $company->id ? 'selected' : '' }}>
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
                  $selectedCompanies = old('company_ids', []);
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

              @error('company_ids')
                  <div class="text-danger small">{{ $message }}</div>
              @enderror

              @error('company_ids.*')
                  <div class="text-danger small">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Hire date</label>
              <input type="date"
                     class="form-control @error('hire_date') is-invalid @enderror"
                     name="hire_date"
                     value="{{ old('hire_date') }}"
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
                    {{ old('department_id') == $department->id ? 'selected' : '' }}>
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
                    {{ old('role_id') == $role->id ? 'selected' : '' }}>
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
                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
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
                <option value="monthly" {{ old('salary_type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="daily" {{ old('salary_type') === 'daily' ? 'selected' : '' }}>Daily</option>
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
                     value="{{ old('salary') }}"
                     required>
              <small class="text-muted" id="salary-help">Enter employee salary amount.</small>
              @error('salary')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <hr>

            <div class="mt-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="create_user" id="create_user"
                       value="1" {{ old('create_user') ? 'checked' : '' }}>
                <label class="form-check-label" for="create_user">
                  Create login account for this employee (uses employee email as user email)
                </label>
              </div>

              <div id="user-fields" class="row mt-3" style="{{ old('create_user') ? '' : 'display:none;' }}">
                <div class="col-md-6">
                  <label class="form-label">User Name</label>
                  <input type="text"
                         class="form-control @error('user_name') is-invalid @enderror"
                         name="user_name"
                         value="{{ old('user_name') }}"
                         placeholder="Leave blank to use employee fullname">
                  @error('user_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6 mt-3">
                  <label class="form-label">Temporary Password</label>
                  <input type="text"
                         class="form-control @error('user_password') is-invalid @enderror"
                         name="user_password"
                         value="{{ old('user_password') }}"
                         placeholder="Leave blank to auto-generate">
                  @error('user_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  <small class="text-muted">If blank, the system will generate one.</small>
                </div>

                <div class="col-12 mt-2">
                  <small class="text-muted">
                    Login Email will be the same as Employee Email.
                  </small>
                </div>
              </div>
            </div>

            <hr>

            <h5 class="mb-2">Deductions</h5>
            <p class="text-muted mb-3">Select deductions for this employee and set the amount per payroll period.</p>

            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th style="width: 30%">Deduction Type</th>
                    <th style="width: 50%">Setup</th>
                    <th style="width: 20%">Active</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($deductionTypes as $type)
                    @php
                        $checked = old("deductions.{$type->id}.selected") ? true : false;
                    @endphp

                    <tr>
                      <td>
                        <div class="form-check">
                          <input
                              class="form-check-input deduction-toggle"
                              type="checkbox"
                              name="deductions[{{ $type->id }}][selected]"
                              value="1"
                              id="deduction_{{ $type->id }}"
                              {{ $checked ? 'checked' : '' }}
                          >
                          <label class="form-check-label" for="deduction_{{ $type->id }}">
                              {{ $type->name }}
                          </label>
                        </div>
                      </td>

                      <td>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control mb-2"
                            name="deductions[{{ $type->id }}][amount]"
                            value="{{ old("deductions.{$type->id}.amount", 0) }}"
                            placeholder="Amount per payroll"
                        >

                        @if(in_array($type->code, ['LOAN', 'INST']))
                          <input
                              type="number"
                              step="0.01"
                              min="0"
                              class="form-control mb-2"
                              name="deductions[{{ $type->id }}][total_amount]"
                              value="{{ old("deductions.{$type->id}.total_amount", 0) }}"
                              placeholder="Total amount"
                          >

                          <input
                              type="number"
                              min="1"
                              class="form-control mb-2"
                              name="deductions[{{ $type->id }}][installment_terms]"
                              value="{{ old("deductions.{$type->id}.installment_terms", 1) }}"
                              placeholder="How many payrolls?"
                          >
                        @endif

                        @if(in_array($type->code, ['CA', 'INS', 'OTH']))
                          <select
                              name="deductions[{{ $type->id }}][payroll_period_id]"
                              class="form-control"
                          >
                              <option value="">Select payroll period</option>
                              @foreach($payrollPeriods as $period)
                                  <option
                                      value="{{ $period->id }}"
                                      {{ old("deductions.{$type->id}.payroll_period_id") == $period->id ? 'selected' : '' }}
                                  >
                                      {{ \Carbon\Carbon::parse($period->date_from)->format('M d, Y') }}
                                      -
                                      {{ \Carbon\Carbon::parse($period->date_to)->format('M d, Y') }}
                                  </option>
                              @endforeach
                          </select>
                        @endif
                      </td>

                      <td class="text-center">
                        <input
                            type="checkbox"
                            name="deductions[{{ $type->id }}][is_active]"
                            value="1"
                            {{ old("deductions.{$type->id}.is_active", 1) ? 'checked' : '' }}
                        >
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-primary">Create Employee</button>
              <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back to list</a>
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cb = document.getElementById('create_user');
    const userFields = document.getElementById('user-fields');

    const employeeFullname = document.querySelector('input[name="fullname"]');
    const userName = document.querySelector('input[name="user_name"]');

    const salaryType = document.getElementById('salary_type');
    const salaryLabel = document.getElementById('salary-label');
    const salaryHelp = document.getElementById('salary-help');
    const salaryInput = document.getElementById('salary');

    function syncDefaults() {
        if (cb && cb.checked) {
            if (userName && !userName.value && employeeFullname && employeeFullname.value) {
                userName.value = employeeFullname.value;
            }
        }
    }

    function toggleUserFields() {
        if (!cb || !userFields) return;
        userFields.style.display = cb.checked ? '' : 'none';
        syncDefaults();
    }

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

    if (cb) cb.addEventListener('change', toggleUserFields);
    if (employeeFullname) employeeFullname.addEventListener('blur', syncDefaults);
    if (salaryType) salaryType.addEventListener('change', updateSalaryUI);

    toggleUserFields();
    updateSalaryUI();
});
</script>
@endsection