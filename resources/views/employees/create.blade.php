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
    <label for="company_id" class="form-label">Company</label>
    <select name="company_id" id="company_id" class="form-control" required>
        <option value="">Select Company</option>
        @foreach($companies as $company)
            <option value="{{ $company->id }}"
                {{ old('company_id', $employee->company_id ?? '') == $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
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
              <label class="form-label">Salary</label>
              <input type="number"
                     class="form-control @error('salary') is-invalid @enderror"
                     name="salary"
                     value="{{ old('salary') }}"
                     required>
              @error('salary')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- ===================== USER ACCOUNT (OPTIONAL) ===================== --}}
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
        <th style="width: 40%">Deduction Type</th>
        <th style="width: 30%">Amount (per payroll)</th>
        <th style="width: 30%">Active</th>
      </tr>
    </thead>
    <tbody>
      @forelse($deductionTypes as $dt)
        @php
          $oldEnabled = old("deductions.{$dt->id}.enabled");
          $oldAmount  = old("deductions.{$dt->id}.amount");
        @endphp
        <tr>
          <td>
            <div class="form-check">
              <input class="form-check-input"
                     type="checkbox"
                     id="ded_{{ $dt->id }}"
                     name="deductions[{{ $dt->id }}][enabled]"
                     value="1"
                     {{ $oldEnabled ? 'checked' : '' }}>
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
                   value="{{ $oldAmount ?? '' }}"
                   placeholder="0.00">
            <small class="text-muted">Example: 500.00</small>
          </td>

          <td class="text-center">
            <input type="hidden" name="deductions[{{ $dt->id }}][is_active]" value="0">
            <input type="checkbox"
                   name="deductions[{{ $dt->id }}][is_active]"
                   value="1"
                   {{ old("deductions.{$dt->id}.is_active", 1) ? 'checked' : '' }}>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="3" class="text-center text-muted">No deduction types found. Add types first.</td>
        </tr>
      @endforelse
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

    function syncDefaults() {
        // If user fields are visible and user_name empty, copy employee fullname
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

    if (cb) cb.addEventListener('change', toggleUserFields);
    if (employeeFullname) employeeFullname.addEventListener('blur', syncDefaults);

    toggleUserFields();
});
</script>
@endsection