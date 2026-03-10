<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use Carbon\Carbon;
use App\Services\GsuiteJobOrderApi;
use App\Models\SalesOrder;
use Illuminate\Http\Request;

class PlannerDashboardController extends Controller
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

    $base = Project::query()->where('department_id', $deptId);

    // ? from DB sales_orders
    $totalProjects = SalesOrder::count();

    $ongoingProjects = (clone $base)->where('status', 'ongoing')->count();
    $pendingProjects = (clone $base)->where('status', 'pending')->count();

    $dueProjects = (clone $base)
        ->whereNotIn('status', ['done', 'cancelled'])
        ->whereNotNull('date_to')
        ->whereDate('date_to', '<=', $today)
        ->count();

    return view('planner.dashboard', compact(
        'totalProjects',
        'ongoingProjects',
        'pendingProjects',
        'dueProjects'
    ));
}


    public function events(Request $request)
    {
        $deptId = $this->currentDeptId();

        $projects = Project::query()
            ->where('department_id', $deptId) // ? department scope
            ->select('id', 'title', 'date_from', 'date_to', 'status')
            ->get();

        $events = $projects->map(function ($p) {
            $endExclusive = $p->date_to
                ? Carbon::parse($p->date_to)->addDay()->toDateString()
                : null;

            return [
                'id'     => $p->id,
                'title'  => $p->title . ' (' . strtoupper($p->status) . ')',
                'start'  => $p->date_from,
                'end'    => $endExclusive,
                'url'    => route('projects.show', $p->id),
                'allDay' => true,
            ];
        });

        return response()->json($events);
    }
}