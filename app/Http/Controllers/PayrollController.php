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
        $companyId = auth()->user()->company_id;

        // list payroll periods for this company only
        $periods = PayrollPeriod::where('company_id', $companyId)
            ->orderByDesc('date_from')
            ->get();

        if ($request->filled('period_id')) {
            $selectedPeriod = PayrollPeriod::where('company_id', $companyId)
                ->findOrFail($request->period_id);
        } else {
            $selectedPeriod = PayrollPeriod::where('company_id', $companyId)
                ->orderByDesc('date_from')
                ->first();
        }

        // payroll rows for selected period
        $payrolls = $selectedPeriod
            ? Payroll::with(['employee', 'items'])
                ->where('company_id', $companyId)
                ->where('payroll_period_id', $selectedPeriod->id)
                ->orderByRaw('net_pay DESC')
                ->get()
            : collect();

        return view('payrolls.index', compact('periods', 'selectedPeriod', 'payrolls'));
    }

    /**
     * Generate payroll
     */
    public function generate(Request $request, PayrollGenerator $generator)
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $period = PayrollPeriod::firstOrCreate(
            [
                'company_id' => $companyId,
                'date_from' => $validated['date_from'],
                'date_to' => $validated['date_to'],
            ]
        );

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
     * View payroll record
     */
    public function show(Payroll $payroll)
    {
        $companyId = auth()->user()->company_id;

        if ($payroll->company_id !== $companyId) {
            abort(403, 'Unauthorized payroll access.');
        }

        $payroll->load(['employee', 'items', 'period']);

        return view('payrolls.show', compact('payroll'));
    }

    /**
     * Delete payroll
     */
    public function destroy(Payroll $payroll)
    {
        $companyId = auth()->user()->company_id;

        if ($payroll->company_id !== $companyId) {
            abort(403, 'Unauthorized payroll deletion.');
        }

        if ($payroll->period && $payroll->period->status === 'posted') {
            return back()->withErrors([
                'delete' => 'Cannot delete payroll from a POSTED period.'
            ]);
        }

        $periodId = $payroll->payroll_period_id;

        $payroll->delete();

        return redirect()
            ->route('payrolls.index', ['period_id' => $periodId])
            ->with('success', 'Payroll record deleted successfully.');
    }
}