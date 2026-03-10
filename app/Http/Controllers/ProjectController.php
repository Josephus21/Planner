<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectProgressUpdate;
use App\Models\QualityChecklist;
use App\Models\Vehicle;
use App\Models\SalesOrder;
use App\Models\ProjectBomItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\JobOrder;

class ProjectController extends Controller
{
    /**
     * Helper: get current user's employee + role + department.
     */
    private function currentEmployeeContext(): array
    {
        $user = auth()->user();

        $employee = null;
        if ($user?->employee_id) {
            $employee = Employee::with(['role'])->find($user->employee_id);
        }

        $roleTitle = strtolower(trim(optional(optional($employee)->role)->title ?? ''));
        $deptId = $employee?->department_id;

        return [$user, $employee, $roleTitle, $deptId];
    }

    /**
     * Helper: department-based access (Developer sees all)
     */
    private function enforceProjectDepartmentAccess(Project $project): void
    {
        [, , $roleTitle, $deptId] = $this->currentEmployeeContext();

        if ($roleTitle === 'developer') {
            return; // allow all
        }

        if (!$deptId || (int) $project->department_id !== (int) $deptId) {
            abort(403, 'You are not allowed to view this project.');
        }
    }

    public function index()
    {
        [, , $roleTitle, $deptId] = $this->currentEmployeeContext();

        $query = Project::with([
                'planner',
                'driver',
                'installers',
                'category',
                'safetyOfficer',
                'subcons',
                'vehicles',
            ])
            ->latest();

        // Department filter for non-developer
        if ($roleTitle !== 'developer') {
            if (!$deptId) {
                $projects = collect();
                return view('projects.index', compact('projects'));
            }
            $query->where('department_id', $deptId);
        }

        $projects = $query->get();

        return view('projects.index', compact('projects'));
    }

  public function create()
{
    [, , $roleTitle] = $this->currentEmployeeContext();

    $employees = Employee::orderBy('fullname')->get();

    $installers = Employee::where('department_id', 6)
        ->orderBy('fullname')
        ->get();

    $categories = ProjectCategory::orderBy('name')->get();

    $subcons = $this->getSubconEmployees();

    $vehicles = Vehicle::where('is_active', 1)
        ->orderBy('type')
        ->orderBy('name')
        ->get();

    $roleKey = str_replace(' ', '', strtolower($roleTitle));

    if ($roleKey === 'ldplanner') {
        $salesOrders = JobOrder::query()
            ->select(
                'id',
                'so_no',
                'jo_no',
                'description',
                'customer_name',
                'delivery_date',
                'status',
                'job_type',
                'location'
            )
            ->whereNotNull('jo_no')
            ->orderByDesc('delivery_date')
            ->limit(500)
            ->get();
    } else {
        $salesOrders = SalesOrder::query()
            ->select(
                'id',
                'so_no',
                'jo_no',
                'description',
                'customer_name',
                'delivery_date',
                'status'
            )
            ->whereNotNull('so_no')
            ->orderByDesc('delivery_date')
            ->limit(500)
            ->get();
    }

    return view('projects.create', compact(
        'employees',
        'installers',
        'categories',
        'subcons',
        'vehicles',
        'salesOrders'
    ));
}
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],

            'project_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date', 'after_or_equal:date_from'],

            'category_id'  => ['required', 'exists:project_categories,id'],
            'vehicle_used' => ['nullable', 'string', 'max:255'],

            'needs_permit' => ['nullable', 'boolean'],
            'permit_file'  => ['nullable', 'file', 'max:4096', 'mimes:pdf,jpg,jpeg,png,webp'],


            'job_order_id' => ['nullable', 'exists:job_orders,id'],
            
            // ? BOM PDF
            'bom_pdf'      => ['nullable', 'file', 'mimes:pdf', 'max:10240'], // 10MB

            'needs_safety_officer' => ['nullable', 'boolean'],
            'safety_officer_id'    => ['nullable', 'exists:employees,id'],

            'driver_id'    => ['nullable', 'exists:employees,id'],

            'installers'   => ['nullable', 'array'],
            'installers.*' => ['exists:employees,id'],

            'subcons'      => ['nullable', 'array'],
            'subcons.*'    => ['exists:employees,id'],

            'vehicles'     => ['nullable', 'array'],
            'vehicles.*'   => ['exists:vehicles,id'],

            'status'       => ['required', 'in:pending,ongoing,on-hold,done,cancelled'],
            'progress'     => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        // permit file required if checked
        if ($request->boolean('needs_permit')) {
            $request->validate([
                'permit_file' => ['required', 'file', 'max:4096', 'mimes:pdf,jpg,jpeg,png,webp'],
            ]);
        }

        // safety officer required if checked
        if ($request->boolean('needs_safety_officer')) {
            $request->validate([
                'safety_officer_id' => ['required', 'exists:employees,id'],
            ]);
        } else {
            $data['safety_officer_id'] = null;
        }

        // auto planner + department
        [$user, $employee] = $this->currentEmployeeContext();

        if (!$user?->employee_id || !$employee) {
            return back()
                ->withErrors(['planner' => 'Your account is not linked to an employee record.'])
                ->withInput();
        }

        if (!$employee->department_id) {
            return back()
                ->withErrors(['department' => 'Your employee record has no department assigned.'])
                ->withInput();
        }

        $data['planner_id'] = $user->employee_id;
        $data['department_id'] = $employee->department_id;

        // normalize booleans
        $data['needs_permit'] = $request->boolean('needs_permit');
        $data['needs_safety_officer'] = $request->boolean('needs_safety_officer');

        // project image
        if ($request->hasFile('project_image')) {
            $data['project_image'] = $request->file('project_image')->store('projects', 'public');
        }

        // permit file
        if ($request->hasFile('permit_file')) {
            $data['permit_path'] = $request->file('permit_file')->store('permits', 'public');
        } else {
            $data['permit_path'] = null;
        }

        $data['progress'] = $data['progress'] ?? 0;

        $project = Project::create($data);
$data['job_order_id'] = $request->job_order_id ?? null;
        // sync relations
        $project->installers()->sync($data['installers'] ?? []);

        if (method_exists($project, 'subcons')) {
            $project->subcons()->sync($data['subcons'] ?? []);
        }

        if (method_exists($project, 'vehicles')) {
            $project->vehicles()->sync($data['vehicles'] ?? []);
        }

        // ? BOM upload + parse into items
        if ($request->hasFile('bom_pdf')) {
            $path = $request->file('bom_pdf')->store('boms', 'public');
            $project->bom_path = $path;
            $project->save();

            $this->parseBomPdfToItems($project);
        }

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $this->enforceProjectDepartmentAccess($project);

        $project->load([
            'planner',
            'driver',
            'installers',
            'updates.user',
            'category',
            'safetyOfficer',
            'subcons',
            'vehicles',
            'category.checklists',
            'bomItems', // ? BOM rows
        ]);

        return view('projects.show', compact('project'));
    }

    /**
     * Mark checklist item as done (supports optional done_at/done_by columns)
     */
    public function checklistDone($id)
    {
        $item = QualityChecklist::findOrFail($id);

        $payload = ['is_done' => 1];

        if (Schema::hasColumn('quality_checklists', 'done_at')) {
            $payload['done_at'] = now();
        }
        if (Schema::hasColumn('quality_checklists', 'done_by')) {
            $payload['done_by'] = auth()->id();
        }

        $item->update($payload);

        return back()->with('success', 'Checklist completed.');
    }

    public function edit(Project $project)
    {
        $this->enforceProjectDepartmentAccess($project);

        $employees = Employee::orderBy('fullname')->get();

        $installers = Employee::where('department_id', 6)
            ->orderBy('fullname')
            ->get();

        $categories = ProjectCategory::orderBy('name')->get();

        $subcons = $this->getSubconEmployees();

        $vehicles = Vehicle::where('is_active', 1)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $project->load(['installers', 'subcons', 'planner', 'category', 'safetyOfficer', 'vehicles']);

        return view('projects.edit', compact(
            'project',
            'employees',
            'installers',
            'categories',
            'subcons',
            'vehicles'
        ));
    }

    public function update(Request $request, Project $project)
    {
        $this->enforceProjectDepartmentAccess($project);

        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],

            'project_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date', 'after_or_equal:date_from'],

            'category_id'  => ['required', 'exists:project_categories,id'],
            'vehicle_used' => ['nullable', 'string', 'max:255'],

            'needs_permit'  => ['nullable', 'boolean'],
            'permit_file'   => ['nullable', 'file', 'max:4096', 'mimes:pdf,jpg,jpeg,png,webp'],
            'remove_permit' => ['nullable', 'boolean'],

            // ? allow updating BOM too (optional)
            'bom_pdf'       => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_bom'    => ['nullable', 'boolean'],

            'needs_safety_officer' => ['nullable', 'boolean'],
            'safety_officer_id'    => ['nullable', 'exists:employees,id'],

            'driver_id'    => ['nullable', 'exists:employees,id'],

            'installers'   => ['nullable', 'array'],
            'installers.*' => ['exists:employees,id'],

            'subcons'      => ['nullable', 'array'],
            'subcons.*'    => ['exists:employees,id'],

            'vehicles'     => ['nullable', 'array'],
            'vehicles.*'   => ['exists:vehicles,id'],

            'status'       => ['required', 'in:pending,ongoing,on-hold,done,cancelled'],
            'progress'     => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        // normalize booleans
        $data['needs_permit'] = $request->boolean('needs_permit');
        $data['needs_safety_officer'] = $request->boolean('needs_safety_officer');
        $removePermit = $request->boolean('remove_permit');
        $removeBom    = $request->boolean('remove_bom');

        // safety officer rules
        if ($data['needs_safety_officer']) {
            $request->validate([
                'safety_officer_id' => ['required', 'exists:employees,id'],
            ]);
        } else {
            $data['safety_officer_id'] = null;
        }

        // PERMIT RULES
        if ($removePermit && $project->permit_path) {
            Storage::disk('public')->delete($project->permit_path);
            $project->permit_path = null;
            $project->save();
        }

        if (!$data['needs_permit']) {
            if ($project->permit_path) {
                Storage::disk('public')->delete($project->permit_path);
            }
            $data['permit_path'] = null;
        } else {
            $hasExistingPermit = !empty($project->permit_path);

            if (!$hasExistingPermit && !$request->hasFile('permit_file')) {
                return back()
                    ->withErrors(['permit_file' => 'Permit file is required when Needs Permit is checked.'])
                    ->withInput();
            }
        }

        // Replace project image
        if ($request->hasFile('project_image')) {
            if ($project->project_image) {
                Storage::disk('public')->delete($project->project_image);
            }
            $data['project_image'] = $request->file('project_image')->store('projects', 'public');
        }

        // Replace permit file
        if ($request->hasFile('permit_file')) {
            if ($project->permit_path) {
                Storage::disk('public')->delete($project->permit_path);
            }
            $data['permit_path'] = $request->file('permit_file')->store('permits', 'public');
        }

        // ? BOM remove
        if ($removeBom && $project->bom_path) {
            Storage::disk('public')->delete($project->bom_path);
            $project->bom_path = null;
            $project->save();

            // also delete items
            if (method_exists($project, 'bomItems')) {
                $project->bomItems()->delete();
            }
        }

        // ? BOM replace
        if ($request->hasFile('bom_pdf')) {
            if ($project->bom_path) {
                Storage::disk('public')->delete($project->bom_path);
            }
            $path = $request->file('bom_pdf')->store('boms', 'public');
            $data['bom_path'] = $path;
        }

        // Keep department_id unchanged
        unset($data['department_id']);

        $project->update($data);

        $project->installers()->sync($data['installers'] ?? []);

        if (method_exists($project, 'subcons')) {
            $project->subcons()->sync($data['subcons'] ?? []);
        }

        if (method_exists($project, 'vehicles')) {
            $project->vehicles()->sync($data['vehicles'] ?? []);
        }

        // ? If BOM replaced, (re)parse items
        if ($request->hasFile('bom_pdf')) {
            if (method_exists($project, 'bomItems')) {
                $project->bomItems()->delete();
            }
            $this->parseBomPdfToItems($project);
        }

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $this->enforceProjectDepartmentAccess($project);

        if ($project->project_image) {
            Storage::disk('public')->delete($project->project_image);
        }

        if ($project->permit_path) {
            Storage::disk('public')->delete($project->permit_path);
        }

        if ($project->bom_path) {
            Storage::disk('public')->delete($project->bom_path);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    public function addProgress(Request $request, Project $project)
    {
        $this->enforceProjectDepartmentAccess($project);

        $data = $request->validate([
            'percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'note'       => ['nullable', 'string'],
            'image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $payload = [
            'project_id' => $project->id,
            'updated_by' => auth()->id(),
            'percent'    => (int) $data['percentage'],
            'note'       => $data['note'] ?? null,
        ];

        if ($request->hasFile('image')) {
            $payload['photo'] = $request->file('image')->store('project_updates', 'public');
        }

        ProjectProgressUpdate::create($payload);

        $current = (int) ($project->progress ?? 0);
        $add = (int) $payload['percent'];

        $project->progress = min(100, $current + $add);

        if ($project->progress >= 100) {
            $project->progress = 100;
            $project->status = 'done';
        } elseif ($project->status === 'pending') {
            $project->status = 'ongoing';
        } elseif ($project->status === 'on-hold') {
            // keep on-hold
        }

        $project->save();

        return back()->with('success', 'Progress updated.');
    }

    private function getSubconEmployees()
    {
        $subconRoleId = DB::table('roles')
            ->whereNull('deleted_at')
            ->where('title', 'Subcon')
            ->value('id');

        if (!$subconRoleId) {
            return collect();
        }

        return Employee::whereNull('deleted_at')
            ->where('role_id', $subconRoleId)
            ->orderBy('fullname')
            ->get();
    }

    /**
     * Simple BOM PDF -> items parser.
     * If you want perfect column extraction, we can improve later.
     *
     * Requires: composer require smalot/pdfparser
     */
  private function parseBomPdfToItems(Project $project): void
{
    if (!$project->bom_path || !Storage::disk('public')->exists($project->bom_path)) {
        return;
    }

    $fullPath = Storage::disk('public')->path($project->bom_path);

    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($fullPath);
        $text = $pdf->getText();
    } catch (\Throwable $e) {
        $project->bomItems()->create([
            'section' => 'BOM',
            'item'    => 'BOM uploaded (unable to auto-parse).',
            'status'  => 'not_ready',
        ]);
        return;
    }

    $lines = collect(preg_split("/\r\n|\n|\r/", $text))
        ->map(fn ($l) => trim(preg_replace('/\s+/', ' ', $l)))
        ->filter()
        ->values();

    $isQty = fn ($s) => (bool) preg_match('/\b\d+(\.\d+)?\s*(sqft|sq ft|pcs|pc|m)\b/i', $s);

    $sectionHeaders = [
        'CUTTING LIST / BILL OF MATERIALS',
        'SCREWS',
        'ELECTRICAL ASSEMBLY',
    ];

    $section = 'CUTTING LIST / BILL OF MATERIALS';

    for ($i = 0; $i < $lines->count(); $i++) {
        $line  = $lines[$i];
        $upper = strtoupper($line);

        // skip obvious report headers
        if (
            str_contains($upper, 'PROJECT NAME') ||
            str_contains($upper, 'ORDER NO') ||
            str_contains($upper, 'PREPARED BY') ||
            (str_contains($upper, 'PARTS') && str_contains($upper, 'MATERIALS'))
        ) {
            continue;
        }

        // pure section header line
        if (in_array($upper, $sectionHeaders, true)) {
            $section = $upper;
            continue;
        }

        // lines that contain section + item together (IMPORTANT for POWER SUPPLY / WIRINGS / LIGHTINGS)
        if (preg_match('/^(WIRINGS|POWER SUPPLY|LIGHTINGS)\b/i', $line, $m) && $isQty($line)) {
            $section = strtoupper($m[1]);
            $itemText = trim(preg_replace('/^(WIRINGS|POWER SUPPLY|LIGHTINGS)\b/i', '', $line));
            $qty = null;
            if (preg_match('/\b\d+(\.\d+)?\s*(sqft|sq ft|pcs|pc|m)\b/i', $line, $mm)) {
                $qty = trim($mm[0]);
            }

            $project->bomItems()->create([
                'section' => $section,
                'item'    => $itemText !== '' ? $itemText : $line,
                'qty'     => $qty,
                'status'  => 'not_ready',
            ]);
            continue;
        }

        // merge LED STRIPS multi-line block into ONE item (this is one of your missing items)
        if (str_starts_with($upper, 'LED STRIPS')) {
            $combined = $line;

            // merge next lines until we hit a new obvious header/section
            while ($i + 1 < $lines->count()) {
                $peek = $lines[$i + 1];
                $peekUpper = strtoupper($peek);

                if (in_array($peekUpper, $sectionHeaders, true)) break;
                if (str_contains($peekUpper, 'PROJECT NAME') || str_contains($peekUpper, 'ORDER NO') || str_contains($peekUpper, 'PREPARED BY')) break;

                $combined .= ' ' . $peek;
                $i++;

                // stop once we already saw a qty AND we also saw "WHITE LIGHT" line (good enough)
                if ($isQty($combined) && str_contains(strtoupper($combined), 'WHITE LIGHT')) {
                    break;
                }
            }

            $qty = null;
            if (preg_match('/\b\d+(\.\d+)?\s*(sqft|sq ft|pcs|pc|m)\b/i', $combined, $mm)) {
                $qty = trim($mm[0]);
            }

            $project->bomItems()->create([
                'section' => 'ELECTRICAL ASSEMBLY',
                'item'    => $combined,
                'qty'     => $qty,
                'status'  => 'not_ready',
            ]);
            continue;
        }

        // normal item rows: anything with qty token
        if ($isQty($line)) {
            $qty = null;
            if (preg_match('/\b\d+(\.\d+)?\s*(sqft|sq ft|pcs|pc|m)\b/i', $line, $mm)) {
                $qty = trim($mm[0]);
            }

            $project->bomItems()->create([
                'section' => $section,
                'item'    => $line,
                'qty'     => $qty,
                'status'  => 'not_ready',
            ]);
        }
    }

    // fallback
    if ($project->bomItems()->count() === 0) {
        $project->bomItems()->create([
            'section' => 'BOM',
            'item'    => 'BOM uploaded (no rows detected).',
            'status'  => 'not_ready',
        ]);
    }
}
}