<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\EmployeeScheduleAssignment;
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
        $companyId = (int) $user->company_id;

        $myEmployee = Employee::with('role')
            ->where('company_id', $companyId)
            ->find($user->employee_id);

        if (!$myEmployee) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record in your company.',
            ]);
        }

        $roleTitle = strtolower(trim($myEmployee->role->title ?? ''));

        $query = Employee::with(['company', 'department', 'role'])
            ->where('company_id', $companyId);

        $employees = $query->get();

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $companyId = (int) auth()->user()->company_id;

        $departments = Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $roles = Role::orderBy('title')->get(); // global roles
        $schedules = Schedule::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $deductionTypes = DeductionType::orderBy('name')->get();

        return view('employees.create', compact(
            'departments',
            'roles',
            'schedules',
            'deductionTypes'
        ));
    }

    public function store(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;

        $validatedData = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'required|date',
            'hire_date' => 'required|date',
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
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
            'company_id' => $companyId,
            'department_id' => $validatedData['department_id'],
            'role_id' => $validatedData['role_id'],
            'status' => $validatedData['status'],
            'salary' => $validatedData['salary'],
        ]);

        $this->syncEmployeeDeductions($employee, $request->input('deductions', []));

        $user = User::where('email', $employee->email)->first();

        if ($user) {
            if ((int) $user->company_id !== $companyId) {
                return redirect()
                    ->route('employees.index')
                    ->withErrors(['email' => 'An existing user with this email belongs to another company.']);
            }

            $user->employee_id = (string) $employee->id;
            $user->company_id = $companyId;
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
            'company_id' => $companyId,
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee created successfully.')
            ->with('temp_password', $tempPassword);
    }

    public function show($id)
    {
        $companyId = (int) auth()->user()->company_id;

        $employee = Employee::with(['company', 'department', 'role'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return view('employees.show', compact('employee'));
    }

    public function edit($id)
    {
        $companyId = (int) auth()->user()->company_id;

        $employee = Employee::with(['deductions', 'company'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $departments = Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $roles = Role::orderBy('title')->get(); // global roles

        $schedules = Schedule::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $deductionTypes = DeductionType::orderBy('name')->get();

        $today = now()->toDateString();

        $currentScheduleAssignment = EmployeeScheduleAssignment::with([
                'schedule' => function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                }
            ])
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            })
            ->latest('effective_from')
            ->first();

        return view('employees.edit', compact(
            'employee',
            'departments',
            'roles',
            'schedules',
            'currentScheduleAssignment',
            'deductionTypes'
        ));
    }

    public function update(Request $request, $id)
    {
        $companyId = (int) auth()->user()->company_id;

        $employee = Employee::where('company_id', $companyId)->findOrFail($id);

        $validatedData = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('employees', 'email')->ignore($employee->id),
            ],
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'required|date',
            'hire_date' => 'required|date',
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|string',
            'salary' => 'required|numeric',
            'deductions' => 'array',
        ]);

        $employee->update([
            'fullname' => $validatedData['fullname'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'address' => $validatedData['address'] ?? null,
            'birth_date' => $validatedData['birth_date'],
            'hire_date' => $validatedData['hire_date'],
            'department_id' => $validatedData['department_id'],
            'role_id' => $validatedData['role_id'],
            'status' => $validatedData['status'],
            'salary' => $validatedData['salary'],
        ]);

        $this->syncEmployeeDeductions($employee, $request->input('deductions', []));

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy($id)
    {
        $companyId = (int) auth()->user()->company_id;

        $employee = Employee::where('company_id', $companyId)->findOrFail($id);
        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    public function assignSchedule(Request $request, $id)
    {
        $companyId = (int) auth()->user()->company_id;

        $employee = Employee::where('company_id', $companyId)->findOrFail($id);

        $data = $request->validate([
            'schedule_id' => [
                'required',
                Rule::exists('schedules', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        EmployeeScheduleAssignment::where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->whereNull('effective_to')
            ->where('effective_from', '<=', $data['effective_from'])
            ->update([
                'effective_to' => Carbon::parse($data['effective_from'])->subDay()->toDateString(),
            ]);

        EmployeeScheduleAssignment::create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'schedule_id' => $data['schedule_id'],
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
        ]);

        return back()->with('success', 'Schedule assigned to employee.');
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