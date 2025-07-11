@extends('layouts.app')

@section('title', 'Rentals Dashboard')

@section('header', 'Rentals Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-0">Rentals Dashboard</h1>
            <p class="text-muted">Overview of rental operations</p>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Basic stats cards - visible to all users -->
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Active Rentals</h5>
                    <h2 class="display-4">{{ $activeAgreements }}</h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="{{ route('rentals.agreements.index', ['status' => 'active']) }}" class="text-white text-decoration-none">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Overdue Rentals</h5>
                    <h2 class="display-4">{{ $overdueAgreements }}</h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="{{ route('rentals.agreements.index', ['overdue' => 'true']) }}" class="text-white text-decoration-none">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Available Items</h5>
                    <h2 class="display-4">{{ $availableItems }}</h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="{{ route('rentals.items.index', ['status' => 'available']) }}" class="text-white text-decoration-none">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Inventory</h5>
                    <h2 class="display-4">{{ $totalItems }}</h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="{{ route('rentals.items.index') }}" class="text-white text-decoration-none">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    @feature('enhanced-rental-dashboard')
    <!-- Enhanced dashboard features - only visible to users with the feature flag enabled -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Agreements</h5>
                    <a href="{{ route('rentals.agreements.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentAgreements as $agreement)
                                <tr>
                                    <td>{{ $agreement['id'] }}</td>
                                    <td>{{ $agreement['customer_name'] }}</td>
                                    <td>{{ $agreement['start_date'] }}</td>
                                    <td>{{ $agreement['expected_end_date'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ $agreement['status'] == 'active' ? 'success' : ($agreement['status'] == 'completed' ? 'primary' : 'secondary') }}">
                                            {{ ucfirst($agreement['status']) }}
                                        </span>
                                    </td>
                                    <td>${{ number_format($agreement['total_amount'], 2) }}</td>
                                </tr>
                                @endforeach
                                @if(count($recentAgreements) == 0)
                                <tr>
                                    <td colspan="6" class="text-center py-3">No recent agreements found</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Upcoming Returns</h5>
                    <a href="{{ route('rentals.agreements.index', ['status' => 'active']) }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Return Date</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upcomingReturns as $return)
                                <tr>
                                    <td>{{ $return['id'] }}</td>
                                    <td>{{ $return['customer_name'] }}</td>
                                    <td>{{ $return['expected_end_date'] }}</td>
                                    <td>{{ $return['items_count'] }}</td>
                                    <td>${{ number_format($return['total_amount'], 2) }}</td>
                                </tr>
                                @endforeach
                                @if(count($upcomingReturns) == 0)
                                <tr>
                                    <td colspan="5" class="text-center py-3">No upcoming returns found</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Revenue ({{ date('Y') }})</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const monthlyData = Array(12).fill(0);
            
            @foreach($monthlyRevenue as $month => $revenue)
                monthlyData[{{ $month - 1 }}] = {{ $revenue }};
            @endforeach
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: monthNames,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: monthlyData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
    @endfeature
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('rentals.agreements.create') }}" class="btn btn-primary w-100 py-3">
                                <i class="fas fa-file-contract fa-2x mb-2"></i><br>
                                New Rental Agreement
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('rentals.items.create') }}" class="btn btn-success w-100 py-3">
                                <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                Add New Item
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('rentals.customers.create') }}" class="btn btn-info w-100 py-3">
                                <i class="fas fa-user-plus fa-2x mb-2"></i><br>
                                Add New Customer
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('rentals.reports.index') }}" class="btn btn-secondary w-100 py-3">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                                Generate Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

