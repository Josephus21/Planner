@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>Edit Project</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <form action="{{ route('projects.update',$project->id) }}"
                  method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">

                    {{-- Title --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">Project Title</label>
                        <input type="text"
                               name="title"
                               class="form-control"
                               value="{{ old('title', $project->title) }}"
                               required>
                    </div>

                    {{-- Change Project Picture --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">Change Project Picture</label>
                        <input type="file" name="project_image" class="form-control" accept="image/*">
                        <small class="text-muted">jpg/png/webp up to 4MB</small>
                    </div>
{{-- ? BOM PDF --}}
<div class="col-12 col-md-6">
    <label class="form-label">BOM (PDF)</label>
    <input type="file" name="bom_pdf" class="form-control" accept="application/pdf">
    <small class="text-muted">
        Upload a new BOM/Cutting List PDF (up to 10MB). Uploading will re-parse and replace BOM items.
    </small>

    {{-- current BOM --}}
    @if(!empty($project->bom_path))
        <div class="mt-2">
            <small class="text-muted d-block">Current BOM:</small>
            <a href="{{ asset('storage/'.$project->bom_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                View BOM PDF
            </a>

            <div class="form-check mt-2">
                <input class="form-check-input"
                       type="checkbox"
                       name="remove_bom"
                       id="remove_bom"
                       value="1"
                       @checked(old('remove_bom'))>
                <label class="form-check-label" for="remove_bom">
                    Remove current BOM
                </label>
            </div>
        </div>
    @endif
</div>


                    {{-- Current Image --}}
                    <div class="col-12">
                        @if($project->project_image)
                            <label class="form-label d-block">Current Image</label>
                            <img src="{{ asset('storage/'.$project->project_image) }}"
                                 width="180" class="rounded border mb-2">
                        @else
                            <div class="text-muted">No image uploaded</div>
                        @endif
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $project->description) }}</textarea>
                    </div>

                    {{-- Dates --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">Start Date</label>
                        <input type="date"
                               name="date_from"
                               value="{{ old('date_from', $project->date_from ? \Carbon\Carbon::parse($project->date_from)->format('Y-m-d') : null) }}"
                               class="form-control">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">End Date</label>
                        <input type="date"
                               name="date_to"
                               value="{{ old('date_to', $project->date_to ? \Carbon\Carbon::parse($project->date_to)->format('Y-m-d') : null) }}"
                               class="form-control">
                    </div>

                    {{-- Category --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label">Project Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    @selected(old('category_id', $project->category_id) == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Vehicles multi dropdown --}}
<div class="col-12 col-md-6">
  <label class="form-label">Car/Truck (select multiple)</label>

  <div class="dropdown w-100">
    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
            type="button"
            id="vehiclesDropdownBtn"
            data-bs-toggle="dropdown"
            aria-expanded="false">
      <span id="vehiclesSelectedText">Select vehicle(s)</span>
    </button>

    <div class="dropdown-menu w-100 p-2"
         aria-labelledby="vehiclesDropdownBtn"
         style="max-height: 300px; overflow:auto;">
      <div class="px-2 pb-2">
        <input type="text"
               class="form-control form-control-sm"
               id="vehiclesSearch"
               placeholder="Search vehicle...">
      </div>

      <div id="vehiclesList">
        @php
          // Create page:
          $selectedVehicleIds = collect(old('vehicles', isset($project) ? ($project->vehicles?->pluck('id')->toArray() ?? []) : []));
        @endphp

        @forelse($vehicles as $v)
          <label class="dropdown-item d-flex align-items-center gap-2" style="cursor:pointer;">
            <input type="checkbox"
                   class="form-check-input vehicle-checkbox"
                   value="{{ $v->id }}"
                   data-name="{{ $v->name }}"
                   {{ $selectedVehicleIds->contains($v->id) ? 'checked' : '' }}>
            <span>
              {{ $v->name }}
              <small class="text-muted">({{ strtoupper($v->type) }}{{ $v->plate_no ? ' • '.$v->plate_no : '' }})</small>
            </span>
          </label>
        @empty
          <div class="text-muted px-3 py-2">No vehicles found.</div>
        @endforelse
      </div>
    </div>
  </div>

  <div id="vehiclesHiddenInputs"></div>
  <small class="text-muted">Tick multiple vehicles.</small>
</div>

                    {{-- Permit --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Permit</label>

                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="needs_permit"
                                   id="needs_permit"
                                   value="1"
                                   @checked(old('needs_permit', $project->needs_permit))>
                            <label class="form-check-label" for="needs_permit">
                                Needs Permit
                            </label>
                        </div>

                        {{-- current permit --}}
                        @if(!empty($project->permit_path))
                            <div class="mt-2">
                                <small class="text-muted d-block">Current Permit:</small>
                                <a href="{{ asset('storage/'.$project->permit_path) }}" target="_blank">
                                    View / Download
                                </a>

                                <div class="form-check mt-2">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="remove_permit"
                                           id="remove_permit"
                                           value="1"
                                           @checked(old('remove_permit'))>
                                    <label class="form-check-label" for="remove_permit">
                                        Remove current permit
                                    </label>
                                </div>
                            </div>
                        @endif

                        <div id="permitUploadWrap" class="mt-2" style="display:none;">
                            <input type="file"
                                   name="permit_file"
                                   class="form-control"
                                   accept="application/pdf,image/*">
                            <small class="text-muted">Upload new permit (PDF/image).</small>
                        </div>
                    </div>

                    {{-- Planner (read-only display) --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label">Planner</label>
                        <input type="text"
                               class="form-control"
                               value="{{ optional($project->planner)->fullname ?? '—' }}"
                               readonly>
                        <small class="text-muted">Planner is auto-set (not editable).</small>
                    </div>

                    {{-- Driver --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label">Driver</label>
                        <select name="driver_id" class="form-select">
                            <option value="">-- Select Driver --</option>
                            @foreach($installers as $e)
                                <option value="{{ $e->id }}"
                                    @selected(old('driver_id', $project->driver_id) == $e->id)>
                                    {{ $e->fullname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Installers --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label">Installer(s)</label>

                        <div class="dropdown w-100">
                            <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
                                    type="button"
                                    id="installersDropdownBtn"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <span id="installersSelectedText">Select installer(s)</span>
                            </button>

                            <div class="dropdown-menu w-100 p-2"
                                 aria-labelledby="installersDropdownBtn"
                                 style="max-height: 300px; overflow:auto;">
                                <div class="px-2 pb-2">
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           id="installersSearch"
                                           placeholder="Search employee...">
                                </div>

                                <div id="installersList">
                                    @forelse($installers as $e)
                                        @php
                                            $selectedInstallers = collect(old('installers', $project->installers->pluck('id')->toArray()));
                                            $checked = $selectedInstallers->contains($e->id);
                                        @endphp

                                        <label class="dropdown-item d-flex align-items-center gap-2" style="cursor:pointer;">
                                            <input type="checkbox"
                                                   class="form-check-input installer-checkbox"
                                                   value="{{ $e->id }}"
                                                   data-name="{{ $e->fullname }}"
                                                   {{ $checked ? 'checked' : '' }}>
                                            <span>{{ $e->fullname }}</span>
                                        </label>
                                    @empty
                                        <div class="text-muted px-3 py-2">
                                            No installers found for <strong>PRODUCTION - SIGNAGE</strong>.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div id="installersHiddenInputs"></div>
                        <small class="text-muted">Click dropdown, tick multiple installers.</small>
                    </div>

                    {{-- Subcons --}}
                    <div class="col-12 col-md-6">
                      <label class="form-label">Subcon(s) Involved</label>

                      <div class="dropdown w-100">
                        <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
                                type="button"
                                id="subconsDropdownBtn"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                          <span id="subconsSelectedText">Select subcon(s)</span>
                        </button>

                        <div class="dropdown-menu w-100 p-2"
                             aria-labelledby="subconsDropdownBtn"
                             style="max-height: 300px; overflow:auto;">
                          <div class="px-2 pb-2">
                            <input type="text"
                                   class="form-control form-control-sm"
                                   id="subconsSearch"
                                   placeholder="Search subcon...">
                          </div>

                          <div id="subconsList">
                            @php
                              $selectedSubconIds = collect(old('subcons', $project->subcons?->pluck('id')->toArray() ?? []));
                            @endphp

                            @forelse($subcons as $sc)
                              <label class="dropdown-item d-flex align-items-center gap-2" style="cursor:pointer;">
                                <input type="checkbox"
                                       class="form-check-input subcon-checkbox"
                                       value="{{ $sc->id }}"
                                       data-name="{{ $sc->fullname }}"
                                       {{ $selectedSubconIds->contains($sc->id) ? 'checked' : '' }}>
                                <span>{{ $sc->fullname }}</span>
                              </label>
                            @empty
                              <div class="text-muted px-3 py-2">
                                No SUBCON employees found.
                              </div>
                            @endforelse
                          </div>
                        </div>
                      </div>

                      <div id="subconsHiddenInputs"></div>
                      <small class="text-muted">Click dropdown, tick multiple subcon(s).</small>
                    </div>

                    {{-- Safety Officer --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Safety Officer</label>

                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="needs_safety_officer"
                                   id="needs_safety_officer"
                                   value="1"
                                   @checked(old('needs_safety_officer', $project->needs_safety_officer))>
                            <label class="form-check-label" for="needs_safety_officer">
                                Assign Safety Officer
                            </label>
                        </div>

                        <div id="safetyOfficerSelectWrap" class="mt-2" style="display:none;">
                            <select name="safety_officer_id" class="form-select">
                                <option value="">-- Select Safety Officer --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}"
                                        @selected(old('safety_officer_id', $project->safety_officer_id) == $emp->id)>
                                        {{ $emp->fullname }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Required if checked.</small>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">Status</label>
                        @php $curStatus = old('status', $project->status); @endphp
                        <select name="status" class="form-select" required>
                            <option value="pending"   @selected($curStatus=='pending')>Pending</option>
                            <option value="ongoing"   @selected($curStatus=='ongoing')>Ongoing</option>
                            <option value="done"      @selected($curStatus=='done')>Done</option>
                            <option value="cancelled" @selected($curStatus=='cancelled')>Cancelled</option>
                        </select>
                    </div>

                    {{-- Progress --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">Progress (%)</label>
                        <input type="number"
                               name="progress"
                               min="0"
                               max="100"
                               value="{{ old('progress', $project->progress) }}"
                               class="form-control">
                    </div>

                </div>

                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> Update Project
                    </button>
                    <a href="{{ route('projects.show', $project->id) }}" class="btn btn-light">Cancel</a>
                </div>

            </form>

        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {

    // ============ GENERIC MULTI-DROPDOWN HELPER ============
    function initMultiDropdown(opts) {
        const hiddenWrap   = document.getElementById(opts.hiddenWrapId);
        const selectedText = document.getElementById(opts.selectedTextId);
        const checkboxes   = Array.from(document.querySelectorAll(opts.checkboxSelector));
        const search       = document.getElementById(opts.searchInputId);

        function renderHiddenInputs() {
            if (!hiddenWrap) return;
            hiddenWrap.innerHTML = '';
            checkboxes
                .filter(cb => cb.checked)
                .forEach(cb => {
                    const input = document.createElement('input');
                    input.type  = 'hidden';
                    input.name  = opts.inputName; // installers[] / subcons[] / vehicles[]
                    input.value = cb.value;
                    hiddenWrap.appendChild(input);
                });
        }

        function renderSelectedText() {
            if (!selectedText) return;
            const selected = checkboxes
                .filter(cb => cb.checked)
                .map(cb => cb.dataset.name)
                .filter(Boolean);

            if (selected.length === 0) {
                selectedText.textContent = opts.placeholder;
            } else if (selected.length <= 2) {
                selectedText.textContent = selected.join(', ');
            } else {
                selectedText.textContent = `${selected.length} selected`;
            }
        }

        function syncAll() {
            renderHiddenInputs();
            renderSelectedText();
        }

        // Initial
        syncAll();

        // Change
        checkboxes.forEach(cb => cb.addEventListener('change', syncAll));

        // Search
        if (search) {
            search.addEventListener('input', function () {
                const q = this.value.toLowerCase().trim();
                checkboxes.forEach(cb => {
                    const label = cb.closest('label');
                    const name = (cb.dataset.name || '').toLowerCase();
                    if (label) label.style.display = name.includes(q) ? '' : 'none';
                });
            });
        }
    }

    // ============ INSTALLERS ============
    initMultiDropdown({
        hiddenWrapId: 'installersHiddenInputs',
        selectedTextId: 'installersSelectedText',
        checkboxSelector: '.installer-checkbox',
        searchInputId: 'installersSearch',
        inputName: 'installers[]',
        placeholder: 'Select installer(s)'
    });

    // ============ SUBCONS ============
    initMultiDropdown({
        hiddenWrapId: 'subconsHiddenInputs',
        selectedTextId: 'subconsSelectedText',
        checkboxSelector: '.subcon-checkbox',
        searchInputId: 'subconsSearch',
        inputName: 'subcons[]',
        placeholder: 'Select subcon(s)'
    });

    // ============ VEHICLES (? MISSING IN YOUR EDIT PAGE) ============
    initMultiDropdown({
        hiddenWrapId: 'vehiclesHiddenInputs',
        selectedTextId: 'vehiclesSelectedText',
        checkboxSelector: '.vehicle-checkbox',
        searchInputId: 'vehiclesSearch',
        inputName: 'vehicles[]',
        placeholder: 'Select vehicle(s)'
    });

    // ============ Permit / Safety toggles ============
    const permitCheckbox = document.getElementById('needs_permit');
    const permitWrap     = document.getElementById('permitUploadWrap');

    const removePermitCheckbox = document.getElementById('remove_permit'); // might be null if no current permit

    const safetyCheckbox = document.getElementById('needs_safety_officer');
    const safetyWrap     = document.getElementById('safetyOfficerSelectWrap');

    function togglePermit() {
        if (!permitCheckbox || !permitWrap) return;

        // show upload only if needs_permit checked AND not removing current permit (optional UX)
        const removing = !!removePermitCheckbox?.checked;
        permitWrap.style.display = (permitCheckbox.checked && !removing) ? '' : 'none';
    }

    function toggleSafety() {
        if (!safetyCheckbox || !safetyWrap) return;
        safetyWrap.style.display = safetyCheckbox.checked ? '' : 'none';
    }

    togglePermit();
    toggleSafety();

    permitCheckbox?.addEventListener('change', togglePermit);
    removePermitCheckbox?.addEventListener('change', togglePermit);
    safetyCheckbox?.addEventListener('change', toggleSafety);

})();
</script>
@endpush

@endsection