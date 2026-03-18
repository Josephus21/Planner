<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print All Payrolls</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111;
            margin: 20px;
            font-size: 12px;
        }

        .toolbar {
            margin-bottom: 20px;
        }

        .toolbar button {
            padding: 8px 14px;
            border: none;
            background: #111827;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }

        .payslip {
            page-break-after: always;
            border: 1px solid #ccc;
            padding: 20px;
            margin-bottom: 20px;
        }

        .payslip:last-child {
            page-break-after: auto;
        }

        .header {
            text-align: center;
            margin-bottom: 16px;
        }

        .header h2,
        .header p {
            margin: 0 0 4px;
        }

        .section-title {
            margin-top: 14px;
            margin-bottom: 8px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
        }

        .text-end {
            text-align: right;
        }

        @media print {
            .toolbar {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">Print All</button>
</div>

@foreach($payrolls as $row)
    @php
        $items = $row->items ?? collect();
        $earnings = $items->where('type', 'earning');
        $deductions = $items->where('type', 'deduction');
    @endphp

    <div class="payslip">
        <div class="header">
            <h2>PAYROLL SLIP</h2>
            <p>
                Period:
                {{ \Carbon\Carbon::parse($period->date_from)->format('M d, Y') }}
                -
                {{ \Carbon\Carbon::parse($period->date_to)->format('M d, Y') }}
            </p>
            <p>Status: {{ strtoupper($period->status) }}</p>
        </div>

        <div class="section-title">Employee Information</div>
        <table>
            <tr>
                <td><strong>Name</strong></td>
                <td>{{ $row->employee->fullname ?? 'N/A' }}</td>
                <td><strong>Employee ID</strong></td>
                <td>{{ $row->employee->id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Company</strong></td>
                <td>{{ $row->employee->company->name ?? 'N/A' }}</td>
                <td><strong>Days Present</strong></td>
                <td>{{ $row->days_present }}</td>
            </tr>
            <tr>
                <td><strong>Minutes Late</strong></td>
                <td>{{ $row->minutes_late }}</td>
                <td><strong>Minutes Worked</strong></td>
                <td>{{ $row->minutes_worked }}</td>
            </tr>
        </table>

        <div class="section-title">Earnings</div>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($earnings as $it)
                    <tr>
                        <td>{{ $it->name }}</td>
                        <td class="text-end">{{ number_format($it->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center">No earnings items</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">Deductions</div>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deductions as $it)
                    <tr>
                        <td>{{ $it->name }}</td>
                        <td class="text-end">{{ number_format($it->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center">No deduction items</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">Summary</div>
        <table>
            <tr>
                <td><strong>Gross Pay</strong></td>
                <td class="text-end">{{ number_format($row->gross_pay, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total Deductions</strong></td>
                <td class="text-end">{{ number_format($row->total_deductions, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Net Pay</strong></td>
                <td class="text-end"><strong>{{ number_format($row->net_pay, 2) }}</strong></td>
            </tr>
        </table>
    </div>
@endforeach

<script>
    window.onload = function () {
        window.print();
    };
</script>

</body>
</html>