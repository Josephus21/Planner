  @extends('layouts.dashboard')

  @section('content')
  <div id="main">
    <div class="page-heading">
      <h3>Create Project</h3>

      @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
      @endif

      <div class="card">
        <div class="card-body">
          <form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">

              {{-- ? SO Picker (Modal with Search) --}}
              <div class="col-12 col-md-6">
                <label class="form-label">SO Number</label>

                <input type="hidden" name="sales_order_id" id="sales_order_id" value="{{ old('sales_order_id') }}">

                <div class="input-group">
                  <input type="text"
                        class="form-control"
                        id="sales_order_display"
                        value=""
                        placeholder="Select SO..."
                        readonly>
                  <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#soPickerModal">
                    Select
                  </button>
                  <button type="button" class="btn btn-outline-danger" id="clearSoBtn" title="Clear SO">
                    Clear
                  </button>
                </div>

                <small class="text-muted">Pick an SO to auto-generate title from JO + description.</small>
              </div>

              {{-- Project Title --}}
              <div class="col-12 col-md-6">
                <label class="form-label">Project Title</label>
                <input type="text" name="title" id="projectTitle" class="form-control" value="{{ old('title') }}" required>
              </div>

              {{-- Project Picture --}}
              <div class="col-12 col-md-6">
                <label class="form-label">Project Picture</label>
                <input type="file" name="project_image" class="form-control" accept="image/*">
                <small class="text-muted">jpg/png/webp up to 4MB</small>
              </div>

              {{-- ? BOM PDF --}}
              <div class="col-12 col-md-6">
                <label class="form-label">BOM (PDF)</label>
                <input type="file" name="bom_pdf" class="form-control" accept="application/pdf">
                <small class="text-muted">Attach BOM/Cutting List PDF (up to 10MB). Items will appear in project view.</small>
              </div>

              {{-- Description --}}
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" id="projectDescription" class="form-control" rows="3">{{ old('description') }}</textarea>
              </div>

              {{-- Timeline --}}
              <div class="col-12 col-md-6">
                <label class="form-label">Timeline From</label>
                <input type="date" name="date_from" class="form-control" value="{{ old('date_from') }}">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Timeline To</label>
                <input type="date" name="date_to" class="form-control" value="{{ old('date_to') }}">
              </div>

              {{-- Category --}}
              <div class="col-12 col-md-4">
                <label class="form-label">Project Category</label>
                <select name="category_id" class="form-select" required>
                  <option value="">-- Select Category --</option>
                  @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
                      {{ $cat->name }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Used to load the correct quality checklist.</small>
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
                      @php $selectedVehicleIds = collect(old('vehicles', [])); @endphp

                      @forelse($vehicles as $v)
                        @php $vid = 'veh_'.$v->id; @endphp
                        <label class="dropdown-item d-flex align-items-center gap-2" for="{{ $vid }}" style="cursor:pointer;">
                          <input type="checkbox"
                                id="{{ $vid }}"
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
                        @checked(old('needs_permit'))>
                  <label class="form-check-label" for="needs_permit">
                    Needs Permit
                  </label>
                </div>

                <div id="permitUploadWrap" class="mt-2" style="display:none;">
                  <input type="file"
                        name="permit_file"
                        class="form-control"
                        accept="application/pdf,image/*">
                  <small class="text-muted">Attach permit (PDF/image). Required if checked.</small>
                </div>
              </div>

              {{-- Planner (auto display) --}}
              <div class="col-12 col-md-4">
                <label class="form-label">Planner</label>
                <input type="text" class="form-control"
                      value="{{ auth()->user()->employee?->fullname ?? auth()->user()->name }}"
                      readonly>
                <small class="text-muted">Auto-filled as the creator of this project</small>
              </div>

              {{-- Driver --}}
              <div class="col-12 col-md-4">
                <label class="form-label">Driver</label>
                <select name="driver_id" class="form-select">
                  <option value="">-- Select Driver --</option>
                  @foreach($installers as $e)
                    <option value="{{ $e->id }}" @selected(old('driver_id') == $e->id)>
                      {{ $e->fullname }}
                    </option>
                  @endforeach
                </select>
              </div>

              {{-- Installers multi dropdown --}}
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
                          $checked = collect(old('installers', []))->contains($e->id);
                          $iid = 'ins_'.$e->id;
                        @endphp
                        <label class="dropdown-item d-flex align-items-center gap-2" for="{{ $iid }}" style="cursor:pointer;">
                          <input type="checkbox"
                                id="{{ $iid }}"
                                class="form-check-input installer-checkbox"
                                value="{{ $e->id }}"
                                data-name="{{ $e->fullname }}"
                                {{ $checked ? 'checked' : '' }}>
                          <span>{{ $e->fullname }}</span>
                        </label>
                      @empty
                        <div class="text-muted px-3 py-2">No installers found.</div>
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
                      @php $subconSelectedIds = collect(old('subcons', [])); @endphp

                      @forelse($subcons as $sc)
                        @php
                          $checked = $subconSelectedIds->contains($sc->id);
                          $sid = 'sub_'.$sc->id;
                        @endphp

                        <label class="dropdown-item d-flex align-items-center gap-2" for="{{ $sid }}" style="cursor:pointer;">
                          <input type="checkbox"
                                id="{{ $sid }}"
                                class="form-check-input subcon-checkbox"
                                value="{{ $sc->id }}"
                                data-name="{{ $sc->fullname }}"
                                {{ $checked ? 'checked' : '' }}>
                          <span>{{ $sc->fullname }}</span>
                        </label>
                      @empty
                        <div class="text-muted px-3 py-2">No SUBCON employees found.</div>
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
                        @checked(old('needs_safety_officer'))>
                  <label class="form-check-label" for="needs_safety_officer">
                    Assign Safety Officer
                  </label>
                </div>

                <div id="safetyOfficerSelectWrap" class="mt-2" style="display:none;">
                  <select name="safety_officer_id" class="form-select">
                    <option value="">-- Select Safety Officer --</option>
                    @foreach($employees as $emp)
                      <option value="{{ $emp->id }}" @selected(old('safety_officer_id') == $emp->id)>
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
                @php
                  $statuses = [
                    'pending'   => 'Pending',
                    'ongoing'   => 'Ongoing',
                    'on-hold'   => 'On Hold',
                    'done'      => 'Done',
                    'cancelled' => 'Cancelled',
                  ];
                  $oldStatus = old('status', 'pending');
                @endphp
                <select name="status" class="form-select" required>
                  @foreach($statuses as $val => $label)
                    <option value="{{ $val }}" @selected($oldStatus === $val)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>

              {{-- Progress --}}
              <div class="col-12 col-md-6">
                <label class="form-label">Progress (%)</label>
                <input type="number" name="progress" class="form-control" min="0" max="100" value="{{ old('progress', 0) }}">
              </div>

            </div>

            <div class="mt-3 d-flex gap-2">
              <button class="btn btn-primary">Save Project</button>
              <a href="{{ url()->previous() }}" class="btn btn-light">Back</a>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- ? SO Picker Modal --}}
  <div class="modal fade" id="soPickerModal" tabindex="-1" aria-labelledby="soPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title" id="soPickerModalLabel">Select Sales Order (SO)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">

          <div class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-6">
              <input type="text" class="form-control" id="soSearchInput"
                    placeholder="Search SO / JO / customer / description...">
            </div>
            <div class="col-12 col-md-6 text-md-end">
              <small class="text-muted">Click a row to select.</small>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover table-sm align-middle" id="soTable">
              <thead>
                <tr>
                  <th style="width: 140px;">SO No</th>
                  <th style="width: 140px;">JO No</th>
                  <th style="width: 220px;">Customer</th>
                  <th>Description</th>
                  <th style="width: 120px;">Delivery</th>
                  <th style="width: 120px;">Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($salesOrders as $so)
                  @php
                    $desc = $so->description ?? '';
                    $cust = $so->customer_name ?? '';
                    $delivery = $so->delivery_date ? \Illuminate\Support\Carbon::parse($so->delivery_date)->format('Y-m-d') : '';
                    $status = $so->status ?? '';
                  @endphp

                  <tr class="so-row"
                      style="cursor:pointer;"
                      data-id="{{ $so->id }}"
                      data-so="{{ $so->so_no }}"
                      data-jo="{{ $so->jo_no }}"
                      data-customer="{{ e($cust) }}"
                      data-desc="{{ e($desc) }}"
                      data-delivery="{{ $delivery }}"
                      data-status="{{ e($status) }}">
                    <td><strong>{{ $so->so_no }}</strong></td>
                    <td>{{ $so->jo_no }}</td>
                    <td>{{ $cust }}</td>
                    <td class="text-truncate" style="max-width: 520px;">{{ $desc }}</td>
                    <td>{{ $delivery }}</td>
                    <td>{{ $status }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        </div>

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
                      input.name  = opts.inputName;
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

          syncAll();
          checkboxes.forEach(cb => cb.addEventListener('change', syncAll));

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

      // ============ VEHICLES ============
      initMultiDropdown({
          hiddenWrapId: 'vehiclesHiddenInputs',
          selectedTextId: 'vehiclesSelectedText',
          checkboxSelector: '.vehicle-checkbox',
          searchInputId: 'vehiclesSearch',
          inputName: 'vehicles[]',
          placeholder: 'Select vehicle(s)'
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

      // ============ Permit / Safety conditional UI ============
      const permitCheckbox = document.getElementById('needs_permit');
      const permitWrap     = document.getElementById('permitUploadWrap');

      const safetyCheckbox = document.getElementById('needs_safety_officer');
      const safetyWrap     = document.getElementById('safetyOfficerSelectWrap');

      function togglePermit() {
          if (!permitCheckbox || !permitWrap) return;
          permitWrap.style.display = permitCheckbox.checked ? '' : 'none';
      }

      function toggleSafety() {
          if (!safetyCheckbox || !safetyWrap) return;
          safetyWrap.style.display = safetyCheckbox.checked ? '' : 'none';
      }

      togglePermit();
      toggleSafety();

      permitCheckbox?.addEventListener('change', togglePermit);
      safetyCheckbox?.addEventListener('change', toggleSafety);

      // ============ SO Picker (Modal + Search + Auto Title) ============
      const soModalEl = document.getElementById('soPickerModal');
      const soSearchInput = document.getElementById('soSearchInput');
      const soTable = document.getElementById('soTable');

      const soHidden = document.getElementById('sales_order_id');
      const soDisplay = document.getElementById('sales_order_display');
      const clearBtn = document.getElementById('clearSoBtn');

      const titleInput = document.getElementById('projectTitle');
      const descInput  = document.getElementById('projectDescription');

      let autoFilledTitle = false;
      let autoFilledDesc  = false;

      function normalizeSpaces(str) {
        return (str || '').replace(/\s+/g, ' ').trim();
      }

      function buildTitle(jo, desc) {
        return normalizeSpaces([jo, normalizeSpaces(desc)].filter(Boolean).join(' - '));
      }

      function setSelectedSO(row) {
        const id = row.dataset.id || '';
        const soNo = row.dataset.so || '';
        const joNo = row.dataset.jo || '';
        const desc = row.dataset.desc || '';

        soHidden.value = id;

        const displayText = normalizeSpaces([soNo, joNo].filter(Boolean).join(' • '));
        soDisplay.value = displayText || soNo || 'Selected';

        const generatedTitle = buildTitle(joNo, desc);

        if (titleInput && titleInput.value.trim() === '') {
          titleInput.value = generatedTitle;
          autoFilledTitle = true;
        }

        if (descInput && descInput.value.trim() === '' && normalizeSpaces(desc) !== '') {
          descInput.value = normalizeSpaces(desc);
          autoFilledDesc = true;
        }

        const modal = bootstrap.Modal.getInstance(soModalEl);
        modal?.hide();
      }

      document.querySelectorAll('.so-row').forEach(row => {
        row.addEventListener('click', () => setSelectedSO(row));
      });

      function applySoFilter() {
        const q = (soSearchInput?.value || '').toLowerCase().trim();
        const rows = soTable?.querySelectorAll('tbody tr') || [];
        rows.forEach(tr => {
          const hay = [
            tr.dataset.so,
            tr.dataset.jo,
            tr.dataset.customer,
            tr.dataset.desc,
            tr.dataset.status,
            tr.dataset.delivery,
          ].join(' ').toLowerCase();

          tr.style.display = hay.includes(q) ? '' : 'none';
        });
      }

      soSearchInput?.addEventListener('input', applySoFilter);

      clearBtn?.addEventListener('click', function () {
        soHidden.value = '';
        soDisplay.value = '';

        if (autoFilledTitle && titleInput) titleInput.value = '';
        if (autoFilledDesc && descInput) descInput.value = '';

        autoFilledTitle = false;
        autoFilledDesc = false;
      });

      soModalEl?.addEventListener('shown.bs.modal', function () {
        if (soSearchInput) {
          soSearchInput.value = '';
          applySoFilter();
          soSearchInput.focus();
        }
      });

      // restore selected display after validation error
      const oldId = soHidden?.value;
      if (oldId) {
        const row = document.querySelector(`.so-row[data-id="${CSS.escape(oldId)}"]`);
        if (row) {
          const soNo = row.dataset.so || '';
          const joNo = row.dataset.jo || '';
          soDisplay.value = normalizeSpaces([soNo, joNo].filter(Boolean).join(' • '));
        }
      }

  })();
  </script>
  @endpush

  @endsection