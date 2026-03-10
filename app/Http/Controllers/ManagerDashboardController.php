<?php

namespace App\Http\Controllers;

use App\Models\JobOrder;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManagerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Job Order Overview
        |--------------------------------------------------------------------------
        */
        $totalJobOrders = JobOrder::count();

        $scheduledJobOrderIds = Project::query()
            ->whereNotNull('job_order_id')
            ->pluck('job_order_id')
            ->filter()
            ->unique();

        $plannedJobOrders = $scheduledJobOrderIds->count();

        $pendingJobOrders = JobOrder::query()
            ->whereNotIn('id', $scheduledJobOrderIds)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Planning Overview
        |--------------------------------------------------------------------------
        */
        $totalProjects = Project::count();

        $ongoingProjects = Project::where('status', 'ongoing')->count();

        $pendingProjects = Project::where('status', 'pending')->count();

        $dueProjects = Project::query()
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('date_to')
            ->whereDate('date_to', '<=', $today)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent Job Orders
        |--------------------------------------------------------------------------
        */
        $recentJobOrders = JobOrder::query()
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return view('manager.dashboard', compact(
            'totalJobOrders',
            'plannedJobOrders',
            'pendingJobOrders',
            'totalProjects',
            'ongoingProjects',
            'pendingProjects',
            'dueProjects',
            'recentJobOrders'
        ));
    }

    public function events()
    {
        $projects = Project::query()
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