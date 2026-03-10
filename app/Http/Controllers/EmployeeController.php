<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $employees = Employee::all();
        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $departments = Department::all();
        $roles = Role::all();
        $schedules = Schedule::all();
        $deductionTypes = DeductionType::orderBy('name')->get();

        return view('employees.create', compact('departments', 'roles', 'schedules', 'deductionTypes'));
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
            'department_id' => 'required',
            'role_id' => 'required',
            'status' => 'required|string',
            'salary' => 'required|numeric',

            // optional when creating user
            'user_password' => 'nullable|string|min:6',

            // deductions array (optional)
            'deductions' => 'array',
        ]);

        // ✅ 1) Create employee (ONLY ONCE)
        $employee = Employee::create([
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

        // ✅ 2) Save deductions for employee
        $this->syncEmployeeDeductions($employee, $request->input('deductions', []));

        // ✅ 3) Auto-link user by same email, else create new user
        $tempPassword = null;

        $user = User::where('email', $employee->email)->first();
        if ($user) {
            $user->employee_id = (string) $employee->id; // your users.employee_id is varchar
            $user->save();

            return redirect()
                ->route('employees.index')
                ->with('success', 'Employee created successfully and existing user linked automatically.');
        }

        // Create new user
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
        $employee = Employee::findOrFail($id);
        return view('employees.show', compact('employee'));
    }

    public function edit($id)
    {
        $employee = Employee::with('deductions')->findOrFail($id);

        $departments = Department::all();
        $roles = Role::all();
        $schedules = Schedule::orderBy('name')->get();
        $deductionTypes = DeductionType::orderBy('name')->get();

        $today = now()->toDateString();
        $currentScheduleAssignment = EmployeeScheduleAssignment::with('schedule')
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
        $validatedData = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'required|date',
            'hire_date' => 'required|date',
            'department_id' => 'required',
            'role_id' => 'required',
            'status' => 'required|string',
            'salary' => 'required|numeric',

            // deductions array (optional)
            'deductions' => 'array',
        ]);

        $employee = Employee::findOrFail($id);
        $employee->update($validatedData);

        // ✅ Sync deductions on update
        $this->syncEmployeeDeductions($employee, $request->input('deductions', []));

        // OPTIONAL: sync user name/email
        // $user = User::where('employee_id', (string)$employee->id)->first();
        // if ($user) {
        //     $user->name = $employee->fullname;
        //     $user->email = $employee->email;
        //     $user->save();
        // }

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

        // close previous active assignment
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

    /**
     * Sync employee deductions from form input.
     * Input format:
     * deductions[type_id][enabled]=1
     * deductions[type_id][amount]=500
     * deductions[type_id][is_active]=1
     */
    private function syncEmployeeDeductions(Employee $employee, array $deductions): void
    {
        // If empty, we can disable all existing (optional)
        // If you want to keep old deductions when none submitted, comment this out.
        $seenTypeIds = [];

        foreach ($deductions as $typeId => $data) {
            $typeId = (int) $typeId;
            $seenTypeIds[] = $typeId;

            $enabled = isset($data['enabled']) && (int) $data['enabled'] === 1;

            if (!$enabled) {
                // remove if exists
                EmployeeDeduction::where('employee_id', $employee->id)
                    ->where('deduction_type_id', $typeId)
                    ->delete();
                continue;
            }

            $amount = (float) ($data['amount'] ?? 0);
            $isActive = isset($data['is_active']) && (int) $data['is_active'] === 1;

            // if enabled but no amount, treat as remove (or set 0)
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

        // OPTIONAL: If you want to delete deductions not present in the form submission
        // (Usually not needed because we send all types in edit/create view)
        // EmployeeDeduction::where('employee_id', $employee->id)
        //     ->whereNotIn('deduction_type_id', $seenTypeIds)
        //     ->delete();
    }
}