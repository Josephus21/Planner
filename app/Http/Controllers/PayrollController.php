<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payroll;
use App\Models\Employee;
class PayrollController extends Controller
{
    public function index()
    {
        $payrolls = Payroll::all();
        return view('payrolls.index', compact('payrolls'));
    }

    public function create()
    {
        $employees = Employee::all();
        return view('payrolls.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric',
            'bonuses' => 'nullable|numeric',
            'deductions' => 'nullable|numeric',
            'pay_date' => 'required|date',
        ]);

        $netsalary = ($request->input('amount') + $request->input('bonuses', 0)) - $request->input('deductions', 0);

        $request->merge(['net_salary' => $netsalary]);
        Payroll::create($request->all());

        return redirect()->route('payrolls.index')->with('success', 'Payroll record created successfully.');
    }

    public function edit(Payroll $payroll)
    {
        
        $employees = Employee::all();
        return view('payrolls.edit', compact('payroll', 'employees'));
    }

    public function update(Request $request, Payroll $payroll)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric',
            'bonuses' => 'nullable|numeric',
            'deductions' => 'nullable|numeric',
            'pay_date' => 'required|date',
        ]);

        $netsalary = ($request->input('amount') + $request->input('bonuses', 0)) - $request->input('deductions', 0);

        $request->merge(['net_salary' => $netsalary]);
        $payroll->update($request->all());

        return redirect()->route('payrolls.index')->with('success', 'Payroll record updated successfully.');
    }

    public function show(Payroll $payroll)
    {
        return view('payrolls.show', compact('payroll'));
    }


    public function destroy(Payroll $payroll)
    {
        $payroll->delete();
        return redirect()->route('payrolls.index')->with('success', 'Payroll record deleted successfully.');
    }


}
