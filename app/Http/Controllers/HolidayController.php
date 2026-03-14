<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Employee;
use App\Models\Company;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $myEmployee = Employee::with(['role', 'companies'])->find($user->employee_id);

        if (!$myEmployee) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $roleTitle = strtolower(trim($myEmployee->role->title ?? ''));
        $accessibleCompanyIds = $this->getAccessibleCompanyIds($myEmployee);

        $query = Holiday::with('company')->latest('holiday_date');

        if ($roleTitle !== 'developer') {
            $query->where(function ($q) use ($accessibleCompanyIds) {
                $q->whereNull('company_id')
                  ->orWhereIn('company_id', $accessibleCompanyIds);
            });
        }

        $holidays = $query->get();

        return view('holidays.index', compact('holidays'));
    }

    public function create()
    {
        $user = auth()->user();
        $myEmployee = Employee::with(['role', 'companies'])->find($user->employee_id);

        if (!$myEmployee) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $roleTitle = strtolower(trim($myEmployee->role->title ?? ''));
        $accessibleCompanyIds = $this->getAccessibleCompanyIds($myEmployee);

        $companies = $roleTitle === 'developer'
            ? Company::where('status', 'active')->orderBy('name')->get()
            : Company::whereIn('id', $accessibleCompanyIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

        return view('holidays.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $myEmployee = Employee::with(['role', 'companies'])->find($user->employee_id);

        if (!$myEmployee) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $roleTitle = strtolower(trim($myEmployee->role->title ?? ''));
        $accessibleCompanyIds = $this->getAccessibleCompanyIds($myEmployee);

        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'holiday_date' => 'required|date',
            'type' => 'required|in:regular,special',
            'is_recurring' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($roleTitle !== 'developer' && !empty($data['company_id'])) {
            if (!in_array((int) $data['company_id'], $accessibleCompanyIds)) {
                abort(403, 'Unauthorized company selection.');
            }
        }

        $data['is_recurring'] = $request->boolean('is_recurring');
        $data['is_active'] = $request->boolean('is_active', true);

        Holiday::create($data);

        return redirect()
            ->route('holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    public function edit(Holiday $holiday)
    {
        $user = auth()->user();
        $myEmployee = Employee::with(['role', 'companies'])->find($user->employee_id);

        if (!$myEmployee) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $roleTitle = strtolower(trim($myEmployee->role->title ?? ''));
        $accessibleCompanyIds = $this->getAccessibleCompanyIds($myEmployee);

        if ($roleTitle !== 'developer' && $holiday->company_id && !in_array((int) $holiday->company_id, $accessibleCompanyIds)) {
            abort(403, 'Unauthorized.');
        }

        $companies = $roleTitle === 'developer'
            ? Company::where('status', 'active')->orderBy('name')->get()
            : Company::whereIn('id', $accessibleCompanyIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

        return view('holidays.edit', compact('holiday', 'companies'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        $user = auth()->user();
        $myEmployee = Employee::with(['role', 'companies'])->find($user->employee_id);

        if (!$myEmployee) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $roleTitle = strtolower(trim($myEmployee->role->title ?? ''));
        $accessibleCompanyIds = $this->getAccessibleCompanyIds($myEmployee);

        if ($roleTitle !== 'developer' && $holiday->company_id && !in_array((int) $holiday->company_id, $accessibleCompanyIds)) {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'holiday_date' => 'required|date',
            'type' => 'required|in:regular,special',
            'is_recurring' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($roleTitle !== 'developer' && !empty($data['company_id'])) {
            if (!in_array((int) $data['company_id'], $accessibleCompanyIds)) {
                abort(403, 'Unauthorized company selection.');
            }
        }

        $data['is_recurring'] = $request->boolean('is_recurring');
        $data['is_active'] = $request->boolean('is_active', true);

        $holiday->update($data);

        return redirect()
            ->route('holidays.index')
            ->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        $user = auth()->user();
        $myEmployee = Employee::with(['role', 'companies'])->find($user->employee_id);

        if (!$myEmployee) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        $roleTitle = strtolower(trim($myEmployee->role->title ?? ''));
        $accessibleCompanyIds = $this->getAccessibleCompanyIds($myEmployee);

        if ($roleTitle !== 'developer' && $holiday->company_id && !in_array((int) $holiday->company_id, $accessibleCompanyIds)) {
            abort(403, 'Unauthorized.');
        }

        $holiday->delete();

        return redirect()
            ->route('holidays.index')
            ->with('success', 'Holiday deleted successfully.');
    }

    private function getAccessibleCompanyIds(Employee $employee): array
    {
        return $employee->companies()
            ->pluck('companies.id')
            ->map(fn ($id) => (int) $id)
            ->push((int) $employee->company_id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}