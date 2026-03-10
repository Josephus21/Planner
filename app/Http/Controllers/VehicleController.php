<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::orderBy('type')->orderBy('name')->get();
        return view('vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('vehicles.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'type' => ['required','in:car,truck'],
            'plate_no' => ['nullable','string','max:100'],
            'description' => ['nullable','string','max:255'],
            'is_active' => ['nullable','boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        Vehicle::create($data);

        return redirect()->route('vehicles.index')->with('success','Vehicle added.');
    }

    public function edit(Vehicle $vehicle)
    {
        return view('vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'type' => ['required','in:car,truck'],
            'plate_no' => ['nullable','string','max:100'],
            'description' => ['nullable','string','max:255'],
            'is_active' => ['nullable','boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $vehicle->update($data);

        return redirect()->route('vehicles.index')->with('success','Vehicle updated.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return back()->with('success','Vehicle deleted.');
    }
}