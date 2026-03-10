@extends('layouts.dashboard')

@section('content')
<div id="main">
  <div class="page-heading">
    <div class="page-title">
      <div class="row">
        <div class="col-12 col-md-6 order-md-1 order-last">
          <h3>Edit User Permissions</h3>
          <p class="text-subtitle text-muted">
            {{ $user->name }} ({{ $user->email }})
          </p>
        </div>
      </div>
    </div>

    <section class="section">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title">Permissions</h5>
        </div>

        <div class="card-body">
          <form method="POST" action="{{ route('users.update', $user->id) }}">
            @csrf
            @method('PUT')

            @foreach($groups as $module => $perms)
              <div class="mb-4">
                <h6 class="mb-2 text-capitalize">{{ $module }} Module</h6>

                <div class="row">
                  @foreach($perms as $perm)
                    <div class="col-md-4">
                      <div class="form-check">
                        <input class="form-check-input"
                               type="checkbox"
                               name="permissions[]"
                               value="{{ $perm->key }}"
                               id="perm_{{ $perm->id }}"
                               {{ $user->permissions->contains('key', $perm->key) ? 'checked' : '' }}>
                        <label class="form-check-label" for="perm_{{ $perm->id }}">
                          {{ $perm->label }}
                          <div class="small text-muted">{{ $perm->key }}</div>
                        </label>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
              <hr>
            @endforeach

            <button class="btn btn-primary" type="submit">Save Permissions</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Back</a>
          </form>
        </div>
      </div>
    </section>
  </div>
</div>
@endsection