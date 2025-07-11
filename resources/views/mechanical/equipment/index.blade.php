@extends('layouts.app')

@section('title', 'Equipment Management')

@section('header', 'Equipment Management')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="h3 mb-0">Equipment Inventory</h1>
        <p class="text-muted">Manage all company equipment and maintenance schedules</p>
    </div>
    <div class="col-md-4 text-end">
        @can('manage-equipment')
        <a href="{{ route('mechanical.equipment.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add New Equipment
        </a>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <div class="row align-items-center">
            <div class="col-md-8">
                <form action="{{ route('mechanical.equipment') }}" method="GET" class="d-flex">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search equipment..." name="search" value="{{ request('search') }}">
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
                            <li><h6 class="dropdown-header">Status</h6></li>
                            <li><a class="dropdown-item" href="{{ route('mechanical.equipment', ['status' => 'operational']) }}">Operational</a></li>
                            <li><a class="dropdown-item" href="{{ route('mechanical.equipment', ['status' => 'maintenance']) }}">Under Maintenance</a></li>
                            <li><a class="dropdown-item" href="{{ route('mechanical.equipment', ['status' => 'repair']) }}">Needs Repair</a></li>
                            <li><a class="dropdown-item" href="{{ route('mechanical.equipment', ['status' => 'retired']) }}">Retired</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Category</h6></li>
                            @foreach(\App\Models\EquipmentCategory::all() as $category)
                            <li><a class="dropdown-item" href="{{ route('mechanical.equipment', ['category' => $category->id]) }}">{{ $category->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <a href="{{ route('mechanical.equipment.export') }}" class="btn btn-outline-success">
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
                        <th>Name</th>
                        <th>Category</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>Purchase Date</th>
                        <th>Warranty Until</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($equipment as $item)
                    <tr>
                        <td>
                            <a href="{{ route('mechanical.equipment.show', $item) }}" class="fw-bold text-decoration-none">
                                {{ $item->name }}
                            </a>
                            <div class="small text-muted">{{ $item->model_number }}</div>
                        </td>
                        <td>{{ $item->category->name ?? 'Uncategorized' }}</td>
                        <td>{{ $item->serial_number }}</td>
                        <td>
                            <span class="badge bg-{{ $item->status == 'operational' ? 'success' : ($item->status == 'maintenance' ? 'warning' : ($item->status == 'repair' ? 'danger' : 'secondary')) }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td>{{ $item->purchase_date ? $item->purchase_date->format('M d, Y') : 'N/A' }}</td>
                        <td>
                            @if($item->warranty_expiry)
                                {{ $item->warranty_expiry->format('M d, Y') }}
                                @if($item->warranty_expiry->isPast())
                                    <span class="badge bg-danger">Expired</span>
                                @elseif($item->warranty_expiry->diffInDays(now()) < 30)
                                    <span class="badge bg-warning">Expiring Soon</span>
                                @endif
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('mechanical.equipment.show', $item) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('manage-equipment')
                                <a href="{{ route('mechanical.equipment.edit', $item) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('manage-maintenance')
                                <a href="{{ route('mechanical.maintenance.create', $item) }}" class="btn btn-outline-warning" title="Log Maintenance">
                                    <i class="fas fa-tools"></i>
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-tools fa-2x mb-3"></i>
                                <p>No equipment found. @can('manage-equipment') <a href="{{ route('mechanical.equipment.create') }}">Add your first equipment</a> @endcan</p>
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
                <small class="text-muted">Showing {{ $equipment->firstItem() ?? 0 }} to {{ $equipment->lastItem() ?? 0 }} of {{ $equipment->total() ?? 0 }} equipment</small>
            </div>
            <div>
                {{ $equipment->links() }}
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Equipment Status Overview</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="p-3 border rounded bg-light">
                            <h3 class="text-success">{{ \App\Models\Equipment::where('status', 'operational')->count() }}</h3>
                            <div class="small text-muted">Operational</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 border rounded bg-light">
                            <h3 class="text-warning">{{ \App\Models\Equipment::where('status', 'maintenance')->count() }}</h3>
                            <div class="small text-muted">Maintenance</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 border rounded bg-light">
                            <h3 class="text-danger">{{ \App\Models\Equipment::where('status', 'repair')->count() }}</h3>
                            <div class="small text-muted">Repair</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 border rounded bg-light">
                            <h3 class="text-secondary">{{ \App\Models\Equipment::where('status', 'retired')->count() }}</h3>
                            <div class="small text-muted">Retired</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Upcoming Maintenance</h5>
                <a href="{{ route('mechanical.maintenance.schedule') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse(\App\Models\MaintenanceSchedule::with('equipment')->where('next_maintenance_date', '<=', now()->addDays(7))->orderBy('next_maintenance_date')->take(5)->get() as $maintenance)
                    <a href="{{ route('mechanical.equipment.show', $maintenance->equipment) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">{{ $maintenance->equipment->name }}</h6>
                            <small class="{{ $maintenance->next_maintenance_date->isPast() ? 'text-danger' : 'text-warning' }}">
                                {{ $maintenance->next_maintenance_date->isPast() ? 'Overdue' : 'Due ' . $maintenance->next_maintenance_date->diffForHumans() }}
                            </small>
                        </div>
                        <p class="mb-1">{{ $maintenance->title }}</p>
                        <small class="text-muted">{{ $maintenance->frequency }} maintenance</small>
                    </a>
                    @empty
                    <div class="list-group-item text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-calendar-check fa-2x mb-3"></i>
                            <p>No upcoming maintenance scheduled</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection