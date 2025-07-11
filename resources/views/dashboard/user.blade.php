@extends('layouts.app')

@section('title', 'My Dashboard')

@section('header', 'My Dashboard')

@section('content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">My Tasks</h6>
                        <h2 class="mb-0">{{ $myTasks->total() }}</h2>
                    </div>
                    <i class="fas fa-tasks fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="#my-tasks" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">My Projects</h6>
                        <h2 class="mb-0">{{ $myProjects->count() }}</h2>
                    </div>
                    <i class="fas fa-project-diagram fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="#my-projects" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Task Completion</h6>
                        <h2 class="mb-0">{{ number_format($taskCompletionRate, 1) }}%</h2>
                    </div>
                    <i class="fas fa-chart-pie fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <span class="text-white text-decoration-none">Last 30 days</span>
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-danger text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Overdue Tasks</h6>
                        <h2 class="mb-0">{{ $overdueTasks }}</h2>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="#overdue-tasks" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header" id="my-tasks">
                <h5 class="mb-0">My Tasks</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="taskTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">Pending</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="in-progress-tab" data-bs-toggle="tab" data-bs-target="#in-progress" type="button" role="tab" aria-controls="in-progress" aria-selected="false">In Progress</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab" aria-controls="completed" aria-selected="false">Completed</button>
                    </li>
                </ul>
                <div class="tab-content" id="taskTabsContent">
                    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Project</th>
                                        <th>Priority</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $pendingTasks = $myTasks->where('status', 'pending'); @endphp
                                    @forelse($pendingTasks as $task)
                                    <tr>
                                        <td>
                                            <a href="{{ route('tasks.show', $task) }}">{{ $task->name }}</a>
                                        </td>
                                        <td>{{ $task->project->name }}</td>
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
                                            @if(now()->gt($task->due_date))
                                                <span class="text-danger">{{ $task->due_date->format('M d, Y') }}</span>
                                            @else
                                                {{ $task->due_date->format('M d, Y') }}
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('tasks.show', $task) }}" class="btn btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-success start-task" data-task-id="{{ $task->id }}">
                                                    <i class="fas fa-play"></i>
                                                </button>
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
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Project</th>
                                        <th>Priority</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $inProgressTasks = $myTasks->where('status', 'in-progress'); @endphp
                                    @forelse($inProgressTasks as $task)
                                    <tr>
                                        <td>
                                            <a href="{{ route('tasks.show', $task) }}">{{ $task->name }}</a>
                                        </td>
                                        <td>{{ $task->project->name }}</td>
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
                                            @if(now()->gt($task->due_date))
                                                <span class="text-danger">{{ $task->due_date->format('M d, Y') }}</span>
                                            @else
                                                {{ $task->due_date->format('M d, Y') }}
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('tasks.show', $task) }}" class="btn btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-success complete-task" data-task-id="{{ $task->id }}">
                                                    <i class="fas fa-check"></i>
                                                </button>
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
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Project</th>
                                        <th>Priority</th>
                                        <th>Completed Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $completedTasks = $myTasks->where('status', 'completed'); @endphp
                                    @forelse($completedTasks as $task)
                                    <tr>
                                        <td>
                                            <a href="{{ route('tasks.show', $task) }}">{{ $task->name }}</a>
                                        </td>
                                        <td>{{ $task->project->name }}</td>
                                        <td>
                                            @if($task->priority == 'high')
                                                <span class="badge bg-danger">High</span>
                                            @elseif($task->priority == 'medium')
                                                <span class="badge bg-warning">Medium</span>
                                            @else
                                                <span class="badge bg-info">Low</span>
                                            @endif
                                        </td>
                                        <td>{{ $task->completed_at->format('M d, Y') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No completed tasks</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    {{ $myTasks->links() }}
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($recentActivities as $activity)
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">{{ $activity->description }}</h6>
                            <small>{{ $activity->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-1">
                            @if($activity->subject_type == 'App\Models\Task')
                                <a href="{{ route('tasks.show', $activity->subject_id) }}">
                                    {{ $activity->properties['name'] ?? 'Task' }}
                                </a>
                            @elseif($activity->subject_type == 'App\Models\Project')
                                <a href="{{ route('projects.show', $activity->subject_id) }}">
                                    {{ $activity->properties['name'] ?? 'Project' }}
                                </a>
                            @else
                                {{ $activity->subject_type }}
                            @endif
                        </p>
                    </div>
                    @empty
                    <div class="list-group-item">
                        <p class="mb-0 text-center">No recent activity</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
        
        <div class="card" id="overdue-tasks">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Overdue Tasks</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @php 
                        $overdueTasks = $myTasks->filter(function($task) {
                            return $task->status != 'completed' && now()->gt($task->due_date);
                        });
                    @endphp
                    
                    @forelse($overdueTasks as $task)
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">
                                <a href="{{ route('tasks.show', $task) }}">{{ $task->name }}</a>
                            </h6>
                            <small class="text-danger">{{ $task->due_date->diffForHumans() }}</small>
                        </div>
                        <p class="mb-1">
                            <small>Project: {{ $task->project->name }}</small>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="badge bg-{{ $task->priority == 'high' ? 'danger' : ($task->priority == 'medium' ? 'warning' : 'info') }}">
                                {{ ucfirst($task->priority) }}
                            </span>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-secondary">View</a>
                                <button type="button" class="btn btn-outline-primary start-task" data-task-id="{{ $task->id }}">Start</button>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="list-group-item">
                        <p class="mb-0 text-center">No overdue tasks</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card" id="my-projects">
            <div class="card-header">
                <h5 class="mb-0">My Projects</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Client</th>
                                <th>Start Date</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myProjects as $project)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                                </td>
                                <td>{{ $project->client->name }}</td>
                                <td>{{ $project->start_date->format('M d, Y') }}</td>
                                <td>
                                    @if($project->status != 'completed' && now()->gt($project->expected_end_date))
                                        <span class="text-danger">{{ $project->expected_end_date->format('M d, Y') }}</span>
                                    @else
                                        {{ $project->expected_end_date->format('M d, Y') }}
                                    @endif
                                </td>
                                <td>
                                    @if($project->status == 'active')
                                        <span class="badge bg-success">Active</span>
                                    @elseif($project->status == 'completed')
                                        <span class="badge bg-primary">Completed</span>
                                    @elseif($project->status == 'on-hold')
                                        <span class="badge bg-warning">On Hold</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($project->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $project->progress }}%;" aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100">{{ $project->progress }}%</div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No projects assigned to you</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Start task buttons
        document.querySelectorAll('.start-task').forEach(function(button) {
            button.addEventListener('click', function() {
                const taskId = this.getAttribute('data-task-id');
                if (confirm('Start working on this task?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/tasks/${taskId}/update-status`;
                    
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'PATCH';
                    
                    const statusInput = document.createElement('input');
                    statusInput.type = 'hidden';
                    statusInput.name = 'status';
                    statusInput.value = 'in-progress';
                    
                    form.appendChild(csrfToken);
                    form.appendChild(methodInput);
                    form.appendChild(statusInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
        
        // Complete task buttons
        document.querySelectorAll('.complete-task').forEach(function(button) {
            button.addEventListener('click', function() {
                const taskId = this.getAttribute('data-task-id');
                if (confirm('Mark this task as completed?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/tasks/${taskId}/update-status`;
                    
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'PATCH';
                    
                    const statusInput = document.createElement('input');
                    statusInput.type = 'hidden';
                    statusInput.name = 'status';
                    statusInput.value = 'completed';
                    
                    form.appendChild(csrfToken);
                    form.appendChild(methodInput);
                    form.appendChild(statusInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
