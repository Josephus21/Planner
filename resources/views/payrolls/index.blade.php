@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Payroll</h3>
    <p class="text-subtitle text-muted">
        Semi-monthly payroll with automatic computation from attendance logs.

        @if(($viewScope ?? 'self') === 'all_companies')
            | <span class="badge bg-primary">Developer View: All Companies</span>
        @elseif(($viewScope ?? 'self') === 'assigned_companies')
            | <span class="badge bg-success">Company View: Assigned Companies Only</span>
        @else
            | <span class="badge bg-secondary">My Payroll Only</span>
        @endif
    </p>
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

    {{-- ===================== PERIOD SELECT + GENERATE ===================== --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Payroll Period</span>

            <div class="d-flex gap-2 flex-wrap">
                {{-- Create / Generate for custom range --}}
                <form action="{{ route('payrolls.generate') }}" method="POST" class="d-flex gap-2 flex-wrap">
                    @csrf

                    <div>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                               value="{{ old('date_from', request('date_from') ?? optional($selectedPeriod)->date_from) }}"
                               required>
                    </div>

                    <div>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                               value="{{ old('date_to', request('date_to') ?? optional($selectedPeriod)->date_to) }}"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">
                        ⚙️ Generate / Recompute
                    </button>
                </form>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('payrolls.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="period_id" class="form-label">Saved Period</label>
                    <select name="period_id" id="period_id" class="form-select form-select-sm">
                        <option value="">-- Select Saved Period --</option>
                        @foreach($periods as $p)
                            <option value="{{ $p->id }}"
                                {{ (string)($selectedPeriod->id ?? request('period_id')) === (string)$p->id ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($p->date_from)->format('M d, Y') }}
                                -
                                {{ \Carbon\Carbon::parse($p->date_to)->format('M d, Y') }}
                                ({{ strtoupper($p->status) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(($canViewAll ?? false) || ($canViewCompany ?? false))
                    <div class="col-md-4">
                        <label for="company_id" class="form-label">Company</label>
                        <select name="company_id" id="company_id" class="form-select form-select-sm">
                            <option value="">All Companies</option>
                            @foreach($assignedCompanies as $company)
                                <option value="{{ $company->id }}"
                                    {{ (string)$selectedCompanyId === (string)$company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        Filter
                    </button>
                </div>
            </form>

            <hr>

            @if($selectedPeriod)
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div>
                        <strong>Selected:</strong>
                        {{ \Carbon\Carbon::parse($selectedPeriod->date_from)->format('M d, Y') }}
                        -
                        {{ \Carbon\Carbon::parse($selectedPeriod->date_to)->format('M d, Y') }}
                    </div>

                    <div>
                        <strong>Status:</strong>
                        <span class="badge bg-{{ $selectedPeriod->status === 'posted' ? 'success' : 'secondary' }}">
                            {{ strtoupper($selectedPeriod->status) }}
                        </span>
                    </div>

                    @if(($canViewAll ?? false) || ($canViewCompany ?? false))
                        <div>
                            <strong>Company Filter:</strong>
                            @if($selectedCompanyId)
                                @php
                                    $selectedCompany = collect($assignedCompanies)->firstWhere('id', (int) $selectedCompanyId);
                                @endphp
                                <span class="badge bg-info text-dark">
                                    {{ $selectedCompany->name ?? 'Selected Company' }}
                                </span>
                            @else
                                <span class="badge bg-light text-dark">All Companies</span>
                            @endif
                        </div>
                    @endif

                    @if(Route::has('payroll_periods.post'))
                        <form action="{{ route('payroll_periods.post', $selectedPeriod->id) }}" method="POST">
                            @csrf
                            <button class="btn btn-success btn-sm"
                                onclick="return confirm('Post this payroll period? This usually means finalized.')">
                                ✅ Post Period
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <div class="text-muted">
                    Select a saved period or generate payroll using a date range above.
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== PAYROLL LIST ===================== --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Payroll Summary</span>

            <div class="d-flex gap-2">
                @if(Route::has('payrolls.export') && $selectedPeriod)
                    <a href="{{ route('payrolls.export', $selectedPeriod->id) }}" class="btn btn-outline-primary btn-sm">
                        ⬇️ Export
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="table1">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Company</th>
                            <th>Days Present</th>
                            <th>Minutes Late</th>
                            <th>Minutes Worked</th>
                            <th class="text-end">Gross</th>
                            <th class="text-end">Deductions</th>
                            <th class="text-end">Net</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($payrolls as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold">{{ $row->employee->fullname ?? 'N/A' }}</div>
                                <div class="text-muted small">
                                    Emp# {{ $row->employee->id ?? '-' }}
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ $row->employee->company->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>{{ $row->days_present }}</td>
                            <td>{{ $row->minutes_late }}</td>
                            <td>{{ $row->minutes_worked }}</td>

                            <td class="text-end">{{ number_format($row->gross_pay, 2) }}</td>
                            <td class="text-end">{{ number_format($row->total_deductions, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($row->net_pay, 2) }}</td>

                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if(Route::has('payrolls.show'))
                                        <a href="{{ route('payrolls.show', $row->id) }}" class="btn btn-info btn-sm">
                                            View
                                        </a>
                                    @endif

                                    <button type="button"
                                            class="btn btn-secondary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#breakdownModal{{ $row->id }}">
                                        Breakdown
                                    </button>
                                </div>

                                {{-- Breakdown Modal --}}
                                <div class="modal fade" id="breakdownModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Payroll Breakdown</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-2">
                                                    <strong>{{ $row->employee->fullname ?? 'Employee' }}</strong>
                                                </div>

                                                <div class="text-muted small mb-3">
                                                    Company: {{ $row->employee->company->name ?? 'N/A' }}
                                                </div>

                                                <div class="mb-3">
                                                    <div><strong>Gross:</strong> {{ number_format($row->gross_pay, 2) }}</div>
                                                    <div><strong>Total Deductions:</strong> {{ number_format($row->total_deductions, 2) }}</div>
                                                    <div><strong>Net:</strong> {{ number_format($row->net_pay, 2) }}</div>
                                                </div>

                                                <hr>

                                                @php
                                                    $items = $row->items ?? collect();
                                                    $earnings = $items->where('type','earning');
                                                    $deductions = $items->where('type','deduction');
                                                @endphp

                                                <h6>Earnings</h6>
                                                @if($earnings->count())
                                                    <ul class="list-group mb-3">
                                                        @foreach($earnings as $it)
                                                            <li class="list-group-item d-flex justify-content-between">
                                                                <span>{{ $it->name }}</span>
                                                                <span>{{ number_format($it->amount, 2) }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <div class="text-muted mb-3">No earnings items.</div>
                                                @endif

                                                <h6>Deductions</h6>
                                                @if($deductions->count())
                                                    <ul class="list-group">
                                                        @foreach($deductions as $it)
                                                            <li class="list-group-item d-flex justify-content-between">
                                                                <span>{{ $it->name }}</span>
                                                                <span>{{ number_format($it->amount, 2) }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <div class="text-muted">No deductions items.</div>
                                                @endif
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- end modal --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                No payroll found. Generate payroll by selecting a date range above.
                            </td>
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