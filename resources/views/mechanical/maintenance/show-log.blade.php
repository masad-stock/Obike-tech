@extends('layouts.app')

@section('title', 'Maintenance Log Details')

@section('header', 'Maintenance Log Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Maintenance Log Details</h1>
            <p class="text-muted">View detailed information about this maintenance activity</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('mechanical.maintenance.history') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to History
            </a>
            <a href="{{ route('mechanical.equipment.show', $maintenanceLog->equipment) }}" class="btn btn-outline-primary">
                <i class="fas fa-tools me-1"></i>View Equipment
            </a>
        </div>
    </div>

    @include('layouts.alerts')

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Maintenance Information</h5>
                    <div>
                        @can('manage-maintenance')
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('mechanical.maintenance.edit-log', $maintenanceLog) }}" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </div>
                        <form id="delete-log-form" action="{{ route('mechanical.maintenance.delete-log', $maintenanceLog) }}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Maintenance Date</div>
                                <div class="h5 mb-0">{{ $maintenanceLog->maintenance_date->format('M d, Y') }}</div>
                                <div class="small text-muted">{{ $maintenanceLog->maintenance_date->diffForHumans() }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Maintenance Type</div>
                                <div>
                                    <span class="badge bg-{{ $maintenanceLog->maintenance_type == 'preventive' ? 'info' : ($maintenanceLog->maintenance_type == 'corrective' ? 'warning' : 'secondary') }} px-3 py-2">
                                        {{ ucfirst($maintenanceLog->maintenance_type) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="small text-muted mb-1">Description</div>
                        <div class="p-3 bg-light rounded">
                            {{ $maintenanceLog->description }}
                        </div>
                    </div>

                    @if($maintenanceLog->parts_replaced)
                    <div class="mb-4">
                        <div class="small text-muted mb-1">Parts Replaced</div>
                        <div class="p-3 bg-light rounded">
                            {{ $maintenanceLog->parts_replaced }}
                        </div>
                    </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Cost</div>
                                <div class="h5 mb-0">${{ number_format($maintenanceLog->cost, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Downtime</div>
                                <div class="h5 mb-0">{{ $maintenanceLog->downtime_hours }} hours</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Performed By</div>
                                <div class="h5 mb-0">{{ $maintenanceLog->performer->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    @if($maintenanceLog->notes)
                    <div class="mb-4">
                        <div class="small text-muted mb-1">Additional Notes</div>
                        <div class="p-3 bg-light rounded">
                            {{ $maintenanceLog->notes }}
                        </div>
                    </div>
                    @endif

                    @if($maintenanceLog->maintenanceSchedule)
                    <div class="mb-4">
                        <div class="small text-muted mb-1">Related Maintenance Schedule</div>
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">{{ $maintenanceLog->maintenanceSchedule->title }}</div>
                                    <div class="small text-muted">
                                        Frequency: {{ ucfirst($maintenanceLog->maintenanceSchedule->frequency) }}
                                    </div>
                                </div>
                                <a href="{{ route('mechanical.maintenance.schedule.edit', $maintenanceLog->maintenanceSchedule) }}" class="btn btn-sm btn-outline-primary">
                                    View Schedule
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            @if($maintenanceLog->equipment->maintenanceLogs->count() > 1)
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Other Maintenance for this Equipment</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Cost</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($maintenanceLog->equipment->maintenanceLogs->where('id', '!=', $maintenanceLog->id)->take(5) as $log)
                                <tr>
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
                                    <td class="text-end">
                                        <a href="{{ route('mechanical.maintenance.show-log', $log) }}" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-center">
                    <a href="{{ route('mechanical.equipment.show', $maintenanceLog->equipment) }}" class="text-decoration-none">
                        View All Maintenance History
                    </a>
                </div>
            </div>
            @endif
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Equipment Information</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-tools fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-0">{{ $maintenanceLog->equipment->name }}</h5>
                            <p class="text-muted mb-0">{{ $maintenanceLog->equipment->model }}</p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Serial Number</div>
                        <div class="fw-bold">{{ $maintenanceLog->equipment->serial_number }}</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Status</div>
                        <div>
                            <span class="badge bg-{{ $maintenanceLog->equipment->status == 'operational' ? 'success' : ($maintenanceLog->equipment->status == 'maintenance' ? 'warning' : 'danger') }}">
                                {{ ucfirst($maintenanceLog->equipment->status) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Category</div>
                        <div class="fw-bold">{{ $maintenanceLog->equipment->category->name ?? 'N/A' }}</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Purchase Date</div>
                        <div class="fw-bold">
                            @if($maintenanceLog->equipment->purchase_date)
                                {{ $maintenanceLog->equipment->purchase_date->format('M d, Y') }}
                                <span class="text-muted">({{ $maintenanceLog->equipment->purchase_date->diffForHumans() }})</span>
                            @else
                                Not specified
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <div class="small text-muted mb-1">Maintenance Count</div>
                        <div class="fw-bold">{{ $maintenanceLog->equipment->maintenanceLogs->count() }} records</div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('mechanical.equipment.show', $maintenanceLog->equipment) }}" class="btn btn-primary w-100">
                            <i class="fas fa-tools me-1"></i>View Equipment Details
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Maintenance Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Total Maintenance Cost</div>
                        <div class="h4 mb-0">${{ number_format($maintenanceLog->equipment->maintenanceLogs->sum('cost'), 2) }}</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Average Maintenance Cost</div>
                        <div class="h5 mb-0">
                            ${{ number_format($maintenanceLog->equipment->maintenanceLogs->avg('cost'), 2) }}
                            <span class="small text-muted">per maintenance</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Total Downtime</div>
                        <div class="h5 mb-0">
                            {{ number_format($maintenanceLog->equipment->maintenanceLogs->sum('downtime_hours'), 1) }}
                            <span class="small text-muted">hours</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Maintenance Types</div>
                        <div class="d-flex justify-content-between mt-2">
                            <div>
                                <span class="badge bg-info px-2 py-1">Preventive</span>
                                <div class="text-center mt-1">
                                    {{ $maintenanceLog->equipment->maintenanceLogs->where('maintenance_type', 'preventive')->count() }}
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-warning px-2 py-1">Corrective</span>
                                <div class="text-center mt-1">
                                    {{ $maintenanceLog->equipment->maintenanceLogs->where('maintenance_type', 'corrective')->count() }}
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-secondary px-2 py-1">Inspection</span>
                                <div class="text-center mt-1">
                                    {{ $maintenanceLog->equipment->maintenanceLogs->where('maintenance_type', 'inspection')->count() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Confirm delete
    function confirmDelete() {
        if (confirm('Are you sure you want to delete this maintenance log? This action cannot be undone.')) {
            document.getElementById('delete-log-form').submit();
        }
    }
</script>
@endsection