<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Employee;
  use Carbon\Carbon;
class LeaveRequestController extends Controller
{
    public function index()
    {
        $leaveRequests = LeaveRequest::all();
        return view('leave-requests.index', compact('leaveRequests'));
    }

    public function create()
    {
        $employees = Employee::all();
        return view('leave-requests.create', compact('employees'));
    }

  

public function store(Request $request)
{
    $request->validate([
        'employee_id' => 'required|exists:employees,id',
        'leave_type' => 'required|string',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $employee = Employee::findOrFail($request->employee_id);

    $days = Carbon::parse($request->start_date)
        ->diffInDays(Carbon::parse($request->end_date)) + 1;

    if ($request->leave_type === 'Sick Leave' && $employee->sick_leave_balance < $days) {
        return back()->withInput()->withErrors([
            'leave_type' => 'Insufficient Sick Leave credits.'
        ]);
    }

    if ($request->leave_type === 'Vacation' && $employee->vacation_leave_balance < $days) {
        return back()->withInput()->withErrors([
            'leave_type' => 'Insufficient Vacation Leave credits.'
        ]);
    }

    LeaveRequest::create([
        'employee_id' => $request->employee_id,
        'leave_type' => $request->leave_type,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'status' => 'pending',
    ]);

    return redirect()->route('leave-requests.index')
        ->with('success', 'Leave request created successfully.');
}

    public function edit(LeaveRequest $leaveRequest)
    {
        $employees = Employee::all();
        return view('leave-requests.edit', compact('leaveRequest', 'employees'));
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $leaveRequest->update($request->all());

        return redirect()->route('leave-requests.index')
                        ->with('success', 'Leave request updated successfully.');
    }


   public function confirm(int $id)
{
    $leaveRequest = LeaveRequest::findOrFail($id);
    $employee = $leaveRequest->employee;

    $days = Carbon::parse($leaveRequest->start_date)
        ->diffInDays(Carbon::parse($leaveRequest->end_date)) + 1;

    if ($leaveRequest->leave_type === 'Sick Leave') {
        if ($employee->sick_leave_balance < $days) {
            return redirect()->route('leave-requests.index')
                ->withErrors(['leave_type' => 'Not enough Sick Leave credits.']);
        }

        $employee->decrement('sick_leave_balance', $days);
    }

    if ($leaveRequest->leave_type === 'Vacation') {
        if ($employee->vacation_leave_balance < $days) {
            return redirect()->route('leave-requests.index')
                ->withErrors(['leave_type' => 'Not enough Vacation Leave credits.']);
        }

        $employee->decrement('vacation_leave_balance', $days);
    }

    $leaveRequest->update([
        'status' => 'confirm',
    ]);

    return redirect()->route('leave-requests.index')
        ->with('success', 'Leave request approved successfully.');
}
    public function reject(int $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        $leaveRequest->update([
            'status' => 'rejected'
        ]);
        return redirect()->route('leave-requests.index')
                        ->with('success', 'Leave request rejected successfully.');
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        $leaveRequest->delete();
        return redirect()->route('leave-requests.index')
                        ->with('success', 'Leave request deleted successfully.');
    }

    
}
