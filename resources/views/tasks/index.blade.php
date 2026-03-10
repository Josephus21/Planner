@extends('layouts.dashboard')

@section('content')
<div id="main">
    <header class="mb-3">
        <a href="#" class="burger-btn d-block d-xl-none">
            <i class="bi bi-justify fs-3"></i>
        </a>
    </header>

    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Tasks</h3>
                    <p class="text-subtitle text-muted">Simple datatable</p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                            <li class="breadcrumb-item" aria-current="page">Task</li>
                            <li class="breadcrumb-item active" aria-current="page">Index</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <section class="section">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title mb-0">Data</h5>

                    <div class="ms-auto">
                        @can('tasks.create')
                            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                                New Task
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <table class="table table-striped" id="table1">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Assigned to</th>
                                <th>Due date</th>
                                <th>Status</th>
                                <th style="min-width: 260px;">Option</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($tasks as $task)
                                <tr>
                                    <td>{{ $task->title }}</td>

                                    <td>{{ $task->employee->fullname ?? '—' }}</td>

                                    <td>
                                        {{-- if due_date is cast to date, this formats nicely --}}
                                        {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') : '—' }}
                                    </td>

                                    <td>
                                        @php($status = $task->status ?? 'pending')

                                        @if($status === 'pending')
                                            <span class="text-warning">pending</span>
                                        @elseif($status === 'done')
                                            <span class="text-success">done</span>
                                        @else
                                            <span class="text-info">{{ $status }}</span>
                                        @endif
                                    </td>

                                    <td class="d-flex flex-wrap gap-1">
                                        @can('tasks.view')
                                            <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-info btn-sm">
                                                View
                                            </a>
                                        @endcan

                                        @can('tasks.edit')
                                            @if(($task->status ?? 'pending') === 'pending')
                                                <a href="{{ route('tasks.done', $task->id) }}" class="btn btn-success btn-sm">
                                                    Mark as Done
                                                </a>
                                            @else
                                                <a href="{{ route('tasks.pending', $task->id) }}" class="btn btn-warning btn-sm">
                                                    Mark as Pending
                                                </a>
                                            @endif
                                        @endcan

                                        @can('tasks.edit')
                                            <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-warning btn-sm">
                                                Edit
                                            </a>
                                        @endcan

                                        @can('tasks.delete')
                                            <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete this task?')">
                                                    Delete
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No tasks found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection