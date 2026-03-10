@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-1">Job Orders (Installation)</h3>
            <p class="text-muted mb-0">
                Source: GSuite API
                @if(!$apiOk)
                    <span class="text-danger ms-2">({{ $apiMessage }})</span>
                @endif
            </p>
        </div>

        <form method="GET" action="{{ route('sales-orders.index') }}" class="d-flex gap-2">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm"
                   placeholder="SearchKey (e.g. INSTALLATION)">
            <select name="limit" class="form-select form-select-sm" style="width:110px;">
                @foreach([10,20,50,100] as $n)
                    <option value="{{ $n }}" @selected((int)$limit === $n)>{{ $n }}/page</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary">
                <i class="bi bi-search"></i> Filter
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="text-muted">
                    Total: <strong>{{ number_format($total) }}</strong>
                </div>

                <div class="d-flex gap-2">
                    @php
                        $prev = max(1, $page - 1);
                        $next = min($lastPage, $page + 1);
                    @endphp

                    <a class="btn btn-sm btn-outline-secondary @if($page<=1) disabled @endif"
                       href="{{ route('sales-orders.index', ['page'=>$prev,'limit'=>$limit,'q'=>$q]) }}">
                        <i class="bi bi-chevron-left"></i> Prev
                    </a>

                    <span class="btn btn-sm btn-light" style="pointer-events:none;">
                        Page {{ $page }} / {{ $lastPage }}
                    </span>

                    <a class="btn btn-sm btn-outline-secondary @if($page>=$lastPage) disabled @endif"
                       href="{{ route('sales-orders.index', ['page'=>$next,'limit'=>$limit,'q'=>$q]) }}">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>JO #</th>
                            <th>SO #</th>
                            <th>Customer</th>
                            <th>Description</th>
                            <th>Prepared By</th>
                            <th>Date Created</th>
                            <th>Delivery Date</th>
                            <th>Status</th>
                            <th>GP Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr>
                                <td>{{ $r['jo_upk'] ?? '-' }}</td>
                                <td>{{ $r['so_upk'] ?? '-' }}</td>
                                <td>{{ $r['Name_Cust'] ?? '-' }}</td>
                                <td style="min-width:320px;">
                                    {{ $r['JobDescription_TransH'] ?? '-' }}
                                </td>
                                <td>{{ $r['PreparedBy_TransH'] ?? '-' }}</td>
                                <td>{{ $r['DateCreated_TransH'] ?? '-' }}</td>
                                <td>{{ $r['DeliveryDate_TransH'] ?? '-' }}</td>
                                <td>
                                    <div>{{ $r['Status_TransH'] ?? '-' }}</div>
                                    <small class="text-muted">{{ $r['SubStatus_TransH'] ?? '' }}</small>
                                </td>
                                <td>{{ isset($r['gpRate']) ? number_format((float)$r['gpRate'], 2) . '%' : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No results.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection