@extends('layouts.app')

@section('title', $equipment->name)

@section('header', 'Equipment Details')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="d-flex align-items-center">
            <h1 class="h3 mb-0">{{ $equipment->name }}</h1>
            <span class="badge bg-{{ $equipment->status == 'operational' ? 'success' : ($equipment->status == 'maintenance' ? 'warning' : ($equipment->status == 'repair' ? 'danger' : 'secondary')) }} ms-2">
                {{ ucfirst($equipment->status) }}
            </span>
        </div>
        <div class="text-muted mt-1">
            {{ $equipment->category->name ?? 'Uncategorized' }} &raquo; {{ $equipment->model_number }}
        </div>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group">
            <a href="{{ route('mechanical.equipment') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i>Back to Equipment
            </a>
            @can('manage-equipment')
            <a href="{{ route('mechanical.equipment.edit', $equipment) }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>Edit Equipment
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Equipment Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%">Serial Number</th>
                                <td>{{ $equipment->serial_number }}</td>
                            </tr>
                            <tr>
                                <th>Manufacturer</th>
                                <td>{{ $equipment->manufacturer ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Supplier</th>
                                <td>{{ $equipment->supplier ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Purchase Date</th>
                                <td>{{ $equipment->purchase_date ? $equipment->purchase_date->format('M d, Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Purchase Cost</th>
                                <td>{{ $equipment->purchase_cost ? '$'.number_format($equipment->purchase_cost, 2) : 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%">Warranty Until</th>
                                <td>
                                    @if($equipment->warranty_expiry)
                                        {{ $equipment->warranty_expiry->format('M d, Y') }}
                                        @if($equipment->warranty_expiry->isPast())
                                            <span class="badge bg-danger">Expired</span>
                                        @elseif($equipment->warranty_expiry->diffInDays(now()) < 30)
                                            <span class="badge bg-warning">Expiring Soon</span>
                                        @endif
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-{{ $equipment->status == 'operational' ? 'success' : ($equipment->status == 'maintenance' ? 'warning' : ($equipment->status == 'repair' ? 'danger' : 'secondary')) }}">
                                        {{ ucfirst($equipment->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td>{{ $equipment->category->name ?? 'Uncategorized' }}</td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td>{{ $equipment->created_at->format('M d, Y') }}</td>
                            </tr>
                            <tr>
                                <th>Last Updated</th>
                                <td>{{ $equipment->updated_at->format('M d, Y') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($equipment->specifications)
                <div class="mt-4">
                    <h6 class="text-muted mb-2">Specifications</h6>
                    <div class="p-3 bg-light rounded">
                        {!! nl2br(e($equipment->specifications)) !!}
                    </div>
                </div>
                @endif

                @if($equipment->notes)
                <div class="mt-4">
                    <h6 class="text-muted mb-2">Notes</h6>
                    <div class="p-3 bg-light rounded">
                        {!! nl2br(e($equipment->notes)) !!}
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Maintenance History</h5>
                @can('manage-maintenance')
                <a href="{{ route('mechanical.maintenance.create', $equipment) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Log Maintenance
                </a>
                @endcan
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
                                <th>Performed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($equipment->maintenanceLogs()->orderBy('maintenance_date', 'desc')->get() as $log)
                            <tr>
                                <td>{{ $log->maintenance_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->maintenance_type == 'preventive' ? 'info' : ($log->maintenance_type == 'corrective' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($log->maintenance_type) }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($log->description, 50) }}</td>
                                <td>${{ number_format($log->cost, 2) }}</td>
                                <td>{{ $log->performer->name ?? 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
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
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assignment History</h5>
                @if($equipment->status == 'operational')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignEquipmentModal">
                    <i class="fas fa-user-plus me-1"></i>Assign Equipment
                </button>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Assigned To</th>
                                <th>Assigned Date</th>
                                <th>Return Date</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($equipment->assignments()->orderBy('assignment_date', 'desc')->get() as $assignment)
                            <tr>
                                <td>{{ $assignment->assignee->name ?? 'N/A' }}</td>
                                <td>{{ $assignment->assignment_date->format('M d, Y') }}</td>
                                <td>{{ $assignment->return_date ? $assignment->return_date->format('M d, Y') : 'Not returned' }}</td>
                                <td>{{ Str::limit($assignment->purpose, 50) }}</td>
                                <td>
                                    <span class="badge bg-{{ $assignment->status == 'active' ? 'primary' : ($assignment->status == 'returned' ? 'success' : 'danger') }}">
                                        {{ ucfirst($assignment->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($assignment->status == 'active')
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#returnEquipmentModal" 
                                        data-assignment-id="{{ $assignment->id }}">
                                        <i class="fas fa-undo"></i> Return
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-user-slash fa-2x mb-3"></i>
                                        <p>No assignment history found</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Maintenance Schedule</h5>
                @can('manage-maintenance')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleMaintenanceModal">
                    <i class="fas fa-calendar-plus me-1"></i>Schedule
                </button>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($equipment->maintenanceSchedules()->orderBy('next_maintenance_date')->get() as $schedule)
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">{{ $schedule->title }}</h6>
                            <small class="{{ $schedule->next_maintenance_date->isPast() ? 'text-danger' : 'text-muted' }}">
                                {{ $schedule->next_maintenance_date->format('M d, Y') }}
                            </small>
                        </div>
                        <p class="mb-1">{{ Str::limit($schedule->description, 100) }}</p>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted">{{ ucfirst($schedule->frequency) }} maintenance</small>
                            @if($schedule->next_maintenance_date->isPast())
                            <span class="badge bg-danger">Overdue</span>
                            @elseif($schedule->next_maintenance_date->diffInDays(now()) < 7)
                            <span class="badge bg-warning">Due soon</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="list-group-item text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-calendar-alt fa-2x mb-3"></i>
                            <p>No maintenance schedules found</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @can('manage-maintenance')
                    <a href="{{ route('mechanical.maintenance.create', $equipment) }}" class="btn btn-outline-primary">
                        <i class="fas fa-tools me-1"></i>Log Maintenance
                    </a>
                    @endcan
                    
                    @can('manage-equipment')
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                        <i class="fas fa-sync-alt me-1"></i>Update Status
                    </button>
                    @endcan
                    
                    <a href="{{ route('mechanical.equipment.print', $equipment) }}" class="btn btn-outline-info" target="_blank">
                        <i class="fas fa-print me-1"></i>Print Details
                    </a>
                    
                    @can('manage-equipment')
                    <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-1"></i>Delete Equipment
                    </button>
                    <form id="delete-equipment-form" action="{{ route('mechanical.equipment.delete', $equipment) }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Equipment Modal -->
<div class="modal fade" id="assignEquipmentModal" tabindex="-1" aria-labelledby="assignEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('mechanical.equipment.assign', $equipment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="assignEquipmentModalLabel">Assign Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label">Assign To <span class="text-danger">*</span></label>
                        <select class="form-select" id="assigned_to" name="assigned_to" required>
                            <option value="">Select User</option>
                            @foreach(\App\Models\User::orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assignment_date" class="form-label">Assignment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="assignment_date" name="assignment_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="expected_return_date" class="form-label">Expected Return Date</label>
                        <input type="date" class="form-control" id="expected_return_date" name="expected_return_date">
                    </div>
                    <div class="mb-3">
                        <label for="purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Equipment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Equipment Modal -->
<div class="modal fade" id="returnEquipmentModal" tabindex="-1" aria-labelledby="returnEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('mechanical.equipment.return') }}" method="POST">
                @csrf
                <input type="hidden" name="assignment_id" id="return_assignment_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnEquipmentModalLabel">Return Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="return_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="return_date" name="return_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="condition_on_return" class="form-label">Condition <span class="text-danger">*</span></label>
                        <select class="form-select" id="condition_on_return" name="condition_on_return" required>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="return_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Return Equipment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('mechanical.equipment.update-status', $equipment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Equipment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="operational" {{ $equipment->status == 'operational' ? 'selected' : '' }}>Operational</option>
                            <option value="maintenance" {{ $equipment->status == 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                            <option value="repair" {{ $equipment->status == 'repair' ? 'selected' : '' }}>Needs Repair</option>
                            <option value="retired" {{ $equipment->status == 'retired' ? 'selected' : '' }}>Retired</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="status_notes" name="notes" rows="3" placeholder="Provide details about this status change"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Maintenance Modal -->
<div class="modal fade" id="scheduleMaintenanceModal" tabindex="-1" aria-labelledby="scheduleMaintenanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('mechanical.maintenance.schedule.create', $equipment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleMaintenanceModalLabel">Schedule Maintenance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="frequency" class="form-label">Frequency <span class="text-danger">*</span></label>
                        <select class="form-select" id="frequency" name="frequency" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly" selected>Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="biannual">Bi-Annual</option>
                            <option value="annual">Annual</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="next_maintenance_date" class="form-label">Next Maintenance Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="next_maintenance_date" name="next_maintenance_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="estimated_cost" class="form-label">Estimated Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="estimated_cost" name="estimated_cost" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set default date for next maintenance to 30 days from now
        const nextMaintenanceDate = document.getElementById('next_maintenance_date');
        if (nextMaintenanceDate && !nextMaintenanceDate.value) {
            const thirtyDaysFromNow = new Date();
            thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
            nextMaintenanceDate.value = thirtyDaysFromNow.toISOString().split('T')[0];
        }
        
        // Handle return equipment modal
        const returnEquipmentModal = document.getElementById('returnEquipmentModal');
        if (returnEquipmentModal) {
            returnEquipmentModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const assignmentId = button.getAttribute('data-assignment-id');
                document.getElementById('return_assignment_id').value = assignmentId;
            });
        }
    });
    
    // Confirm delete
    function confirmDelete() {
        if (confirm('Are you sure you want to delete this equipment? This action cannot be undone.')) {
            document.getElementById('delete-equipment-form').submit();
        }
    }
</script>
@endsection
