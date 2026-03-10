<?php

namespace App\Http\Controllers;

use App\Models\ProjectCategory;
use Illuminate\Http\Request;

class ProjectCategoryController extends Controller
{
    public function index()
    {
        $categories = ProjectCategory::withCount('checklists')->orderBy('name')->get();
        return view('project_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('project_categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255','unique:project_categories,name'],
            'description' => ['nullable','string'],
        ]);

        ProjectCategory::create($data);

        return redirect()->route('project-categories.index')->with('success','Category created.');
    }

    public function edit(ProjectCategory $project_category)
    {
        return view('project_categories.edit', ['category' => $project_category]);
    }

    public function update(Request $request, ProjectCategory $project_category)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255','unique:project_categories,name,'.$project_category->id],
            'description' => ['nullable','string'],
        ]);

        $project_category->update($data);

        return redirect()->route('project-categories.index')->with('success','Category updated.');
    }

    public function destroy(ProjectCategory $project_category)
    {
        $project_category->delete();
        return redirect()->route('project-categories.index')->with('success','Category deleted.');
    }

    // ? manage checklist page
    public function checklists(ProjectCategory $project_category)
    {
        $project_category->load('checklists');
        return view('project_categories.checklists', ['category' => $project_category]);
    }
}