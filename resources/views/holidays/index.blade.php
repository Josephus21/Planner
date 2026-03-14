@extends('layouts.dashboard')

@section('content')
<div id="main">
    <div class="page-heading">
        <h3>Holidays</h3>
        <p class="text-subtitle text-muted">Manage regular and special holidays.</p>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Holiday List</h5>
                <a href="{{ route('holidays.create') }}" class="btn btn-primary">Add Holiday</a>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Company</th>
                                <th>Recurring</th>
                                <th>Status</th>
                                <th width="180">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($holidays as $holiday)
                                <tr>
                                    <td>{{ $holiday->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($holiday->holiday_date)->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $holiday->type === 'regular' ? 'danger' : 'warning' }}">
                                            {{ ucfirst($holiday->type) }}
                                        </span>
                                    </td>
                                    <td>{{ $holiday->company->name ?? 'All Companies' }}</td>
                                    <td>{{ $holiday->is_recurring ? 'Yes' : 'No' }}</td>
                                    <td>{{ $holiday->is_active ? 'Active' : 'Inactive' }}</td>
                                    <td>
                                        <a href="{{ route('holidays.edit', $holiday->id) }}" class="btn btn-sm btn-primary">Edit</a>

                                        <form action="{{ route('holidays.destroy', $holiday->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Delete this holiday?')">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No holidays found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection