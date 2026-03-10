@extends('layouts.dashboard')

@section('content')
<div id="main">
  <div class="page-heading">

    {{-- HEADER --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
      <div>
        <h3 class="mb-1">{{ $project->title }}</h3>

        <p class="text-muted mb-0">
          @if($project->date_from && $project->date_to)
            {{ \Carbon\Carbon::parse($project->date_from)->format('M d, Y') }}
            -
            {{ \Carbon\Carbon::parse($project->date_to)->format('M d, Y') }}
          @else
            <span class="text-muted">No timeline set</span>
          @endif
        </p>
      </div>

      <div class="d-flex gap-2">
        @can('projects.edit')
          <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> Edit
          </a>
        @endcan

        <a href="{{ route('projects.index') }}" class="btn btn-light">
          <i class="bi bi-arrow-left"></i> Back
        </a>
      </div>
    </div>

    {{-- ALERTS --}}
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row">

      {{-- LEFT --}}
      <div class="col-md-4">

        {{-- PROJECT CARD --}}
        <div class="card">
          <div class="card-body text-center">

            @if($project->project_image)
              <img src="{{ asset('storage/'.$project->project_image) }}"
                   class="img-fluid rounded mb-3">
            @else
              <div class="text-muted mb-3">No image</div>
            @endif

            @php
              $statusColors = [
                'pending'   => 'secondary',
                'ongoing'   => 'primary',
                'on-hold'   => 'warning',
                'done'      => 'success',
                'cancelled' => 'danger'
              ];
            @endphp

            <h6 class="mb-2">
              Status:
              <span class="badge bg-{{ $statusColors[$project->status] ?? 'info' }}">
                {{ ucfirst($project->status) }}
              </span>
            </h6>

            <div class="progress mt-3" style="height:20px;">
              <div class="progress-bar bg-success"
                   style="width: {{ (int)$project->progress }}%;">
                {{ (int)$project->progress }}%
              </div>
            </div>

          </div>
        </div>

        {{-- DETAILS --}}
        <div class="card mt-3">
          <div class="card-body">
            <h6 class="mb-3">Details</h6>

            <p class="mb-1"><strong>Category:</strong>
              {{ optional($project->category)->name ?? '—' }}
            </p>

            <p class="mb-1"><strong>Vehicle(s):</strong>
              @if($project->vehicles && $project->vehicles->count())
                {{ $project->vehicles->map(function($v){
                    $label = $v->name;
                    if (!empty($v->plate_no)) $label .= ' ('.$v->plate_no.')';
                    return $label;
                })->join(', ') }}
              @else
                —
              @endif
            </p>

            <p class="mb-1"><strong>Permit Needed:</strong>
              @if($project->needs_permit)
                <span class="badge bg-warning text-dark">YES</span>
              @else
                <span class="badge bg-light border text-dark">NO</span>
              @endif
            </p>

            @if($project->needs_permit)
              <p class="mb-1"><strong>Permit File:</strong>
                @if(!empty($project->permit_path))
                  <a href="{{ asset('storage/'.$project->permit_path) }}" target="_blank">
                    View / Download
                  </a>
                @else
                  <span class="text-muted">— (no file attached)</span>
                @endif
              </p>
            @endif

            <p class="mb-1"><strong>BOM PDF:</strong>
              @if(!empty($project->bom_path))
                <a href="{{ asset('storage/'.$project->bom_path) }}" target="_blank">
                  <i class="bi bi-file-earmark-pdf"></i> View / Download
                </a>
              @else
                <span class="text-muted">—</span>
              @endif
            </p>

            <p class="mb-0"><strong>Safety Officer:</strong>
              @if($project->needs_safety_officer)
                {{ optional($project->safetyOfficer)->fullname ?? '—' }}
              @else
                <span class="text-muted">Not required</span>
              @endif
            </p>
          </div>
        </div>

        {{-- TEAM --}}
        <div class="card mt-3">
          <div class="card-body">
            <h6 class="mb-3">Team</h6>

            <p class="mb-1"><strong>Planner:</strong>
              {{ optional($project->planner)->fullname ?? '—' }}
            </p>

            <p class="mb-1"><strong>Driver:</strong>
              {{ optional($project->driver)->fullname ?? '—' }}
            </p>

            <p class="mb-1"><strong>Installers:</strong>
              @if($project->installers && $project->installers->count())
                {{ $project->installers->pluck('fullname')->join(', ') }}
              @else
                —
              @endif
            </p>

            <p class="mb-0"><strong>Subcons:</strong>
              @if($project->subcons && $project->subcons->count())
                {{ $project->subcons->pluck('fullname')->join(', ') }}
              @else
                —
              @endif
            </p>
          </div>
        </div>

      </div>

      {{-- RIGHT --}}
      <div class="col-md-8">

        {{-- QUALITY CHECKLIST --}}
        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between">
            <strong>Quality Checklist</strong>
            <span class="text-muted" style="font-size:12px;">
              Category: {{ optional($project->category)->name ?? '—' }}
            </span>
          </div>

          <div class="card-body">

            @if(!$project->category)
              <p class="text-muted mb-0">No category assigned.</p>

            @elseif($project->category->checklists->count() == 0)
              <p class="text-muted mb-0">No checklist items for this category.</p>

            @else
              <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                  <thead class="table-light">
                    <tr>
                      <th width="60">#</th>
                      <th>Checklist Item</th>
                      <th width="190">Done Date</th>
                      <th width="120" class="text-center">Status</th>
                    </tr>
                  </thead>

                  <tbody>
                    @foreach($project->category->checklists as $c)
                      <tr>
                        <td>{{ $c->sort_order }}</td>

                        <td>
                          {{ $c->item }}
                          @if($c->is_required)
                            <span class="badge bg-danger ms-2">Required</span>
                          @endif
                        </td>

                        <td>
                          @if(!empty($c->done_at))
                            {{ \Carbon\Carbon::parse($c->done_at)->format('M d, Y h:i A') }}
                          @else
                            —
                          @endif
                        </td>

                        <td class="text-center">
                          @if(!empty($c->done_at))
                            <span class="badge bg-success">
                              <i class="bi bi-check2-circle"></i> DONE
                            </span>
                          @else
                            <form method="POST" action="{{ route('quality-checklists.done', $c->id) }}" class="d-inline">
                              @csrf
                              <button type="submit"
                                      class="btn btn-sm btn-outline-success"
                                      title="Mark as DONE">
                                <i class="bi bi-check-circle"></i>
                              </button>
                            </form>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>

                </table>
              </div>
            @endif

          </div>
        </div>

     {{-- BOM ITEMS --}}
<div class="card mt-3">
  <div class="card-header d-flex align-items-center justify-content-between">
    <strong>BOM Items</strong>
    <span class="text-muted" style="font-size:12px;">
      {{ $project->bomItems?->count() ?? 0 }} item(s)
    </span>
  </div>

  <div class="card-body">

    @if(empty($project->bom_path))
      <p class="text-muted mb-0">No BOM PDF attached.</p>
    @else

      {{-- Buttons row --}}
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-secondary"
             href="{{ asset('storage/'.$project->bom_path) }}"
             target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> View BOM PDF
          </a>

          @if(Route::has('projects.bom-items.reparse'))
            <form method="POST" action="{{ route('projects.bom-items.reparse', $project->id) }}">
              @csrf
              <button class="btn btn-sm btn-outline-primary" type="submit">
                <i class="bi bi-arrow-repeat"></i> Re-Parse
              </button>
            </form>
          @endif
        </div>

        {{-- Add Item --}}
        @if(Route::has('projects.bom-items.store'))
          <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addBomItemModal">
            <i class="bi bi-plus-circle"></i> Add Item
          </button>
        @endif
      </div>

      {{-- Table --}}
      @if(($project->bomItems?->count() ?? 0) === 0)
        <p class="text-muted mb-0">BOM PDF is attached but no items were generated yet.</p>
      @else
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Item</th>
                <th style="width: 170px;">Status</th>
                <th style="width: 190px;">Done Date</th>
                @if(Route::has('projects.bom-items.destroy'))
                  <th style="width: 90px;" class="text-center">Action</th>
                @endif
              </tr>
            </thead>

            <tbody>
              @foreach($project->bomItems as $bi)
                <tr>
                  <td>{{ $bi->item }}</td>

                  <td>
                    @if(Route::has('projects.bom-items.status'))
                      <form method="POST" action="{{ route('projects.bom-items.status', [$project->id, $bi->id]) }}">
                        @csrf
                        <select name="status"
                                class="form-select form-select-sm"
                                onchange="this.form.submit()">
                          <option value="not_ready" @selected($bi->status === 'not_ready')>Not Ready</option>
                          <option value="ready"     @selected($bi->status === 'ready')>Ready</option>
                          <option value="done"      @selected($bi->status === 'done')>Done</option>
                        </select>
                      </form>
                    @else
                      <span class="badge bg-secondary">
                        {{ strtoupper(str_replace('_',' ', $bi->status ?? 'not_ready')) }}
                      </span>
                    @endif
                  </td>

                  <td>
                    @if(!empty($bi->done_at))
                      {{ optional($bi->done_at)->format('M d, Y h:i A') ?? \Carbon\Carbon::parse($bi->done_at)->format('M d, Y h:i A') }}
                    @else
                      —
                    @endif
                  </td>

                 @can('bom_items.delete')
<td class="text-center">
  <form method="POST"
        action="{{ route('projects.bom-items.destroy', [$project->id, $bi->id]) }}"
        onsubmit="return confirm('Delete this BOM item?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-sm btn-outline-danger">
      <i class="bi bi-trash"></i>
    </button>
  </form>
</td>
@endcan
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @php
          $items = $project->bomItems ?? collect();
          $nr = $items->where('status','not_ready')->count();
          $r  = $items->where('status','ready')->count();
          $d  = $items->where('status','done')->count();
          $t  = max(1, $items->count());
          $pct = (int) round(($d / $t) * 100);
        @endphp

        <div class="mt-3">
          <div class="d-flex justify-content-between">
            <small class="text-muted">Not Ready: {{ $nr }} | Ready: {{ $r }} | Done: {{ $d }}</small>
            <small class="text-muted">{{ $pct }}% Done</small>
          </div>
          <div class="progress mt-1" style="height: 16px;">
            <div class="progress-bar" style="width: {{ $pct }}%;">{{ $pct }}%</div>
          </div>
        </div>
      @endif

    @endif

  </div>
</div>

{{-- Add BOM Item Modal --}}
@if(Route::has('projects.bom-items.store'))
<div class="modal fade" id="addBomItemModal" tabindex="-1" aria-labelledby="addBomItemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="addBomItemModalLabel">Add BOM Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="POST" action="{{ route('projects.bom-items.store', $project->id) }}">
        @csrf

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Item</label>
            <textarea name="item" class="form-control" rows="3" required
                      placeholder="Type the BOM item here...">{{ old('item') }}</textarea>
          </div>

          <div class="mb-0">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="not_ready" @selected(old('status','not_ready')==='not_ready')>Not Ready</option>
              <option value="ready"     @selected(old('status')==='ready')>Ready</option>
              <option value="done"      @selected(old('status')==='done')>Done</option>
            </select>
            <small class="text-muted">If you choose Done, Done Date will be set automatically.</small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-success" type="submit">
            <i class="bi bi-plus-circle"></i> Add Item
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
@endif
        {{-- UPDATE PROGRESS --}}
        @can('project_updates.create')
        <div class="card mt-3">
          <div class="card-header">Update Progress</div>
          <div class="card-body">

            <form action="{{ route('projects.updateProgress', $project->id) }}"
                  method="POST" enctype="multipart/form-data">
              @csrf

              <div class="mb-3">
                <label class="form-label">Progress Percentage</label>
                <input type="number"
                       name="percentage"
                       value="{{ old('percentage') }}"
                       class="form-control"
                       min="0" max="100"
                       required>
                <small class="text-muted">Adds to the current progress.</small>
              </div>

              <div class="mb-3">
                <label class="form-label">Note</label>
                <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
              </div>

              <div class="mb-3">
                <label class="form-label">Upload Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
              </div>

              <button class="btn btn-success">
                <i class="bi bi-check2-circle"></i> Update
              </button>
            </form>

          </div>
        </div>
        @endcan

        {{-- UPDATE HISTORY --}}
        <div class="card mt-3">
          <div class="card-header d-flex align-items-center justify-content-between">
            <strong>Update History</strong>
            <span class="text-muted" style="font-size:12px;">
              {{ $project->updates->count() }} record(s)
            </span>
          </div>

          <div class="card-body">

            @forelse($project->updates as $update)
              <div class="border rounded p-3 mb-3">

                <div class="d-flex align-items-start justify-content-between gap-2">
                  <div>
                    <div class="fw-bold">
                      +{{ (int)($update->percent ?? 0) }}%
                    </div>
                    <div class="text-muted" style="font-size: 12px;">
                      {{ optional($update->created_at)->format('M d, Y h:i A') ?? '—' }}
                    </div>
                  </div>

                  @if(!empty($update->photo))
                    <button class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#progressPhotoModal{{ $update->id }}">
                      <i class="bi bi-image"></i> View Photo
                    </button>
                  @endif
                </div>

                @if(!empty($update->note))
                  <div class="mt-2 text-muted">{{ $update->note }}</div>
                @endif

                <div class="mt-2 text-muted" style="font-size: 12px;">
                  Updated by: {{ optional($update->user)->name ?? '—' }}
                </div>

              </div>

              {{-- Modal per update --}}
              @if(!empty($update->photo))
                <div class="modal fade" id="progressPhotoModal{{ $update->id }}" tabindex="-1"
                     aria-labelledby="progressPhotoModalLabel{{ $update->id }}" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">

                      <div class="modal-header">
                        <h5 class="modal-title" id="progressPhotoModalLabel{{ $update->id }}">
                          Progress: +{{ (int)($update->percent ?? 0) }}%
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                      </div>

                      <div class="modal-body text-center">
                        <img src="{{ asset('storage/'.$update->photo) }}"
                             class="img-fluid rounded">

                        @if(!empty($update->note))
                          <div class="mt-3 text-start">
                            <strong>Note:</strong>
                            <div class="text-muted">{{ $update->note }}</div>
                          </div>
                        @endif
                      </div>

                    </div>
                  </div>
                </div>
              @endif

            @empty
              <p class="text-muted mb-0">No progress updates yet.</p>
            @endforelse

          </div>
        </div>

      </div> {{-- /col-md-8 --}}
    </div> {{-- /row --}}

  </div> {{-- /page-heading --}}
</div> {{-- /main --}}
@endsection