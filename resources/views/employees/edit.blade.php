<hr>
<h5 class="mb-2">Deductions</h5>
<p class="text-muted mb-3">Select deductions for this employee and configure how they will be deducted.</p>

<div class="table-responsive">
  <table class="table table-bordered">
    <thead>
      <tr>
        <th style="width: 25%">Deduction Type</th>
        <th style="width: 55%">Setup</th>
        <th style="width: 20%" class="text-center">Active</th>
      </tr>
    </thead>
    <tbody>
    @forelse($deductionTypes as $dt)
      @php
        $existing = $existingDeductionMap->get($dt->id);

        $enabledOld = old("deductions.{$dt->id}.enabled");
        $enabled = is_null($enabledOld)
            ? (bool) $existing
            : (bool) $enabledOld;

        $amount = old("deductions.{$dt->id}.amount", $existing->amount ?? '');
        $totalAmount = old("deductions.{$dt->id}.total_amount", $existing->total_amount ?? '');
        $installmentTerms = old("deductions.{$dt->id}.installment_terms", $existing->installment_terms ?? '');
        $remainingTerms = old("deductions.{$dt->id}.remaining_terms", $existing->remaining_terms ?? '');
        $remainingBalance = old("deductions.{$dt->id}.remaining_balance", $existing->remaining_balance ?? '');
        $payrollPeriodId = old("deductions.{$dt->id}.payroll_period_id", $existing->payroll_period_id ?? '');

        $isActiveOld = old("deductions.{$dt->id}.is_active");
        $isActive = is_null($isActiveOld)
            ? (bool)($existing->is_active ?? true)
            : (bool)$isActiveOld;
      @endphp

      <tr>
        <td>
          <div class="form-check">
            <input class="form-check-input deduction-toggle"
                   type="checkbox"
                   id="ded_{{ $dt->id }}"
                   name="deductions[{{ $dt->id }}][enabled]"
                   value="1"
                   {{ $enabled ? 'checked' : '' }}>
            <label class="form-check-label" for="ded_{{ $dt->id }}">
              {{ $dt->name }}
            </label>
          </div>
        </td>

        <td>
          {{-- Default amount field --}}
          <div class="mb-2">
            <label class="form-label mb-1">Amount per payroll</label>
            <input type="number"
                   step="0.01"
                   min="0"
                   class="form-control"
                   name="deductions[{{ $dt->id }}][amount]"
                   value="{{ $amount }}"
                   placeholder="0.00">
          </div>

          {{-- Loan / Installment --}}
          @if(in_array($dt->code, ['LOAN', 'INST']))
            <div class="row">
              <div class="col-md-6 mb-2">
                <label class="form-label mb-1">Total Amount</label>
                <input type="number"
                       step="0.01"
                       min="0"
                       class="form-control"
                       name="deductions[{{ $dt->id }}][total_amount]"
                       value="{{ $totalAmount }}"
                       placeholder="e.g. 12000.00">
              </div>

              <div class="col-md-6 mb-2">
                <label class="form-label mb-1">Payment Terms (No. of Payrolls)</label>
                <input type="number"
                       min="1"
                       class="form-control"
                       name="deductions[{{ $dt->id }}][installment_terms]"
                       value="{{ $installmentTerms }}"
                       placeholder="e.g. 12">
              </div>

              <div class="col-md-6 mb-2">
                <label class="form-label mb-1">Remaining Terms</label>
                <input type="number"
                       min="0"
                       class="form-control"
                       name="deductions[{{ $dt->id }}][remaining_terms]"
                       value="{{ $remainingTerms }}"
                       placeholder="e.g. 12">
              </div>

              <div class="col-md-6 mb-2">
                <label class="form-label mb-1">Remaining Balance</label>
                <input type="number"
                       step="0.01"
                       min="0"
                       class="form-control"
                       name="deductions[{{ $dt->id }}][remaining_balance]"
                       value="{{ $remainingBalance }}"
                       placeholder="e.g. 12000.00">
              </div>
            </div>

            <small class="text-muted">
              Example: total amount 12,000 / 12 payrolls / 1,000 per payroll.
            </small>
          @endif

          {{-- Scheduled deductions --}}
          @if(in_array($dt->code, ['CA', 'INS', 'OTH']))
            <div class="mb-2 mt-2">
              <label class="form-label mb-1">Deduct On Payroll Period</label>
              <select name="deductions[{{ $dt->id }}][payroll_period_id]" class="form-control">
                <option value="">Select payroll period</option>
                @foreach($payrollPeriods as $period)
                  <option value="{{ $period->id }}"
                    {{ (string)$payrollPeriodId === (string)$period->id ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::parse($period->date_from)->format('M d, Y') }}
                    -
                    {{ \Carbon\Carbon::parse($period->date_to)->format('M d, Y') }}
                  </option>
                @endforeach
              </select>
            </div>
            <small class="text-muted">
              This deduction will apply only to the selected payroll period.
            </small>
          @endif

          {{-- Recurring info --}}
          @if(in_array($dt->code, ['SSS', 'PHIC', 'PAGIBIG']))
            <small class="text-muted d-block mt-2">
              @if($dt->code === 'SSS')
                Automatically deducted on the second payroll of the month.
              @elseif(in_array($dt->code, ['PHIC', 'PAGIBIG']))
                Automatically deducted on the first payroll of the month.
              @endif
            </small>
          @endif
        </td>

        <td class="text-center">
          <input type="hidden" name="deductions[{{ $dt->id }}][is_active]" value="0">
          <input type="checkbox"
                 name="deductions[{{ $dt->id }}][is_active]"
                 value="1"
                 {{ $isActive ? 'checked' : '' }}>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="3" class="text-center text-muted">
          No deduction types found. Please add types first.
        </td>
      </tr>
    @endforelse
    </tbody>
  </table>
</div>