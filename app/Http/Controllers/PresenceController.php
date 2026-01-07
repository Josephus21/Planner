<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presence;
use App\Models\Employee;
use Carbon\Carbon;

class PresenceController extends Controller
{
    public function index()
    {
        if(session('role')=='HR'){
            $presences = Presence::all();
        }else{
            $presences = Presence::where('employee_id', session('employee_id'))->get();
        }
        return view('presences.index', compact('presences'));
    }

    public function create()
    {
        $employees = Employee::all();
        return view('presences.create', compact('employees')    );
    }

    public function store(Request $request)
    {
        if(session('role')=='HR'){
            $request->merge(['employee_id' => session('employee_id')]);
        

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'check_in'=> 'required',
            'check_out' => 'required',
            'date' => 'required|date',
            'status' => 'required|string',
        ]);


        Presence::create($request->all());
    }else{
        Presence::create([
            'employee_id' => session('employee_id'),
            'check_in'=> Carbon::now()->format('Y-m-d H:i:s'),
            'date' => Carbon::now()->format('Y-m-d'),
            'status' => 'present',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
    }
        return redirect()->route('presences.index')
                         ->with('success', 'Presence recorded successfully.');
    }

    public function edit(Presence $presence)
    {
        $employees = Employee::all();
        return view('presences.edit', compact('presence', 'employees'));
    }

    public function update(Request $request, Presence $presence)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'check_in'=> 'required',
            'check_out' => 'required',
            'date' => 'required|date',
            'status' => 'required|string',
        ]);

        $presence->update($request->all());

        return redirect()->route('presences.index')
                         ->with('success', 'Presence updated successfully.');
    }

    public function destroy(Presence $presence)
    {
        $presence->delete();

        return redirect()->route('presences.index')
                         ->with('success', 'Presence deleted successfully.');
    }


}
