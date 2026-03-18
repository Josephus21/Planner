<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\EmployeeOvertimeSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OvertimeRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = OvertimeRequest::with(['employee', 'dates'])->latest();

        if (!$user->hasPermission('overtime_requests.manage')) {
            $query->where('employee_id', (int) $user->employee_id);
        }

        $requests = $query->get();

        return view('overtime_requests.index', compact('requests'));
    }

    public function create()
    {
        return view('overtime_requests.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::findOrFail((int) $user->employee_id);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'reason' => 'required|string',
            'dates' => 'required|array|min:1',
            'dates.*.ot_date' => 'required|date',
            'dates.*.start_time' => 'required',
            'dates.*.end_time' => 'required',
            'dates.*.break_minutes' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $employee) {
            $requestRow = OvertimeRequest::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'title' => $data['title'] ?? null,
                'reason' => $data['reason'],
                'status' => 'pending',
            ]);

            foreach ($data['dates'] as $row) {
                $start = Carbon::parse($row['ot_date'].' '.$row['start_time']);
                $end   = Carbon::parse($row['ot_date'].' '.$row['end_time']);

                if ($end->lte($start)) {
                    $end->addDay();
                }

                $breakMinutes = (float) ($row['break_minutes'] ?? 0);
                $plannedHours = max(0, ($start->diffInMinutes($end) - $breakMinutes) / 60);

                $requestRow->dates()->create([
                    'ot_date' => $row['ot_date'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'break_minutes' => $breakMinutes,
                    'planned_hours' => round($plannedHours, 2),
                ]);
            }
        });

        return redirect()
            ->route('overtime-requests.index')
            ->with('success', 'Overtime request submitted successfully.');
    }

    public function show(OvertimeRequest $overtimeRequest)
    {
        $overtimeRequest->load(['employee', 'dates']);

        return view('overtime_requests.show', compact('overtimeRequest'));
    }

    public function approve(Request $request, OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending requests can be approved.']);
        }

        DB::transaction(function () use ($overtimeRequest, $request) {
            $overtimeRequest->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'remarks' => $request->input('remarks'),
            ]);

            foreach ($overtimeRequest->dates as $dateRow) {
                EmployeeOvertimeSchedule::updateOrCreate(
                    [
                        'employee_id' => $overtimeRequest->employee_id,
                        'overtime_request_id' => $overtimeRequest->id,
                        'ot_date' => $dateRow->ot_date->format('Y-m-d'),
                        'start_time' => $dateRow->start_time,
                        'end_time' => $dateRow->end_time,
                    ],
                    [
                        'break_minutes' => $dateRow->break_minutes ?? 0,
                        'approved_hours' => $dateRow->planned_hours ?? 0,
                        'status' => 'approved',
                    ]
                );
            }
        });

        return redirect()
            ->route('overtime-requests.index')
            ->with('success', 'Overtime request approved and added to employee schedule.');
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending requests can be rejected.']);
        }

        $overtimeRequest->update([
            'status' => 'rejected',
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        return redirect()
            ->route('overtime-requests.index')
            ->with('success', 'Overtime request rejected.');
    }
}