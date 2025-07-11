@extends('layouts.app')

@section('title', $project->name)

@section('header', 'Project Details')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="d-flex align-items-center">
            <h1 class="h3 mb-0">{{ $project->name }}</h1>
            <span class="badge bg-{{ $project->status == 'active' ? 'success' : ($project->status == 'completed' ? 'primary' : ($project->status == 'on-hold' ? 'warning' : 'secondary')) }} ms-2">
                {{ ucfirst($project->status) }}
            </span>
        </div>
        <p class="text-muted">{{ $project->description }}</p>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group">
            @can('update', $project)
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
            @endcan
            <a href="{{ route('projects.tasks.create', $project) }}" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Add Task
            </a>
            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                More
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('projects.timeline', $project) }}">
                    <i class="fas fa-calendar-alt me-1"></i>Timeline
                </a></li>
                <li><a class="dropdown-item" href="{{ route('projects.report', $project) }}">
                    <i class="fas fa-chart-bar me-1"></i>Generate Report
                </a></li>
                @can('delete', $project)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('projects.destroy', $project) }}" method="POST" id="delete-project-form">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="dropdown-item text-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash-alt me-1"></i>Delete Project
                        </button>
                    </form>
                </li>
                @endcan
            </ul>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Project Details</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Client:</span>
                        <a href="{{ route('clients.show', $project->client) }}">{{ $project->client->name }}</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Manager:</span>
                        <a href="{{ route('users.show', $project->manager) }}">{{ $project->manager->name }}</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Start Date:</span>
                        <span>{{ $project->start_date->format('M d, Y') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Expected End Date:</span>
                        <span class="{{ $isOverdue ? 'text-danger' : '' }}">{{ $project->expected_end_date->format('M d, Y') }}</span>
                    </li>
                    @if($project->actual_end_date)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Actual End Date:</span>
                        <span>{{ $project->actual_end_date->format('M d, Y') }}</span>
                    </li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Budget:</span>
                        <span>${{ number_format($project->budget, 2) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Total Tasks</h6>
                                <h2 class="mb-0">{{ $totalTasks }}</h2>
                            </div>
                            <i class="fas fa-tasks fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Completed Tasks</h6>
                                <h2 class="mb-0">{{ $completedTasks }}</h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Days Remaining</h6>
                                <h2 class="mb-0">{{ $daysRemaining }}</h2>
                            </div>
                            <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Project Progress</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Task Completion</span>
                        <span>{{ number_format($taskCompletionRate, 1) }}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $taskCompletionRate }}%;" aria-valuenow="{{ $taskCompletionRate }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Time Progress</span>
                        @php
                            $totalDays = $project->start_date->diffInDays($project->expected_end_date);
                            $elapsedDays = $project->start_date->diffInDays(now());
                            $timeProgress = $totalDays > 0 ? min(100, ($elapsedDays / $totalDays) * 100) : 0;
                        @endphp
                        <span>{{ number_format($timeProgress, 1) }}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $timeProgress }}%;" aria-valuenow="{{ $timeProgress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tasks</h5>
                <a href="{{ route('projects.tasks.create', $project) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Add Task
                </a>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="taskTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">All</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">Pending</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="in-progress-tab" data-bs-toggle="tab" data-bs-target="#in-progress" type="button" role="tab" aria-controls="in-progress" aria-selected="false">In Progress</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab" aria-controls="completed" aria-selected="false">Completed</button>
                    </li>
                </ul>
                <div class="tab-content" id="taskTabsContent">
                    <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Assigned To</th>
                                        <th>Due Date</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($project->tasks as $task)
                                    <tr>
                                        <td>
                                            <a href="{{ route('projects.tasks.show', ['project' => $project, 'task' => $task]) }}">{{ $task->name }}</a>
                                        </td>
                                        <td>
                                            @if($task->assignedTo)
                                                {{ $task->assignedTo->name }}
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($task->status != 'completed' && now()->gt($task->due_date))
                                                <span class="text-danger">{{ $task->due_date->format('M d, Y') }}</span>
                                            @else
                                                {{ $task->due_date->format('M d, Y') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($task->priority == 'high')
                                                <span class="badge bg-danger">High</span>
                                            @elseif($task->priority == 'medium')
                                                <span class="badge bg-warning">Medium</span>
                                            @else
                                                <span class="badge bg-info">Low</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($task->status == 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @elseif($task->status == 'in-progress')
                                                <span class="badge bg-primary">In Progress</span>
                                            @elseif($task->status == 'review')
                                                <span class="badge bg-info">Review</span>
                                            @elseif($task->status == 'cancelled')
                                                <span class="badge bg-secondary">Cancelled</span>
                                            @else
                                                <span class="badge bg-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('projects.tasks.show', ['project' => $project, 'task' => $task]) }}" class="btn btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('update', $task)
                                                <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No tasks found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Assigned To</th>
                                        <th>Due Date</th>
                                        <th>Priority</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $pendingTasks = $project->tasks->where('status', 'pending'); @endphp
                                    @forelse($pendingTasks as $task)
                                    <tr>
                                        <td>
                                            <a href="{{ route('projects.tasks.show', ['project' => $project, 'task' => $task]) }}">{{ $task->name }}</a>
                                        </td>
                                        <td>
                                            @if($task->assignedTo)
                                                {{ $task->assignedTo->name }}
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(now()->gt($task->due_date))
                                                <span class="text-danger">{{ $task->due_date->format('M d, Y') }}</span>
                                            @else
                                                {{ $task->due_date->format('M d, Y') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($task->priority == 'high')
                                                <span class="badge bg-danger">High</span>
                                            @elseif($task->priority == 'medium')
                                                <span class="badge bg-warning">Medium</span>
                                            @else
                                                <span class="badge bg-info">Low</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('projects.tasks.show', ['project' => $project, 'task' => $task]) }}" class="btn btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('update', $task)
                                                <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No pending tasks</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="in-progress" role="tabpanel" aria-labelledby="in-progress-tab">
                        <!-- Similar structure for in-progress tasks -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Assigned To</th>
                                        <th>Due Date</th>
                                        <th>Priority</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $inProgressTasks = $project->tasks->where('status', 'in-progress'); @endphp
                                    @forelse($inProgressTasks as $task)
                                    <!-- Task rows similar to pending tab -->
                                    <tr>
                                        <td>
                                            <a href="{{ route('projects.tasks.show', ['project' => $project, 'task' => $task]) }}">{{ $task->name }}</a>
                                        </td>
                                        <td>
                                            @if($task->assignedTo)
                                                {{ $task->assignedTo->name }}
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(now()->gt($task->due_date))
                                                <span class="text-danger">{{ $task->due_date->format('M d, Y') }}</span>
                                            @else
                                                {{ $task->due_date->format('M d, Y') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($task->priority == 'high')
                                                <span class="badge bg-danger">High</span>
                                            @elseif($task->priority == 'medium')
                                                <span class="badge bg-warning">Medium</span>
                                            @else
                                                <span class="badge bg-info">Low</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('projects.tasks.show', ['project' => $project, 'task' => $task]) }}" class="btn btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('update', $task)
                                                <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No in-progress tasks</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                        <!-- Similar structure for completed tasks -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Assigned To</th>
                                        <th>Completed Date</th>
                                        <th>Priority</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $completedTasks = $project->tasks->where('status', 'completed'); @endphp
                                    @forelse($completedTasks as $task)
                                    <tr>
                                        <td>
                                            <a href="{{ route('projects.tasks.show', ['project' => $project, 'task' => $task]) }}">{{ $task->name }}</a>
                                        </td>
                                        <td>
                                            @if($task->assignedTo)
                                                {{ $task->assignedTo->name }}
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $task->completed_at ? $task->completed_at->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td>
                                            @if($task->priority == 'high')
                                                <span class="badge bg-danger">High</span>
                                            @elseif($task->priority == 'medium')
                                                <span class="badge bg-warning">Medium</span>
                                            @else
                                                <span class="badge bg-info">Low</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('projects.tasks.show', ['project' => $project, 'task' => $task]) }}" class="btn btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No completed tasks</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Team Members</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="fas fa-plus me-1"></i>Add Member
                </button>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($project->members as $member)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                                @if($member->user->profile_photo_path)
                                    <img src="{{ Storage::url($member->user->profile_photo_path) }}" alt="{{ $member->user->name }}" class="rounded-circle" width="40">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        {{ strtoupper(substr($member->user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $member->user->name }}</h6>
                                <small class="text-muted">{{ ucfirst($member->role) }}</small>
                            </div>
                        </div>
                        @if($member->role != 'manager' && auth()->user()->can('update', $project))
                        <form action="{{ route('projects.members.remove', ['project' => $project, 'member' => $member]) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to remove this member?')">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                        @endif
                    </li>
                    @empty
                    <li class="list-group-item text-center">No team members added yet</li>
                    @endforelse
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Project Activity</h5>
            </div>
            <div class="card-body p-0">
                <div class="timeline">
                    @forelse($timelineEvents as $event)
                    <div class="timeline-item">
                        <div class="timeline-marker {{ $event['type'] == 'project' ? 'bg-primary' : 'bg-info' }}"></div>
                        <div class="timeline-content">
                            <h6 class="mb-0">{{ $event['event'] }}</h6>
                            <small class="text-muted">{{ \Carbon\Carbon::parse($event['date'])->format('M d, Y') }}</small>
                            <p class="mb-0">{{ $event['description'] }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="p-3 text-center">No activity recorded yet</div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('projects.timeline', $project) }}" class="btn btn-sm btn-outline-primary">View Full Timeline</a>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('projects.members.add', $project) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addMemberModalLabel">Add Team Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Select User</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Select a user...</option>
                            @foreach($availableUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="developer">Developer</option>
                            <option value="designer">Designer</option>
                            <option value="tester">Tester</option>
                            <option value="analyst">Analyst</option>
                            <option value="consultant">Consultant</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function confirmDelete() {
        if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
            document.getElementById('delete-project-form').submit();
        }
    }
    
    // Add custom CSS for timeline
    document.head.insertAdjacentHTML('beforeend', `
        <style>
            .timeline {
                position: relative;
                padding: 1rem;
                margin: 0;
            }
            
            .timeline:before {
                content: '';
                position: absolute;
                height: 100%;
                left: 1.5rem;
                top: 0;
                width: 2px;
                background: #e9ecef;
            }
            
            .timeline-item {
                position: relative;
                padding-left: 2.5rem;
                padding-bottom: 1.5rem;
            }
            
            .timeline-marker {
                position: absolute;
                left: 0.75rem;
                height: 12px;
                width: 12px;
                border-radius: 50%;
                transform: translateX(-50%);
                z-index: 1;
            }
            
            .timeline-content {
                padding-bottom: 0.5rem;
                border-bottom: 1px solid #e9ecef;
            }
            
            .timeline-item:last-child .timeline-content {
                border-bottom: none;
            }
        </style>
    `);
</script>
@endsection
