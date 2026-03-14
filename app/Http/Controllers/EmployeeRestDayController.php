<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeRestDay;
use Illuminate\Http\Request;

class EmployeeRestDayController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['restDays' => function ($q) {
            $q->where('is_active', 1);
        }])->orderBy('first_name')->get();

        return view('employee-rest-days.index', compact('employees'));
    }

    public function edit(Employee $employee)
    {
        $selectedDays = $employee->restDays()
            ->where('is_active', 1)
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

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'rest_days' => 'nullable|array',
            'rest_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        $restDays = $data['rest_days'] ?? [];

        // remove old
        EmployeeRestDay::where('employee_id', $employee->id)->delete();

        // insert new
        foreach ($restDays as $day) {
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
}