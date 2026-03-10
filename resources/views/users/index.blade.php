@extends('layouts.dashboard')

@section('content')
<div id="main">
  <div class="page-heading">
    <div class="page-title">
      <div class="row">
        <div class="col-12 col-md-6 order-md-1 order-last">
          <h3>Users</h3>
          <p class="text-subtitle text-muted">Manage system accounts</p>
        </div>
      </div>
    </div>

    <section class="section">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title">User Accounts</h5>
        </div>

        <div class="card-body">
          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          <div class="table-responsive">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Employee</th>
                  <th>Actions</th>
                </tr>
              </thead>

              <tbody>
              @forelse($users as $user)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $user->name }}</td>
                  <td>{{ $user->email }}</td>
                  <td>
                    @if($user->employee)
                      <span class="badge bg-success">{{ $user->employee->fullname }}</span>
                    @else
                      <span class="badge bg-secondary">Not linked</span>
                    @endif
                  </td>
                  <td>
                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-primary">
                      Edit Permissions
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center">No users found.</td>
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