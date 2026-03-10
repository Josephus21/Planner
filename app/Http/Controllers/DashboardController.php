<?php

namespace App\Http\Controllers;

use App\Models\Employee;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $employee = $user->employee_id
            ? Employee::with('role')->find($user->employee_id)
            : null;

        $roleTitle = strtolower(trim(optional($employee?->role)->title ?? ''));

        // Planner goes planner dashboard
        if ($roleTitle === 'planner') {
            return redirect()->route('planner.dashboard');
        }

        // LD Planner goes Job Order dashboard
        if ($roleTitle === 'ldplanner') {
            return redirect()->route('job-orders.dashboard');
        }

        if ($roleTitle === 'manager') {
            return redirect()->route('manager.dashboard');
        }
        // Developer goes admin dashboard
        if ($roleTitle === 'developer') {
            return view('dashboard.index');
        }

        return redirect()->route('employee.dashboard');
    }
}