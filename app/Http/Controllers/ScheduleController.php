<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::orderBy('name')->get();
        return view('schedules.index', compact('schedules'));
    }

    public function create()
    {
        return view('schedules.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
            'break_start' => 'nullable',
            'break_end' => 'nullable',
            'lunch_start' => 'nullable',
            'lunch_end' => 'nullable',
        ]);

        Schedule::create($data);

        return redirect()->route('schedules.index')->with('success', 'Schedule created.');
    }

    public function edit(Schedule $schedule)
    {
        return view('schedules.edit', compact('schedule'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
            'break_start' => 'nullable',
            'break_end' => 'nullable',
            'lunch_start' => 'nullable',
            'lunch_end' => 'nullable',
        ]);

        $schedule->update($data);

        return redirect()->route('schedules.index')->with('success', 'Schedule updated.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'Schedule deleted.');
    }
}