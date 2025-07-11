@extends('layouts.app')

@section('title', $project->name . ' - Report')

@section('header', 'Project Report')

@section('styles')
<style>
    @media print {
        .no-print {
            display: none !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .page-break {
            page-break-after: always;
        }
    }
    
    .progress {
        height: 20px;
    }
    
    .chart-container {
        height: 300px;
    }
</style>
@endsection

@section('content')
<div class="row mb-4 no-print">
    <div class="col-md-8">
        <div class="d-flex align-items-center">
            <h1 class="h3 mb-0">{{ $project->name }} - Project Report</h1>
            <span class="badge bg-{{ $project->status == 'active' ? 'success' : ($project->status == 'completed' ? 'primary' : ($project->status == 'on-hold' ? 'warning' : 'secondary')) }} ms-2">
                {{ ucfirst($project->status) }}
            </span>
        </div>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group">
            <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i>Back to Project
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-1"></i>Print Report
            </button>
            <a href="{{ route('projects.report.download', $project) }}" class="btn btn-success">
                <i class="fas fa-download me-1"></i>Download PDF
            </a>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="mb-0">Project Summary</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%">Project Name</th>
                        <td>{{ $project->name }}</td>
                    </tr>
                    <tr>
                        <th>Client</th>
                        <td>{{ $project->client->name }}</td>
                    </tr>
                    <tr>
                        <th>Project Manager</th>
                        <td>{{ $project->manager->name }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-{{ $project->status == 'active' ? 'success' : ($project->status == 'completed' ? 'primary' : ($project->status == 'on-hold' ? 'warning' : 'secondary')) }}">
                                {{ ucfirst($project->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Priority</th>
                        <td>
                            <span class="badge bg-{{ $project->priority == 'high' ? 'danger' : ($project->priority == 'medium' ? 'warning' : 'info') }}">
                                {{ ucfirst($project->priority) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%">Start Date</th>
                        <td>{{ $project->start_date->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <th>Expected End Date</th>
                        <td>{{ $project->expected_end_date->format('M d, Y') }}</td>
                    </tr>
                    @if($project->actual_end_date)
                    <tr>
                        <th>Actual End Date</th>
                        <td>{{ $project->actual_end_date->format('M d, Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Budget</th>
                        <td>${{ number_format($project->budget, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Total Expenses</th>
                        <td>${{ number_format($totalExpenses, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="mb-0">Project Progress</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-4">
                <h5>Task Completion</h5>
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $taskCompletionRate }}%;" aria-valuenow="{{ $taskCompletionRate }}" aria-valuemin="0" aria-valuemax="100">{{ number_format($taskCompletionRate, 1) }}%</div>
                </div>
                <div class="small text-muted">{{ $completedTasks }} of {{ $totalTasks }} tasks completed</div>
            </div>
            
            <div class="col-md-6 mb-4">
                <h5>Budget Utilization</h5>
                <div class="progress mb-2">
                    <div class="progress-bar bg-{{ $budgetUtilization > 100 ? 'danger' : 'info' }}" role="progressbar" style="width: {{ min($budgetUtilization, 100) }}%;" aria-valuenow="{{ $budgetUtilization }}" aria-valuemin="0" aria-valuemax="100">{{ number_format($budgetUtilization, 1) }}%</div>
                </div>
                <div class="small text-muted">${{ number_format($totalExpenses, 2) }} of ${{ number_format($totalBudget, 2) }} budget used</div>
            </div>
            
            <div class="col-md-6 mb-4">
                <h5>Time Progress</h5>
                @php
                    $totalDays = $project->start_date->diffInDays($project->expected_end_date);
                    $elapsedDays = $project->start_date->diffInDays(now());
                    $timeProgress = $totalDays > 0 ? min(100, ($elapsedDays / $totalDays) * 100) : 0;
                @endphp
                <div class="progress mb-2">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $timeProgress }}%;" aria-valuenow="{{ $timeProgress }}" aria-valuemin="0" aria-valuemax="100">{{ number_format($timeProgress, 1) }}%</div>
                </div>
                <div class="small text-muted">{{ $elapsedDays }} of {{ $totalDays }} days elapsed</div>
            </div>
            
            <div class="col-md-6 mb-4">
                <h5>Overall Project Health</h5>
                @php
                    $healthScore = ($taskCompletionRate / $timeProgress) * 100;
                    $healthStatus = $healthScore >= 110 ? 'Excellent' : ($healthScore >= 90 ? 'Good' : ($healthScore >= 70 ? 'Fair' : 'At Risk'));
                    $healthColor = $healthScore >= 110 ? 'success' : ($healthScore >= 90 ? 'primary' : ($healthScore >= 70 ? 'warning' : 'danger'));
                @endphp
                <div class="alert alert-{{ $healthColor }} mb-2">
                    <strong>{{ $healthStatus }}</strong> - Health Score: {{ number_format($healthScore, 1) }}%
                </div>
                <div class="small text-muted">Based on task completion relative to time elapsed</div>
            </div>
        </div>
    </div>
</div>

<div class="page-break"></div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="mb-0">Task Status</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-4">
                <h5>Tasks by Status</h5>
                <div class="chart-container">
                    <canvas id="taskStatusChart"></canvas>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <h5>Tasks by Priority</h5>
                <div class="chart-container">
                    <canvas id="taskPriorityChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="table-responsive mt-4">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Task Name</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->tasks as $task)
                    <tr>
                        <td>{{ $task->name }}</td>
                        <td>
                            @if($task->assignedTo)
                                {{ $task->assignedTo->name }}
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </td>
                        <td>{{ $task->due_date->format('M d, Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $task->status == 'completed' ? 'success' : ($task->status == 'in-progress' ? 'primary' : ($task->status == 'review' ? 'info' : ($task->status == 'cancelled' ? 'secondary' : 'warning'))) }}">
                                {{ ucfirst($task->status) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $task->priority == 'high' ? 'danger' : ($task->priority == 'medium' ? 'warning' : 'info') }}">
                                {{ ucfirst($task->priority) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="page-break"></div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="mb-0">Financial Summary</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-4">
                <h5>Expenses by Category</h5>
                <div class="chart-container">
                    <canvas id="expenseCategoryChart"></canvas>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <h5>Budget vs Actual</h5>
                <div class="chart-container">
                    <canvas id="budgetChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="table-responsive mt-4">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->expenses as $expense)
                    <tr>
                        <td>{{ ucfirst($expense->category) }}</td>
                        <td>{{ $expense->description }}</td>
                        <td>{{ $expense->date->format('M d, Y') }}</td>
                        <td class="text-end">${{ number_format($expense->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total Expenses:</th>
                        <th class="text-end">${{ number_format($totalExpenses, 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Budget:</th>
                        <th class="text-end">${{ number_format($totalBudget, 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Remaining Budget:</th>
                        <th class="text-end" class="{{ ($totalBudget - $totalExpenses) < 0 ? 'text-danger' : '' }}">
                            ${{ number_format($totalBudget - $totalExpenses, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="mb-0">Team Members</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Tasks Assigned</th>
                        <th>Tasks Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->members as $member)
                    @php
                        $tasksAssigned = $project->tasks->where('assigned_to', $member->user_id)->count();
                        $tasksCompleted = $project->tasks->where('assigned_to', $member->user_id)->where('status', 'completed')->count();
                    @endphp
                    <tr>
                        <td>{{ $member->user->name }}</td>
                        <td>{{ ucfirst($member->role) }}</td>
                        <td>{{ $member->user->email }}</td>
                        <td>{{ $tasksAssigned }}</td>
                        <td>{{ $tasksCompleted }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Task Status Chart
        const taskStatusCtx = document.getElementById('taskStatusChart').getContext('2d');
        const taskStatusChart = new Chart(taskStatusCtx, {
            type: 'pie',
            data: {
                labels: [
                    'Pending', 
                    'In Progress', 
                    'Review', 
                    'Completed', 
                    'Cancelled'
                ],
                datasets: [{
                    data: [
                        {{ $tasksByStatus->get('pending', collect())->count() }},
                        {{ $tasksByStatus->get('in-progress', collect())->count() }},
                        {{ $tasksByStatus->get('review', collect())->count() }},
                        {{ $tasksByStatus->get('completed', collect())->count() }},
                        {{ $tasksByStatus->get('cancelled', collect())->count() }}
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#0d6efd',
                        '#17a2b8',
                        '#198754',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Task Priority Chart
        const taskPriorityCtx = document.getElementById('taskPriorityChart').getContext('2d');
        const taskPriorityChart = new Chart(taskPriorityCtx, {
            type: 'pie',
            data: {
                labels: ['Low', 'Medium', 'High', 'Urgent'],
                datasets: [{
                    data: [
                        {{ $project->tasks->where('priority', 'low')->count() }},
                        {{ $project->tasks->where('priority', 'medium')->count() }},
                        {{ $project->tasks->where('priority', 'high')->count() }},
                        {{ $project->tasks->where('priority', 'urgent')->count() }}
                    ],
                    backgroundColor: [
                        '#17a2b8',
                        '#ffc107',
                        '#dc3545',
                        '#6610f2'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Expense Category Chart
        const expenseCategoryCtx = document.getElementById('expenseCategoryChart').getContext('2d');
        const expenseCategoryChart = new Chart(expenseCategoryCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode($expensesByCategory->keys()->map(function($category) { return ucfirst($category); })) !!},
                datasets: [{
                    data: {!! json_encode($expensesByCategory->map(function($expenses) { return $expenses->sum('amount'); })) !!},
                    backgroundColor: [
                        '#0d6efd',
                        '#198754',
                        '#dc3545',
                        '#ffc107',
                        '#6610f2',
                        '#fd7e14',
                        '#20c997',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Budget Chart
        const budgetCtx = document.getElementById('budgetChart').getContext('2d');
        const budgetChart = new Chart(budgetCtx, {
            type: 'bar',
            data: {
                labels: ['Budget Allocation'],
                datasets: [
                    {
                        label: 'Budget',
                        data: [{{ $totalBudget }}],
                        backgroundColor: '#0d6efd'
                    },
                    {
                        label: 'Actual Expenses',
                        data: [{{ $totalExpenses }}],
                        backgroundColor: '#dc3545'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endsection