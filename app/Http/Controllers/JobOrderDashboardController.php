<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\JobOrder;
use App\Models\Project;
use Carbon\Carbon;

class JobOrderDashboardController extends Controller
{
    private function currentDeptId(): int
    {
        $user = auth()->user();
        $employeeId = $user?->employee_id;

        if (!$employeeId) {
            abort(403, 'Your account is not linked to an employee record.');
        }

        $employee = Employee::find($employeeId);

        if (!$employee || !$employee->department_id) {
            abort(403, 'Your employee record has no department assigned.');
        }

        return (int) $employee->department_id;
    }

    public function index()
    {
        $deptId = $this->currentDeptId();
        $today = Carbon::today()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Total Job Orders (all fetched)
        |--------------------------------------------------------------------------
        */

        $totalJobOrders = JobOrder::count();

        /*
        |--------------------------------------------------------------------------
        | On-going Projects (planned already)
        |--------------------------------------------------------------------------
        */

        $ongoingProjects = Project::where('department_id', $deptId)
            ->where('status', 'ongoing')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Pending Job Orders
        | Job orders NOT yet scheduled as projects
        |--------------------------------------------------------------------------
        */

        $scheduledIds = Project::whereNotNull('job_order_id')
            ->pluck('job_order_id');

        $pendingJobOrders = JobOrder::whereNotIn('id', $scheduledIds)->count();

        /*
        |--------------------------------------------------------------------------
        | Due Projects
        |--------------------------------------------------------------------------
        */

        $dueProjects = Project::where('department_id', $deptId)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('date_to')
            ->whereDate('date_to', '<=', $today)
            ->count();

        return view('job_orders.dashboard', compact(
            'totalJobOrders',
            'ongoingProjects',
            'pendingJobOrders',
            'dueProjects'
        ));
    }

    public function events()
    {
        $deptId = $this->currentDeptId();

        $projects = Project::where('department_id', $deptId)
            ->select('id','title','date_from','date_to','status')
            ->get();

        $events = $projects->map(function ($p) {

            $endExclusive = $p->date_to
                ? Carbon::parse($p->date_to)->addDay()->toDateString()
                : null;

            return [
                'id' => $p->id,
                'title' => $p->title.' ('.strtoupper($p->status).')',
                'start' => $p->date_from,
                'end' => $endExclusive,
                'url' => route('projects.show', $p->id),
                'allDay' => true,
            ];
        });

        return response()->json($events);
    }
}