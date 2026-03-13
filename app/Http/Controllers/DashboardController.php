<?php

namespace App\Http\Controllers;

use App\Models\Employee;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $employee = $user->employee_id
            ? Employee::with(['role', 'department'])->find($user->employee_id)
            : null;

        $roleTitle = strtolower(trim(optional($employee?->role)->title ?? ''));
        $departmentName = strtolower(trim(optional($employee?->department)->name ?? ''));

        // HR Department → HR Dashboard
        if ($departmentName === 'hr') {
            return redirect()->route('hr.dashboard');
        }

        // Planner → Planner Dashboard
        if ($roleTitle === 'planner') {
            return redirect()->route('planner.dashboard');
        }

        // LD Planner → Job Order Dashboard
        if ($roleTitle === 'ldplanner') {
            return redirect()->route('job-orders.dashboard');
        }

        // Manager → Manager Dashboard
        if ($roleTitle === 'manager') {
            return redirect()->route('manager.dashboard');
        }

        // Developer → Admin Dashboard
        if ($roleTitle === 'developer') {
            return view('dashboard.index');
        }

        // Default → Employee Dashboard
        return redirect()->route('employee.dashboard');
    }
}