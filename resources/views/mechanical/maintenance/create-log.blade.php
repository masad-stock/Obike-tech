@extends('layouts.app')

@section('title', 'Log Maintenance')

@section('header', 'Log Maintenance')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Log Maintenance</h1>
            <p class="text-muted">Record maintenance performed on equipment</p>
        </div>
        <div>
            <a href="{{ route('mechanical.equipment.show', $equipment) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Equipment
            </a>
        </div>
    </div>

    @include('layouts.alerts')

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Maintenance Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('mechanical.maintenance.create', $equipment) }}" method="POST">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="maintenance_date" class="form-label">Maintenance Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('maintenance_date') is-invalid @enderror" 
                                    id="maintenance_date" name="maintenance_date" 
                                    value="{{ old('maintenance_date', now()->format('Y-m-d')) }}" required>
                                @error('maintenance_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="maintenance_type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('maintenance_type') is-invalid @enderror" 
                                    id="maintenance_type" name="maintenance_type" required>
                                    <option value="preventive" {{ old('maintenance_type', $schedule ? 'preventive' : '') == 'preventive' ? 'selected' : '' }}>Preventive Maintenance</option>
                                    <option value="corrective" {{ old('maintenance_type') == 'corrective' ? 'selected' : '' }}>Corrective Maintenance</option>
                                    <option value="inspection" {{ old('maintenance_type') == 'inspection' ? 'selected' : '' }}>Inspection</option>
                                </select>
                                @error('maintenance_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="maintenance_schedule_id" class="form-label">Related Maintenance Schedule</label>
                            <select class="form-select @error('maintenance_schedule_id') is-invalid @enderror" 
                                id="maintenance_schedule_id" name="maintenance_schedule_id">
                                <option value="">-- None --</option>
                                @foreach($maintenanceSchedules as $maintenanceSchedule)
                                    <option value="{{ $maintenanceSchedule->id }}" 
                                        {{ old('maintenance_schedule_id', $schedule ? $schedule->id : '') == $maintenanceSchedule->id ? 'selected' : '' }}>
                                        {{ $maintenanceSchedule->title }} (Due: {{ $maintenanceSchedule->next_maintenance_date->format('M d, Y') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('maintenance_schedule_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="parts_replaced" class="form-label">Parts Replaced</label>
                            <textarea class="form-control @error('parts_replaced') is-invalid @enderror" 
                                id="parts_replaced" name="parts_replaced" rows="2">{{ old('parts_replaced') }}</textarea>
                            @error('parts_replaced')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cost" class="form-label">Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control @error('cost') is-invalid @enderror" 
                                        id="cost" name="cost" step="0.01" min="0" value="{{ old('cost', '0.00') }}">
                                    @error('cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="downtime_hours" class="form-label">Downtime (Hours)</label>
                                <input type="number" class="form-control @error('downtime_hours') is-invalid @enderror" 
                                    id="downtime_hours" name="downtime_hours" step="0.5" min="0" value="{{ old('downtime_hours', '0') }}">
                                @error('downtime_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="update_status" name="update_status" value="1" 
                                {{ old('update_status') ? 'checked' : '' }}>
                            <label class="form-check-label" for="update_status">
                                Update equipment status to operational
                            </label>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('mechanical.equipment.show', $equipment) }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Maintenance Log
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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
                            <h5 class="mb-0">{{ $equipment->name }}</h5>
                            <p class="text-muted mb-0">{{ $equipment->model }}</p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Serial Number</div>
                        <div class="fw-bold">{{ $equipment->serial_number }}</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Status</div>
                        <div>
                            <span class="badge bg-{{ $equipment->status == 'operational' ? 'success' : ($equipment->status == 'maintenance' ? 'warning' : 'danger') }}">
                                {{ ucfirst($equipment->status) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Category</div>
                        <div class="fw-bold">{{ $equipment->category->name ?? 'N/A' }}</div>
                    </div>
                    
                    <div>
                        <div class="small text-muted mb-1">Last Maintenance</div>
                        <div class="fw-bold">
                            @if($equipment->maintenanceLogs->count() > 0)
                                {{ $equipment->maintenanceLogs->sortByDesc('maintenance_date')->first()->maintenance_date->format('M d, Y') }}
                                <span class="text-muted">({{ $equipment->maintenanceLogs->sortByDesc('maintenance_date')->first()->maintenance_date->diffForHumans() }})</span>
                            @else
                                No maintenance records
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            @if($schedule)
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Scheduled Maintenance</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Title</div>
                        <div class="fw-bold">{{ $schedule->title }}</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Due Date</div>
                        <div class="fw-bold">
                            {{ $schedule->next_maintenance_date->format('M d, Y') }}
                            <span class="text-{{ $schedule->next_maintenance_date->isPast() ? 'danger' : 'muted' }}">
                                ({{ $schedule->next_maintenance_date->diffForHumans() }})
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Frequency</div>
                        <div class="fw-bold">{{ ucfirst($schedule->frequency) }}</div>
                    </div>
                    
                    <div>
                        <div class="small text-muted mb-1">Description</div>
                        <div>{{ $schedule->description }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection