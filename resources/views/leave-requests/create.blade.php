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
                    <h3>Leave Requests</h3>
                    <p class="text-subtitle text-muted">Handle leave requests data</p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                            <li class="breadcrumb-item" aria-current="page">Leave Requests</li>
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

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('leave-requests.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select name="employee_id" id="employee_id" class="form-control" required>
                                <option value="">-- Select Employee --</option>
                                @foreach ($employees as $employee)
                                    <option
                                        value="{{ $employee->id }}"
                                        data-fullname="{{ $employee->fullname }}"
                                        data-hire-date="{{ $employee->hire_date }}"
                                        data-sl-balance="{{ $employee->sick_leave_balance ?? 0 }}"
                                        data-vl-balance="{{ $employee->vacation_leave_balance ?? 0 }}"
                                        {{ old('employee_id') == $employee->id ? 'selected' : '' }}
                                    >
                                        {{ $employee->fullname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div id="employeeLeaveInfo" class="alert alert-light border small mb-0" style="display:none;"></div>
                        </div>

                        <div class="mb-3">
                            <label for="leave_type" class="form-label">Leave Type</label>
                            <select name="leave_type" id="leave_type" class="form-control" required>
                                <option value="">-- Select Leave Type --</option>
                                <option value="Sick Leave" {{ old('leave_type') == 'Sick Leave' ? 'selected' : '' }}>Sick Leave</option>
                                <option value="Vacation" {{ old('leave_type') == 'Vacation' ? 'selected' : '' }}>Vacation</option>
                                <option value="Birth Leave" {{ old('leave_type') == 'Birth Leave' ? 'selected' : '' }}>Birth Leave</option>
                            </select>
                            @error('leave_type')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="start_date" value="{{ old('start_date') }}" required>
                            @error('start_date')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="end_date" value="{{ old('end_date') }}" required>
                            @error('end_date')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>
                        <a href="{{ route('leave-requests.index') }}" class="btn btn-secondary">Back to list</a>
                    </form>

                </div>
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const employeeSelect = document.getElementById('employee_id');
    const leaveTypeSelect = document.getElementById('leave_type');
    const infoBox = document.getElementById('employeeLeaveInfo');

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function updateLeaveUI() {
        const selected = employeeSelect.options[employeeSelect.selectedIndex];

        const hireDate = selected?.dataset?.hireDate || '';
        const fullname = selected?.dataset?.fullname || '';
        const sickBalance = selected?.dataset?.slBalance || '0';
        const vacationBalance = selected?.dataset?.vlBalance || '0';

        const sickOption = [...leaveTypeSelect.options].find(o => o.value === 'Sick Leave');
        const vacationOption = [...leaveTypeSelect.options].find(o => o.value === 'Vacation');

        if (!selected || !selected.value || !hireDate) {
            infoBox.style.display = 'none';
            if (sickOption) sickOption.disabled = false;
            if (vacationOption) vacationOption.disabled = false;
            return;
        }

        const hired = new Date(hireDate);
        const eligibleDate = new Date(hired);
        eligibleDate.setFullYear(eligibleDate.getFullYear() + 1);

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        eligibleDate.setHours(0, 0, 0, 0);

        const eligible = today >= eligibleDate;

        if (sickOption) sickOption.disabled = !eligible;
        if (vacationOption) vacationOption.disabled = !eligible;

        if (!eligible && (leaveTypeSelect.value === 'Sick Leave' || leaveTypeSelect.value === 'Vacation')) {
            leaveTypeSelect.value = '';
        }

        infoBox.style.display = 'block';

        infoBox.innerHTML = `
            <div><strong>Employee:</strong> ${fullname}</div>
            <div><strong>Hire Date:</strong> ${formatDate(hireDate)}</div>
            <div><strong>Sick Leave Balance:</strong> ${parseFloat(sickBalance).toFixed(2)}</div>
            <div><strong>Vacation Leave Balance:</strong> ${parseFloat(vacationBalance).toFixed(2)}</div>
            <div class="mt-2">
                ${
                    eligible
                        ? `<span class="text-success"><strong>Eligible</strong> for Sick Leave and Vacation starting ${formatDate(eligibleDate)}.</span>`
                        : `<span class="text-danger"><strong>Not yet eligible</strong> for Sick Leave and Vacation. Available on ${formatDate(eligibleDate)}.</span>`
                }
            </div>
        `;
    }

    employeeSelect.addEventListener('change', updateLeaveUI);
    updateLeaveUI();
});
</script>
@endsection