@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3>Projects</h3>
            <p class="text-muted">Project management overview</p>
        </div>

        @can('projects.create')
        <a href="{{ route('projects.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Project
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    <div class="card mt-3">
        <div class="card-body">
            <table class="table table-striped" id="table1">
                <thead>
                    <tr>
                        <th>Picture</th>
                        <th>Project Name</th>
                        <th>Timeline</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
@forelse($projects as $project)
    <tr>
        {{-- Picture --}}
        <td width="100">
            @if($project->project_image)
                <img src="{{ asset('storage/'.$project->project_image) }}"
                     width="80" class="rounded">
            @else
                <span class="text-muted">No image</span>
            @endif
        </td>

        {{-- Project Title --}}
        <td>
            <strong>{{ $project->title }}</strong>
        </td>

        {{-- Timeline --}}
        <td>
            @if($project->date_from && $project->date_to)
                {{ \Carbon\Carbon::parse($project->date_from)->format('M d, Y') }}
                <br>
                to
                {{ \Carbon\Carbon::parse($project->date_to)->format('M d, Y') }}
            @else
                <span class="text-muted">No schedule</span>
            @endif
        </td>

        {{-- Status --}}
        <td>
            @php
                $statusColors = [
                    'pending' => 'secondary',
                    'ongoing' => 'primary',
                    'done' => 'success',
                    'cancelled' => 'danger'
                ];
            @endphp

            <span class="badge bg-{{ $statusColors[$project->status] ?? 'info' }}">
                {{ ucfirst($project->status) }}
            </span>
        </td>

        {{-- Progress --}}
        <td width="200">
            <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-success"
                     role="progressbar"
                     style="width: {{ (int)$project->progress }}%;">
                    {{ (int)$project->progress }}%
                </div>
            </div>
        </td>

        {{-- Actions --}}
<td>
    <a href="{{ route('projects.show', $project->id) }}"
       class="btn btn-sm btn-info">
        View
    </a>

    @can('projects.edit')
        <a href="{{ route('projects.edit', $project->id) }}"
           class="btn btn-sm btn-warning">
            Edit
        </a>
    @endcan

    @can('projects.delete')
        <form action="{{ route('projects.destroy', $project->id) }}"
              method="POST"
              class="d-inline"
              onsubmit="return confirm('Delete this project?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
    @endcan
</td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center text-muted">
            No projects found.
        </td>
    </tr>
@endforelse
</tbody>
            </table>
        </div>
    </div>
</div>
@endsection