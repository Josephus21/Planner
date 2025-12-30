<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Role;

class EmployeeController extends Controller
{
    public function index(){
        $employees = Employee::all();
        return view('employees.index', compact('employees'));
    }

    public function create(){
        $departments = Department::all();
        $roles = Role::all();
        return view('employees.create', compact('departments', 'roles'));
    }

    public function store(Request $request){
        $validatedData = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone_number'=>'required|string|max:20',
            'address' => 'required|string|nullable',
            'birth_date' => 'required|date',
            'hire_date' => 'required|date',
            'department_id' => 'required',
            'role_id' => 'required',
            'status' => 'required|string',
            'salary' => 'required|numeric',
        ]);

        Employee::create($validatedData);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }


}
