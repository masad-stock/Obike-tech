@extends('layouts.app')

@section('title', 'Maintenance History')

@section('header', 'Maintenance History')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Maintenance History</h1>
            <p class="text-muted">View all maintenance activities across equipment</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('mechanical.maintenance.pending') }}" class="btn btn-outline-primary">
                <i class="fas fa-clock me-1"></i>Pending Maintenance
            </a>
            <a href="{{ route('mechanical.maintenance.schedule') }}" class="btn btn-outline-secondary">
                <i class="fas fa-calendar me-1"></i>Maintenance Schedule
            </a>
        </div>
    </div>

    @include('layouts.alerts')

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Monthly Maintenance Activities</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyMaintenanceChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Maintenance Costs</h5>
                </div>
                <div class="card-body">
                    <canvas id="maintenanceCostChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <form action="{{ route('mechanical.maintenance.history') }}" method="GET" class="d-flex">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search maintenance logs..." name="search" value="{{ request('search') }}">
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
                                <li><h6 class="dropdown-header">Maintenance Type</h6></li>
                                <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.history', ['type' => 'preventive']) }}">Preventive</a></li>
                                <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.history', ['type' => 'corrective']) }}">Corrective</a></li>
                                <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.history', ['type' => 'inspection']) }}">Inspection</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Time Period</h6></li>
                                <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.history', ['period' => 'month']) }}">This Month</a></li>
                                <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.history', ['period' => 'quarter']) }}">This Quarter</a></li>
                                <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.history', ['period' => 'year']) }}">This Year</a></li>
                            </ul>
                        </div>
                        <a href="{{ route('mechanical.maintenance.export-history') }}" class="btn btn-outline-success">
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
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Cost</th>
                            <th>Performed By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maintenanceLogs as $log)
                        <tr>
                            <td>
                                <a href="{{ route('mechanical.equipment.show', $log->equipment) }}" class="fw-bold text-decoration-none">
                                    {{ $log->equipment->name }}
                                </a>
                                <div class="small text-muted">{{ $log->equipment->serial_number }}</div>
                            </td>
                            <td>
                                {{ $log->maintenance_date->format('M d, Y') }}
                                <div class="small text-muted">{{ $log->maintenance_date->diffForHumans() }}</div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $log->maintenance_type == 'preventive' ? 'info' : ($log->maintenance_type == 'corrective' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($log->maintenance_type) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($log->description, 50) }}</td>
                            <td>${{ number_format($log->cost, 2) }}</td>
                            <td>
                                {{ $log->performer->name ?? 'N/A' }}
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('mechanical.maintenance.show-log', $log) }}" class="btn btn-outline-primary" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @can('manage-maintenance')
                                    <a href="{{ route('mechanical.maintenance.edit-log', $log) }}" class="btn btn-outline-secondary" title="Edit Log">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" onclick="confirmDelete({{ $log->id }})" title="Delete Log">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-log-{{ $log->id }}" action="{{ route('mechanical.maintenance.delete-log', $log) }}" method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-tools fa-2x mb-3"></i>
                                    <p>No maintenance logs found</p>
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
                    <small class="text-muted">Showing {{ $maintenanceLogs->firstItem() ?? 0 }} to {{ $maintenanceLogs->lastItem() ?? 0 }} of {{ $maintenanceLogs->total() ?? 0 }} logs</small>
                </div>
                <div>
                    {{ $maintenanceLogs->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Maintenance by Type</h5>
                </div>
                <div class="card-body">
                    <canvas id="maintenanceTypeChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Maintenance Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-tools fa-2x text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="small text-muted">Total Maintenance</div>
                                            <div class="h4 mb-0">{{ $maintenanceLogs->total() }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-dollar-sign fa-2x text-success"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="small text-muted">Total Cost</div>
                                            <div class="h4 mb-0">${{ number_format($maintenanceLogs->sum('cost'), 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-calendar-check fa-2x text-info"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="small text-muted">This Month</div>
                                            <div class="h4 mb-0">{{ $maintenanceLogs->where('maintenance_date', '>=', now()->startOfMonth())->count() }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-clock fa-2x text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="small text-muted">Avg. Downtime</div>
                                            <div class="h4 