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

    $myEmployee = Employee::with('role')->find($user->employee_id);

    if (!$myEmployee) {
        return back()->withErrors([
            'employee' => 'Your account is not linked to an employee record.',
        ]);
    }

    $roleTitle = strtolower(trim($myEmployee->role->title ?? ''));
    $myCompanyId = $myEmployee->company_id;

    $query = Employee::with(['company', 'department', 'role']);

    // Developer can see all companies
    if ($roleTitle !== 'developer') {
        $query->where('company_id', $myCompanyId);
    }

    $employees = $query->get();

    return view('employees.index', compact('employees'));
}

    public function create()
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();
        $departments = Department::all();
        $roles = Role::all();
        $schedules = Schedule::all();
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
        $validatedData = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'required|date',
            'hire_date' => 'required|date',
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'required|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|string',
            'salary' => 'required|numeric',

            'user_password' => 'nullable|string|min:6',
            'deductions' => 'array',
        ]);

      $employee = Employee::create([
    'fullname' => $validatedData['fullname'],
    'email' => $validatedData['email'],
    'phone_number' => $validatedData['phone_number'],
    'address' => $validatedData['address'] ?? null,
    'birth_date' => $validatedData['birth_date'],
    'hire_date' => $validatedData['hire_date'],
    'company_id' => $validatedData['company_id'],   // ADD THIS
    'department_id' => $validatedData['department_id'],
    'role_id' => $validatedData['role_id'],
    'status' => $validatedData['status'],
    'salary' => $validatedData['salary'],
]);

        $this->syncEmployeeDeductions($employee, $request->input('deductions', []));

        $tempPassword = null;

        $user = User::where('email', $employee->email)->first();
        if ($user) {
            $user->employee_id = (string) $employee->id;
            $user->save();

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
        $employee = Employee::with(['company', 'department', 'role'])->findOrFail($id);
        return view('employees.show', compact('employee'));
    }

   public function edit($id)
{
    $user = auth()->user();

    $myEmployee = Employee::with('role')->find($user->employee_id);

    if (!$myEmployee) {
        return back()->withErrors([
            'employee' => 'Your account is not linked to an employee record.',
        ]);
    }

    $roleTitle = strtolower(trim($myEmployee->role->title ?? ''));
    $myCompanyId = $myEmployee->company_id;

    $query = Employee::with([
        'deductions',
        'company',
        'department',
        'role',
    ]);

    // Developer can access all companies
    if ($roleTitle !== 'developer') {
        $query->where('company_id', $myCompanyId);
    }

    $employee = $query->findOrFail($id);

    // Developer can choose all active companies
    // Non-developer can only see own company in dropdown
    $companies = $roleTitle === 'developer'
        ? Company::where('status', 'active')->orderBy('name')->get()
        : Company::where('id', $myCompanyId)->where('status', 'active')->orderBy('name')->get();

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

    $myEmployee = Employee::with('role')->find($user->employee_id);

    if (!$myEmployee) {
        return back()->withErrors([
            'employee' => 'Your account is not linked to an employee record.',
        ]);
    }

    $roleTitle = strtolower(trim($myEmployee->role->title ?? ''));
    $myCompanyId = $myEmployee->company_id;

    $query = Employee::query();

    if ($roleTitle !== 'developer') {
        $query->where('company_id', $myCompanyId);
    }

    $employee = $query->findOrFail($id);

    $validatedData = $request->validate([
        'fullname' => 'required|string|max:255',
        'email' => 'required|email',
        'phone_number' => 'required|string|max:20',
        'address' => 'nullable|string',
        'birth_date' => 'required|date',
        'hire_date' => 'required|date',
        'company_id' => 'required|exists:companies,id',
        'department_id' => 'required|exists:departments,id',
        'role_id' => 'required|exists:roles,id',
        'status' => 'required|string',
        'salary' => 'required|numeric',
        'deductions' => 'array',
    ]);

    // Non-developer cannot move employee to another company
    if ($roleTitle !== 'developer') {
        $validatedData['company_id'] = $myCompanyId;
    }

    $employee->update($validatedData);

    $this->syncEmployeeDeductions($employee, $request->input('deductions', []));

    return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
}

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    public function assignSchedule(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

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

    private function syncEmployeeDeductions(Employee $employee, array $deductions): void
    {
        $seenTypeIds = [];

        foreach ($deductions as $typeId => $data) {
            $typeId = (int) $typeId;
            $seenTypeIds[] = $typeId;

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