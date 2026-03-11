<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        $companyId = (int) auth()->user()->company_id;

        $departments = Department::where('company_id', $companyId)->get();

        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        return view('departments.create');
    }

    public function store(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:active,inactive',
        ]);

        Department::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit($id)
    {
        $companyId = (int) auth()->user()->company_id;

        $department = Department::where('company_id', $companyId)->findOrFail($id);

        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, $id)
    {
        $companyId = (int) auth()->user()->company_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:active,inactive',
        ]);

        $department = Department::where('company_id', $companyId)->findOrFail($id);

        $department->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy($id)
    {
        $companyId = (int) auth()->user()->company_id;

        $department = Department::where('company_id', $companyId)->findOrFail($id);

        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}