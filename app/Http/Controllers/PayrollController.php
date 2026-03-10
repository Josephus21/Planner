<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Services\PayrollGenerator;

class PayrollController extends Controller
{
    /**
     * Payroll Index (by period)
     */
    public function index(Request $request)
    {
        // list all saved payroll periods
        $periods = PayrollPeriod::orderByDesc('date_from')->get();

        // pick selected period
        if ($request->filled('period_id')) {
            $selectedPeriod = PayrollPeriod::findOrFail($request->period_id);
        } else {
            $selectedPeriod = PayrollPeriod::orderByDesc('date_from')->first();
        }

        // get payroll rows for the period
        $payrolls = $selectedPeriod
            ? Payroll::with(['employee', 'items'])
                ->where('payroll_period_id', $selectedPeriod->id)
                ->orderByRaw('net_pay DESC')
                ->get()
            : collect();

        return view('payrolls.index', compact('periods', 'selectedPeriod', 'payrolls'));
    }

    /**
     * Generate / Recompute payroll (semi-monthly but flexible)
     * Takes date_from and date_to, creates a period if not exists, then computes payroll.
     */
    public function generate(Request $request, PayrollGenerator $generator)
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $period = PayrollPeriod::firstOrCreate([
            'date_from' => $validated['date_from'],
            'date_to'   => $validated['date_to'],
        ]);

        // Prevent recompute if posted/locked
        if ($period->status === 'posted') {
            return redirect()
                ->route('payrolls.index', ['period_id' => $period->id])
                ->withErrors(['period' => 'This payroll period is already POSTED and cannot be recomputed.']);
        }

        $generator->generate($period);

        return redirect()
            ->route('payrolls.index', ['period_id' => $period->id])
            ->with('success', 'Payroll generated successfully.');
    }

    /**
     * View a single payroll record (with breakdown)
     */
    public function show(Payroll $payroll)
    {
        $payroll->load(['employee', 'items', 'period']);
        return view('payrolls.show', compact('payroll'));
    }

    /**
     * Delete payroll record (optional)
     */
    public function destroy(Payroll $payroll)
    {
        // block delete if period posted
        if ($payroll->period && $payroll->period->status === 'posted') {
            return back()->withErrors(['delete' => 'Cannot delete payroll from a POSTED period.']);
        }

        $periodId = $payroll->payroll_period_id;
        $payroll->delete();

        return redirect()
            ->route('payrolls.index', ['period_id' => $periodId])
            ->with('success', 'Payroll record deleted successfully.');
    }
}