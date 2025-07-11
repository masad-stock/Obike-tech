@extends('layouts.app')

@section('title', 'Pending Maintenance')

@section('header', 'Pending Maintenance')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="h3 mb-0">Pending Maintenance</h1>
        <p class="text-muted">View and manage all pending maintenance tasks</p>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group">
            <a href="{{ route('mechanical.maintenance.schedule') }}" class="btn btn-outline-primary">
                <i class="fas fa-calendar me-1"></i>Maintenance Schedule
            </a>
            <a href="{{ route('mechanical.maintenance.history') }}" class="btn btn-outline-secondary">
                <i class="fas fa-history me-1"></i>Maintenance History
            </a>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <div class="row align-items-center">
            <div class="col-md-8">
                <form action="{{ route('mechanical.maintenance.pending') }}" method="GET" class="d-flex">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search maintenance..." name="search" value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <div class="d-flex justify-content-end">
                    <div class="dropdown me-2">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                            <li><h6 class="dropdown-header">Priority</h6></li>
                            <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.pending', ['priority' => 'high']) }}">High Priority</a></li>
                            <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.pending', ['priority' => 'medium']) }}">Medium Priority</a></li>
                            <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.pending', ['priority' => 'low']) }}">Low Priority</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Status</h6></li>
                            <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.pending', ['status' => 'overdue']) }}">Overdue</a></li>
                            <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.pending', ['status' => 'upcoming']) }}">Upcoming</a></li>
                        </ul>
                    </div>
                    <a href="{{ route('mechanical.maintenance.export') }}" class="btn btn-outline-success">
                        <i class="fas fa-file-excel me-1"></i>Export
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Equipment</th>
                        <th>Maintenance Type</th>
                        <th>Due Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($maintenanceTasks as $task)
                    <tr class="{{ $task->next_maintenance_date->isPast() ? 'table-danger' : '' }}">
                        <td>
                            <a href="{{ route('mechanical.equipment.show', $task->equipment) }}" class="fw-bold text-decoration-none">
                                {{ $task->equipment->name }}
                            </a>
                            <div class="small text-muted">{{ $task->equipment->serial_number }}</div>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $task->title }}</div>
                            <div class="small text-muted">{{ Str::limit($task->description, 50) }}</div>
                        </td>
                        <td>
                            {{ $task->next_maintenance_date->format('M d, Y') }}
                            <div class="small {{ $task->next_maintenance_date->isPast() ? 'text-danger' : 'text-muted' }}">
                                {{ $task->next_maintenance_date->isPast() ? $task->next_maintenance_date->diffForHumans() . ' ago' : $task->next_maintenance_date->diffForHumans() }}
                            </div>
                        </td>
                        <td>
                            @php
                                $priorityClass = 'secondary';
                                if ($task->next_maintenance_date->isPast()) {
                                    $priorityClass = 'danger';
                                } elseif ($task->next_maintenance_date->diffInDays(now()) < 7) {
                                    $priorityClass = 'warning';
                                }
                            @endphp
                            <span class="badge bg-{{ $priorityClass }}">
                                {{ $task->next_maintenance_date->isPast() ? 'Overdue' : ($task->next_maintenance_date->diffInDays(now()) < 7 ? 'Urgent' : 'Scheduled') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $task->frequency == 'daily' ? 'info' : ($task->frequency == 'weekly' ? 'primary' : 'secondary') }}">
                                {{ ucfirst($task->frequency) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('mechanical.maintenance.create', ['equipment' => $task->equipment, 'schedule' => $task->id]) }}" class="btn btn-success" title="Complete Maintenance">
                                    <i class="fas fa-check"></i> Complete
                                </a>
                                <a href="{{ route('mechanical.maintenance.schedule.edit', $task) }}" class="btn btn-outline-secondary" title="Edit Schedule">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger" onclick="confirmDelete({{ $task->id }})" title="Delete Schedule">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <form id="delete-schedule-{{ $task->id }}" action="{{ route('mechanical.maintenance.schedule.delete', $task) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-tools fa-2x mb-3"></i>
                                <p>No pending maintenance tasks found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted">Showing {{ $maintenanceTasks->firstItem() ?? 0 }} to {{ $maintenanceTasks->lastItem() ?? 0 }} of {{ $maintenanceTasks->total() ?? 0 }} tasks</small>
            </div>
            <div>
                {{ $maintenanceTasks->links() }}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Maintenance by Equipment Category</h5>
            </div>
            <div class="card-body">
                <canvas id="maintenanceCategoryChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Maintenance Timeline</h5>
            </div>
            <div class="card-body">
                <canvas id="maintenanceTimelineChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Confirm delete
    function confirmDelete(id) {
        if (confirm('Are you sure you want to delete this maintenance schedule? This action cannot be undone.')) {
            document.getElementById('delete-schedule-' + id).submit();
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Maintenance by Category Chart
        const categoryCtx = document.getElementById('maintenanceCategoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode($categoryLabels ?? []) !!},
                datasets: [{
                    data: {!! json_encode($categoryData ?? []) !!},
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Maintenance Timeline Chart
        const timelineCtx = document.getElementById('maintenanceTimelineChart').getContext('2d');
        const timelineChart = new Chart(timelineCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($timelineLabels ?? []) !!},
                datasets: [{
                    label: 'Upcoming Maintenance',
                    data: {!! json_encode($timelineData ?? []) !!},
                    backgroundColor: '#4e73df'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    });
</script>
@endsection