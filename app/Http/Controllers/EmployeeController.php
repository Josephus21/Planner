<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\EmployeeScheduleAssignment;
use App\Models\Company;
use Carbon\Carbon;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\DeductionType;
use App\Models\EmployeeDeduction;

class EmployeeController extends Controller
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

        $query = Employee::with(['company', 'companies', 'department', 'role']);

        if ($roleTitle !== 'developer') {
            $query->where(function ($q) use ($accessibleCompanyIds) {
                $q->whereIn('company_id', $accessibleCompanyIds)
                  ->orWhereHas('companies', function ($sub) use ($accessibleCompanyIds) {
                      $sub->whereIn('companies.id', $accessibleCompanyIds);
                  });
            });
        }

        $employees = $query->get();

        return view('employees.index', compact('employees'));
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
            : Company::whereIn('id', $accessibleCompanyIds)->where('status', 'active')->orderBy('name')->get();

        $departments = Department::orderBy('name')->get();
        $roles = Role::orderBy('title')->get();
        $schedules = Schedule::orderBy('name')->get();
        $deductionTypes = DeductionType::orderBy('name')->get();

        return view('employees.create', compact(
            'companies',
            'departments',
            'roles',
            'schedules',
            'deductionTypes'
        ));
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

        $validatedData = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'required|date',
            'hire_date' => 'required|date',
            'company_id' => 'required|exists:companies,id',
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'exists:companies,id',
            'department_id' => 'required|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|string|in:active,inactive',
            'salary_type' => 'required|in:monthly,daily',
            'salary' => 'required|numeric|min:0',
            'user_password' => 'nullable|string|min:6',
            'deductions' => 'nullable|array',
        ]);

        if ($roleTitle !== 'developer') {
            $validatedData['company_id'] = in_array((int) $validatedData['company_id'], $accessibleCompanyIds)
                ? (int) $validatedData['company_id']
                : (int) $myEmployee->company_id;

            $validatedData['company_ids'] = collect($validatedData['company_ids'])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => in_array($id, $accessibleCompanyIds))
                ->values()
                ->all();

            if (empty($validatedData['company_ids'])) {
                $validatedData['company_ids'] = [(int) $validatedData['company_id']];
            }
        }

        $employee = Employee::create([
            'fullname' => $validatedData['fullname'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'address' => $validatedData['address'] ?? null,
            'birth_date' => $validatedData['birth_date'],
            'hire_date' => $validatedData['hire_date'],
            'company_id' => $validatedData['company_id'],
            'department_id' => $validatedData['department_id'],
            'role_id' => $validatedData['role_id'],
            'status' => $validatedData['status'],
            'salary_type' => $validatedData['salary_type'],
            'salary' => $validatedData['salary'],
        ]);

        $companyIds = collect($validatedData['company_ids'])
            ->push($validatedData['company_id'])
            ->unique()
            ->values()
            ->all();

        $employee->companies()->sync($companyIds);

        $this->syncEmployeeDeductions($employee, $request->input('deductions', []));

        $existingUser = User::where('email', $employee->email)->first();

        if ($existingUser) {
            $existingUser->employee_id = (string) $employee->id;
            $existingUser->save();

            return redirect()
                ->route('employees.index')
                ->with('success', 'Employee created successfully and existing user linked automatically.');
        }

        $tempPassword = $request->input('user_password') ?: Str::random(10);

        User::create([
            'name' => $employee->fullname,
            'email' => $employee->email,
            'password' => Hash::make($tempPassword),
            'employee_id' => (string) $employee->id,
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee created successfully.')
            ->with('temp_password', $tempPassword);
    }

    public function show($id)
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

        $query = Employee::with(['company', 'companies', 'department', 'role']);

        if ($roleTitle !== 'developer') {
            $query->where(function ($q) use ($accessibleCompanyIds) {
                $q->whereIn('company_id', $accessibleCompanyIds)
                  ->orWhereHas('companies', function ($sub) use ($accessibleCompanyIds) {
                      $sub->whereIn('companies.id', $accessibleCompanyIds);
                  });
            });
        }

        $employee = $query->findOrFail($id);

        return view('employees.show', compact('employee'));
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

        $query = Employee::with([
            'deductions',
            'company',
            'companies',
            'department',
            'role',
        ]);

        if ($roleTitle !== 'developer') {
            $query->where(function ($q) use ($accessibleCompanyIds) {
                $q->whereIn('company_id', $accessibleCompanyIds)
                  ->orWhereHas('companies', function ($sub) use ($accessibleCompanyIds) {
                      $sub->whereIn('companies.id', $accessibleCompanyIds);
                  });
            });
        }

        $employee = $query->findOrFail($id);

        $companies = $roleTitle === 'developer'
            ? Company::where('status', 'active')->orderBy('name')->get()
            : Company::whereIn('id', $accessibleCompanyIds)->where('status', 'active')->orderBy('name')->get();

        $departments = Department::orderBy('name')->get();
        $roles = Role::orderBy('title')->get();
        $schedules = Schedule::orderBy('name')->get();
        $deductionTypes = DeductionType::orderBy('name')->get();

        $today = now()->toDateString();

        $currentScheduleAssignment = EmployeeScheduleAssignment::with('schedule')
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $today);
            })
            ->latest('effective_from')
            ->first();

        return view('employees.edit', compact(
            'employee',
            'companies',
            'departments',
            'roles',
            'schedules',
            'currentScheduleAssignment',
            'deductionTypes'
        ));
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

        $validatedData = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'required|date',
            'hire_date' => 'required|date',
            'company_id' => 'required|exists:companies,id',
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'exists:companies,id',
            'department_id' => 'required|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|string|in:active,inactive',
            'salary_type' => 'required|in:monthly,daily',
            'salary' => 'required|numeric|min:0',
            'deductions' => 'nullable|array',
        ]);

        if ($roleTitle !== 'developer') {
            $validatedData['company_id'] = in_array((int) $validatedData['company_id'], $accessibleCompanyIds)
                ? (int) $validatedData['company_id']
                : (int) $myEmployee->company_id;

            $validatedData['company_ids'] = collect($validatedData['company_ids'])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => in_array($id, $accessibleCompanyIds))
                ->values()
                ->all();

            if (empty($validatedData['company_ids'])) {
                $validatedData['company_ids'] = [(int) $validatedData['company_id']];
            }
        }

        $employee->update([
            'fullname' => $validatedData['fullname'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'address' => $validatedData['address'] ?? null,
            'birth_date' => $validatedData['birth_date'],
            'hire_date' => $validatedData['hire_date'],
            'company_id' => $validatedData['company_id'],
            'department_id' => $validatedData['department_id'],
            'role_id' => $validatedData['role_id'],
            'status' => $validatedData['status'],
            'salary_type' => $validatedData['salary_type'],
            'salary' => $validatedData['salary'],
        ]);

        $companyIds = collect($validatedData['company_ids'])
            ->push($validatedData['company_id'])
            ->unique()
            ->values()
            ->all();

        $employee->companies()->sync($companyIds);

        $this->syncEmployeeDeductions($employee, $request->input('deductions', []));

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy($id)
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
        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    public function assignSchedule(Request $request, $id)
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
            'schedule_id' => 'required|exists:schedules,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        EmployeeScheduleAssignment::where('employee_id', $employee->id)
            ->whereNull('effective_to')
            ->where('effective_from', '<=', $data['effective_from'])
            ->update([
                'effective_to' => Carbon::parse($data['effective_from'])->subDay()->toDateString()
            ]);

        EmployeeScheduleAssignment::create([
            'employee_id' => $employee->id,
            'schedule_id' => $data['schedule_id'],
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
        ]);

        return back()->with('success', 'Schedule assigned to employee.');
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

    private function syncEmployeeDeductions(Employee $employee, array $deductions): void
    {
        foreach ($deductions as $typeId => $data) {
            $typeId = (int) $typeId;

            $enabled = isset($data['enabled']) && (int) $data['enabled'] === 1;

            if (!$enabled) {
                EmployeeDeduction::where('employee_id', $employee->id)
                    ->where('deduction_type_id', $typeId)
                    ->delete();
                continue;
            }

            $amount = (float) ($data['amount'] ?? 0);
            $isActive = isset($data['is_active']) && (int) $data['is_active'] === 1;

            if ($amount <= 0) {
                EmployeeDeduction::where('employee_id', $employee->id)
                    ->where('deduction_type_id', $typeId)
                    ->delete();
                continue;
            }

            EmployeeDeduction::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'deduction_type_id' => $typeId,
                ],
                [
                    'amount' => $amount,
                    'is_active' => $isActive,
                ]
            );
        }
    }
}