<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Services\PayrollGenerator;

class PayrollController extends Controller
{
    /**
     * Payroll Index (by period)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $myEmployeeId = $user->employee_id ? (int) $user->employee_id : null;

        if (!$myEmployeeId) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $me = DB::table('employees as e')
            ->leftJoin('roles as r', 'r.id', '=', 'e.role_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->where('e.id', $myEmployeeId)
            ->select([
                'e.id',
                'e.company_id',
                'e.department_id',
                'r.title as role_title',
                'd.name as department_title',
            ])
            ->first();

        if (!$me) {
            return back()->withErrors([
                'employee' => 'Employee record not found.',
            ]);
        }

        $roleNorm = Str::lower(trim((string) $me->role_title));
        $deptNorm = Str::lower(trim((string) $me->department_title));

        $myCompanyIds = DB::table('employee_companies')
            ->where('employee_id', $myEmployeeId)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->push((int) $me->company_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $assignedCompanies = DB::table('companies')
            ->whereIn('id', $myCompanyIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $isDeveloper = $roleNorm === 'developer';
        $isHrDept = in_array($deptNorm, ['hr', 'human resources', 'human resource']);
        $isCompanyViewer = in_array($roleNorm, ['admin', 'manager']) || $isHrDept;

        $canViewAllCompanies = $isDeveloper;
        $canViewCompany = $isCompanyViewer;

        if ($canViewAllCompanies) {
            $viewScope = 'all_companies';
        } elseif ($canViewCompany) {
            $viewScope = 'assigned_companies';
        } else {
            $viewScope = 'self';
        }

        $periods = PayrollPeriod::orderByDesc('date_from')->get();

        if ($request->filled('period_id')) {
            $selectedPeriod = PayrollPeriod::findOrFail($request->period_id);
        } else {
            $selectedPeriod = PayrollPeriod::orderByDesc('date_from')->first();
        }

        $selectedCompanyId = $request->filled('company_id')
            ? (int) $request->company_id
            : null;

        if ($selectedPeriod) {
            $query = Payroll::with(['employee.company', 'items', 'period'])
                ->where('payroll_period_id', $selectedPeriod->id)
                ->join('employees as e', 'e.id', '=', 'payrolls.employee_id')
                ->leftJoin('companies as c', 'c.id', '=', 'e.company_id');

            if ($canViewAllCompanies) {
                if (!is_null($selectedCompanyId)) {
                    $query->where('e.company_id', $selectedCompanyId);
                }
            } elseif ($canViewCompany) {
                $query->whereIn('e.company_id', $myCompanyIds);

                if (!is_null($selectedCompanyId) && in_array($selectedCompanyId, $myCompanyIds)) {
                    $query->where('e.company_id', $selectedCompanyId);
                } else {
                    $selectedCompanyId = null;
                }
            } else {
                $query->where('payrolls.employee_id', $myEmployeeId);
                $selectedCompanyId = null;
            }

            $payrolls = $query
                ->select('payrolls.*')
                ->orderByDesc('payrolls.net_pay')
                ->get();
        } else {
            $payrolls = collect();
        }

        return view('payrolls.index', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'payrolls' => $payrolls,
            'assignedCompanies' => $assignedCompanies,
            'selectedCompanyId' => $selectedCompanyId,
            'canViewAll' => $canViewAllCompanies,
            'canViewCompany' => $canViewCompany,
            'viewScope' => $viewScope,
            'roleTitle' => $me->role_title,
        ]);
    }

    /**
     * Generate / Recompute payroll
     */
    public function generate(Request $request, PayrollGenerator $generator)
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $period = PayrollPeriod::firstOrCreate(
            [
                'date_from' => $validated['date_from'],
                'date_to'   => $validated['date_to'],
            ],
            [
                'status' => 'draft',
            ]
        );

        if ($period->status === 'posted') {
            return redirect()
                ->route('payrolls.index', ['period_id' => $period->id])
                ->withErrors([
                    'period' => 'This payroll period is already POSTED and cannot be recomputed.',
                ]);
        }

        $generator->generate($period);

        return redirect()
            ->route('payrolls.index', ['period_id' => $period->id])
            ->with('success', 'Payroll generated successfully.');
    }

    /**
     * View single payroll
     */
    public function show(Payroll $payroll)
    {
        $user = auth()->user();
        $myEmployeeId = $user->employee_id ? (int) $user->employee_id : null;

        if (!$myEmployeeId) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $me = DB::table('employees as e')
            ->leftJoin('roles as r', 'r.id', '=', 'e.role_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->where('e.id', $myEmployeeId)
            ->select([
                'e.id',
                'e.company_id',
                'r.title as role_title',
                'd.name as department_title',
            ])
            ->first();

        if (!$me) {
            return back()->withErrors([
                'employee' => 'Employee record not found.',
            ]);
        }

        $roleNorm = Str::lower(trim((string) $me->role_title));
        $deptNorm = Str::lower(trim((string) $me->department_title));

        $myCompanyIds = DB::table('employee_companies')
            ->where('employee_id', $myEmployeeId)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->push((int) $me->company_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $isDeveloper = $roleNorm === 'developer';
        $isHrDept = in_array($deptNorm, ['hr', 'human resources', 'human resource']);
        $isCompanyViewer = in_array($roleNorm, ['admin', 'manager']) || $isHrDept;

        $payroll->load(['employee.company', 'items', 'period']);

        if (!$payroll->employee) {
            return back()->withErrors([
                'payroll' => 'Payroll employee record not found.',
            ]);
        }

        if (!$isDeveloper) {
            if ($isCompanyViewer) {
                if (!in_array((int) $payroll->employee->company_id, $myCompanyIds)) {
                    abort(403, 'You are not allowed to view this payroll record.');
                }
            } else {
                if ((int) $payroll->employee_id !== $myEmployeeId) {
                    abort(403, 'You are not allowed to view this payroll record.');
                }
            }
        }

        return view('payrolls.show', compact('payroll'));
    }

    /**
     * Post payroll period
     */
    public function postPeriod(PayrollPeriod $period)
    {
        $user = auth()->user();
        $myEmployeeId = $user->employee_id ? (int) $user->employee_id : null;

        if (!$myEmployeeId) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $me = DB::table('employees as e')
            ->leftJoin('roles as r', 'r.id', '=', 'e.role_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->where('e.id', $myEmployeeId)
            ->select([
                'e.id',
                'e.company_id',
                'r.title as role_title',
                'd.name as department_title',
            ])
            ->first();

        if (!$me) {
            return back()->withErrors([
                'employee' => 'Employee record not found.',
            ]);
        }

        $roleNorm = Str::lower(trim((string) $me->role_title));
        $deptNorm = Str::lower(trim((string) $me->department_title));

        $isDeveloper = $roleNorm === 'developer';
        $isHrDept = in_array($deptNorm, ['hr', 'human resources', 'human resource']);
        $isCompanyViewer = in_array($roleNorm, ['admin', 'manager']) || $isHrDept;

        if (!$isDeveloper && !$isCompanyViewer) {
            abort(403, 'You are not allowed to post this payroll period.');
        }

        $hasPayroll = Payroll::where('payroll_period_id', $period->id)->exists();

        if (!$hasPayroll) {
            return back()->withErrors([
                'period' => 'No payroll found for this period.',
            ]);
        }

        if ($period->status === 'posted') {
            return redirect()
                ->route('payrolls.index', ['period_id' => $period->id])
                ->with('success', 'Payroll period is already posted.');
        }

        $period->update([
            'status' => 'posted',
        ]);

        return redirect()
            ->route('payrolls.index', ['period_id' => $period->id])
            ->with('success', 'Payroll period posted successfully.');
    }

    /**
     * Print all payrolls in selected period
     */
    public function printAll(Request $request, PayrollPeriod $period)
    {
        $user = auth()->user();
        $myEmployeeId = $user->employee_id ? (int) $user->employee_id : null;

        if (!$myEmployeeId) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $me = DB::table('employees as e')
            ->leftJoin('roles as r', 'r.id', '=', 'e.role_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->where('e.id', $myEmployeeId)
            ->select([
                'e.id',
                'e.company_id',
                'r.title as role_title',
                'd.name as department_title',
            ])
            ->first();

        if (!$me) {
            return back()->withErrors([
                'employee' => 'Employee record not found.',
            ]);
        }

        $roleNorm = Str::lower(trim((string) $me->role_title));
        $deptNorm = Str::lower(trim((string) $me->department_title));

        $myCompanyIds = DB::table('employee_companies')
            ->where('employee_id', $myEmployeeId)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->push((int) $me->company_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $isDeveloper = $roleNorm === 'developer';
        $isHrDept = in_array($deptNorm, ['hr', 'human resources', 'human resource']);
        $isCompanyViewer = in_array($roleNorm, ['admin', 'manager']) || $isHrDept;

        $selectedCompanyId = $request->filled('company_id')
            ? (int) $request->company_id
            : null;

        $query = Payroll::with(['employee.company', 'items', 'period'])
            ->where('payroll_period_id', $period->id)
            ->join('employees as e', 'e.id', '=', 'payrolls.employee_id');

        if ($isDeveloper) {
            if (!is_null($selectedCompanyId)) {
                $query->where('e.company_id', $selectedCompanyId);
            }
        } elseif ($isCompanyViewer) {
            $query->whereIn('e.company_id', $myCompanyIds);

            if (!is_null($selectedCompanyId) && in_array($selectedCompanyId, $myCompanyIds)) {
                $query->where('e.company_id', $selectedCompanyId);
            }
        } else {
            $query->where('payrolls.employee_id', $myEmployeeId);
        }

        $payrolls = $query
            ->select('payrolls.*')
            ->orderBy('e.company_id')
            ->orderBy('e.fullname')
            ->get();

        if ($payrolls->isEmpty()) {
            return back()->withErrors([
                'print' => 'No payroll found to print for this period.',
            ]);
        }

        return view('payrolls.print-all', [
            'period' => $period,
            'payrolls' => $payrolls,
        ]);
    }

    /**
     * Delete payroll record
     */
    public function destroy(Payroll $payroll)
    {
        $user = auth()->user();
        $myEmployeeId = $user->employee_id ? (int) $user->employee_id : null;

        if (!$myEmployeeId) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $me = DB::table('employees as e')
            ->leftJoin('roles as r', 'r.id', '=', 'e.role_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->where('e.id', $myEmployeeId)
            ->select([
                'e.id',
                'e.company_id',
                'r.title as role_title',
                'd.name as department_title',
            ])
            ->first();

        if (!$me) {
            return back()->withErrors([
                'employee' => 'Employee record not found.',
            ]);
        }

        $roleNorm = Str::lower(trim((string) $me->role_title));
        $deptNorm = Str::lower(trim((string) $me->department_title));

        $myCompanyIds = DB::table('employee_companies')
            ->where('employee_id', $myEmployeeId)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->push((int) $me->company_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $isDeveloper = $roleNorm === 'developer';
        $isHrDept = in_array($deptNorm, ['hr', 'human resources', 'human resource']);
        $isCompanyViewer = in_array($roleNorm, ['admin', 'manager']) || $isHrDept;

        $payroll->load(['employee', 'period']);

        if (!$isDeveloper) {
            if ($isCompanyViewer) {
                if (!$payroll->employee || !in_array((int) $payroll->employee->company_id, $myCompanyIds)) {
                    abort(403, 'You are not allowed to delete this payroll record.');
                }
            } else {
                abort(403, 'You are not allowed to delete this payroll record.');
            }
        }

        if ($payroll->period && $payroll->period->status === 'posted') {
            return back()->withErrors([
                'delete' => 'Cannot delete payroll from a POSTED period.'
            ]);
        }

        $periodId = $payroll->payroll_period_id;

        if (method_exists($payroll, 'items')) {
            $payroll->items()->delete();
        }

        $payroll->delete();

        return redirect()
            ->route('payrolls.index', ['period_id' => $periodId])
            ->with('success', 'Payroll record deleted successfully.');
    }
}