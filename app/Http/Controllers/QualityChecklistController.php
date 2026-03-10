<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\ProjectCategory;
use App\Models\QualityChecklist;
use Illuminate\Http\Request;


class QualityChecklistController extends Controller
{
    public function store(Request $request, ProjectCategory $project_category)
    {
        $data = $request->validate([
            'item' => ['required','string','max:255'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_required' => ['nullable','boolean'],
            'is_active' => ['nullable','boolean'],
        ]);

        $data['project_category_id'] = $project_category->id;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_required'] = $request->boolean('is_required');
        $data['is_active'] = $request->boolean('is_active', true);

        QualityChecklist::create($data);

        return back()->with('success', 'Checklist item added.');
    }

    public function update(Request $request, QualityChecklist $quality_checklist)
    {
        $data = $request->validate([
            'item' => ['required','string','max:255'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_required' => ['nullable','boolean'],
            'is_active' => ['nullable','boolean'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_required'] = $request->boolean('is_required');
        $data['is_active'] = $request->boolean('is_active');

        $quality_checklist->update($data);

        return back()->with('success','Checklist item updated.');
    }
public function done(QualityChecklist $checklist)
    {
        if (!$checklist->is_done) {
            $checklist->update([
                'is_done'  => 1,
                'done_at'  => Carbon::now(),
            ]);
        }

        return back()->with('success', 'Checklist marked DONE.');
    }
    public function destroy(QualityChecklist $quality_checklist)
    {
        $quality_checklist->delete();
        return back()->with('success','Checklist item deleted.');
    }
}