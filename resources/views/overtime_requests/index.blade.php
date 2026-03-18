@extends('layouts.dashboard')

@section('content')
<div id="main">
    <div class="page-heading">
        <h3>Overtime Requests</h3>
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0">Requests</h5>
                    <a href="{{ route('overtime-requests.create') }}" class="btn btn-primary">Request Overtime</a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Dates</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    <tr>
                                        <td>{{ $req->employee->fullname ?? 'N/A' }}</td>
                                        <td>{{ $req->title ?? '-' }}</td>
                                        <td>{{ ucfirst($req->status) }}</td>
                                        <td>
                                            @foreach($req->dates as $d)
                                                <div>{{ $d->ot_date->format('M d, Y') }} | {{ $d->start_time }} - {{ $d->end_time }}</div>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if($req->status === 'pending' && auth()->user()->hasPermission('overtime_requests.approve'))
                                                <form method="POST" action="{{ route('overtime-requests.approve', $req->id) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success">Approve</button>
                                                </form>

                                                <form method="POST" action="{{ route('overtime-requests.reject', $req->id) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            @else
                                                <span class="text-muted">No action</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No overtime requests found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection