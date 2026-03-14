<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeRestDay;
use Illuminate\Http\Request;

class EmployeeRestDayController extends Controller
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

        $query = Employee::with(['restDays' => function ($q) {
            $q->where('is_active', 1);
        }]);

        if ($roleTitle !== 'developer') {
            $query->where(function ($q) use ($accessibleCompanyIds) {
                $q->whereIn('company_id', $accessibleCompanyIds)
                  ->orWhereHas('companies', function ($sub) use ($accessibleCompanyIds) {
                      $sub->whereIn('companies.id', $accessibleCompanyIds);
                  });
            });
        }

        $employees = $query->orderBy('fullname')->get();

        return view('employee-rest-days.index', compact('employees'));
    }

    public function edit($id)
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

        $query = Employee::with(['restDays' => function ($q) {
            $q->where('is_active', 1);
        }]);

        if ($roleTitle !== 'developer') {
            $query->where(function ($q) use ($accessibleCompanyIds) {
                $q->whereIn('company_id', $accessibleCompanyIds)
                  ->orWhereHas('companies', function ($sub) use ($accessibleCompanyIds) {
                      $sub->whereIn('companies.id', $accessibleCompanyIds);
                  });
            });
        }

        $employee = $query->findOrFail($id);

        $selectedDays = $employee->restDays
            ->pluck('day_name')
            ->toArray();

        $days = [
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ];

        return view('employee-rest-days.edit', compact('employee', 'selectedDays', 'days'));
    }

    public function update(Request $request, $id)
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

        $query = Employee::query();

        if ($roleTitle !== 'developer') {
            $query->where(function ($q) use ($accessibleCompanyIds) {
                $q->whereIn('company_id', $accessibleCompanyIds)
                  ->orWhereHas('companies', function ($sub) use ($accessibleCompanyIds) {
                      $sub->whereIn('companies.id', $accessibleCompanyIds);
                  });
            });
        }

        $employee = $query->findOrFail($id);

        $data = $request->validate([
            'rest_days' => 'nullable|array',
            'rest_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        EmployeeRestDay::where('employee_id', $employee->id)->delete();

        foreach (($data['rest_days'] ?? []) as $day) {
            EmployeeRestDay::create([
                'employee_id' => $employee->id,
                'day_name' => $day,
                'is_active' => true,
            ]);
        }

        return redirect()
            ->route('employee-rest-days.index')
            ->with('success', 'Rest day schedule updated successfully.');
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