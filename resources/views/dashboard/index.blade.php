@extends('layouts.app')

@section('title', 'Dashboard')

@section('header', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Active Projects</h6>
                        <h2 class="mb-0">{{ $activeProjects }}</h2>
                    </div>
                    <i class="fas fa-project-diagram fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="{{ route('projects.index') }}" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Pending Tasks</h6>
                        <h2 class="mb-0">{{ $pendingTasks }}</h2>
                    </div>
                    <i class="fas fa-tasks fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="{{ route('tasks.index') }}" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Revenue</h6>
                        <h2 class="mb-0">${{ number_format($totalRevenue, 2) }}</h2>
                    </div>
                    <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="{{ route('reports.financial') }}" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-danger text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Expenses</h6>
                        <h2 class="mb-0">${{ number_format($totalExpenses, 2) }}</h2>
                    </div>
                    <i class="fas fa-chart-line fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="{{ route('reports.financial') }}" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Projects</h5>
                <a href="{{ route('projects.index') }}" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Client</th>
                                <th>Status</th>
                                <th>Deadline</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentProjects as $project)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                                </td>
                                <td>{{ $project->client->name }}</td>
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
                                <td>{{ $project->expected_end_date->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">No projects found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Tasks</h5>
                <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTasks as $task)
                            <tr>
                                <td>
                                    <a href="{{ route('tasks.show', $task) }}">{{ $task->name }}</a>
                                </td>
                                <td>{{ $task->project->name }}</td>
                                <td>
                                    @if($task->status == 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($task->status == 'in-progress')
                                        <span class="badge bg-primary">In Progress</span>
                                    @elseif($task->status == 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($task->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $task->due_date->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">No tasks found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">My Tasks</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myTasks as $task)
                            <tr>
                                <td>
                                    <a href="{{ route('tasks.show', $task) }}">{{ $task->name }}</a>
                                </td>
                                <td>{{ $task->project->name }}</td>
                                <td>
                                    @if($task->status == 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($task->status == 'in-progress')
                                        <span class="badge bg-primary">In Progress</span>
                                    @elseif($task->status == 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($task->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $task->due_date->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">No tasks assigned to you</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Upcoming Deadlines</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Days Left</th>
                                <th>Deadline</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingDeadlines as $deadline)
                            <tr>
                                <td>
                                    @if($deadline['type'] == 'project')
                                        <a href="{{ route('projects.show', $deadline['item']) }}">{{ $deadline['item']->name }}</a>
                                    @else
                                        <a href="{{ route('tasks.show', $deadline['item']) }}">{{ $deadline['item']->name }}</a>
                                    @endif
                                </td>
                                <td>{{ ucfirst($deadline['type']) }}</td>
                                <td>
                                    @if($deadline['days_left'] < 0)
                                        <span class="text-danger">Overdue ({{ abs($deadline['days_left']) }} days)</span>
                                    @elseif($deadline['days_left'] == 0)
                                        <span class="text-warning">Due today</span>
                                    @else
                                        {{ $deadline['days_left'] }} days
                                    @endif
                                </td>
                                <td>{{ $deadline['date']->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">No upcoming deadlines</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Project Progress</h5>
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
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projectProgress as $project)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                                </td>
                                <td>{{ $project->client->name }}</td>
                                <td>{{ $project->start_date->format('M d, Y') }}</td>
                                <td>{{ $project->expected_end_date->format('M d, Y') }}</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $project->progress }}%;" aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100">{{ $project->progress }}%</div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No active projects</td>
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