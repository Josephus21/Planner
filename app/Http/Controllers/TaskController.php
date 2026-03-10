<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // If user can view all tasks (ex: HR role has tasks.view + tasks.view_all),
        // show all. Otherwise, show only tasks assigned to their employee_id.
        // If you don't have "tasks.view_all", remove that and just use tasks.view.
        if ($user->can('tasks.view_all')) {
            $tasks = Task::with('employee')->latest()->get();
        } else {
            $employeeId = $user->employee_id;

            $tasks = Task::with('employee')
                ->when($employeeId, fn ($q) => $q->where('assigned_to', $employeeId))
                ->latest()
                ->get();
        }

        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        // route already protected by permission:tasks.create
        $employees = Employee::orderBy('fullname')->get();

        return view('tasks.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['required', 'exists:employees,id'],
            'due_date'    => ['required', 'date'],
            // ✅ Do NOT require status on create (avoid validation failure)
            'status'      => ['nullable', 'in:pending,done'],
        ]);

        // ✅ default status
        $data['status'] = $data['status'] ?? 'pending';

        // ✅ safer than $request->all() (prevents mass-assign + extra fields)
        Task::create($data);

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        // ✅ optional: ensure relationship ready for blade $task->employee
        $task->load('employee');

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        $employees = Employee::orderBy('fullname')->get();

        return view('tasks.edit', compact('task', 'employees'));
    }

    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['required', 'exists:employees,id'],
            'due_date'    => ['required', 'date'],
            'status'      => ['required', 'in:pending,done'],
        ]);

        $task->update($data);

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function done(Task $task)
    {
        $task->update(['status' => 'done']);

        return redirect()->route('tasks.index')->with('success', 'Task marked as done successfully.');
    }

    public function pending(Task $task)
    {
        $task->update(['status' => 'pending']);

        return redirect()->route('tasks.index')->with('success', 'Task marked as pending successfully.');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }
}