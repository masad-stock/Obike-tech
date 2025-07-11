@extends('layouts.app')

@section('title', 'Manage Feature: ' . $featureInfo['name'])

@section('header', 'Manage Feature: ' . $featureInfo['name'])

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $featureInfo['name'] }}</h5>
                    <a href="{{ route('features.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Features
                    </a>
                </div>
                <div class="card-body">
                    <p>{{ $featureInfo['description'] }}</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This interface allows you to override the default feature flag behavior. 
                        By default, features are assigned based on rules defined in the <code>FeatureServiceProvider</code>.
                    </div>
                    
                    <div class="mt-4">
                        <h6>Bulk Actions</h6>
                        <form action="{{ route('features.update', $feature) }}" method="POST" class="row g-3">
                            @csrf
                            @method('PUT')
                            
                            <div class="col-md-6">
                                <button type="submit" name="action" value="enable-all" class="btn btn-success me-2">
                                    <i class="fas fa-check-circle me-1"></i>Enable for All Users
                                </button>
                                <button type="submit" name="action" value="disable-all" class="btn btn-danger">
                                    <i class="fas fa-times-circle me-1"></i>Disable for All Users
                                </button>
                            </div>
                            
                            <div class="col-md-6 text-end">
                                <a href="{{ route('features.reset', $feature) }}" 
                                   class="btn btn-warning"
                                   onclick="return confirm('Are you sure you want to reset this feature? This will remove all overrides and revert to the default behavior.')">
                                    <i class="fas fa-undo me-1"></i>Reset to Default
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">User Management</h5>
                    <div class="input-group" style="max-width: 300px;">
                        <input type="text" id="userSearch" class="form-control" placeholder="Search users...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <form action="{{ route('features.update', $feature) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="usersTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th width="150">Feature Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input user-checkbox" type="checkbox" 
                                                       name="selected_users[]" value="{{ $user->id }}">
                                            </div>
                                        </td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input feature-toggle" 
                                                       type="checkbox" 
                                                       data-user-id="{{ $user->id }}" 
                                                       data-feature="{{ $feature }}"
                                                       {{ $user->has_feature ? 'checked' : '' }}>
                                                <label class="form-check-label">
                                                    {{ $user->has_feature ? 'Enabled' : 'Disabled' }}
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="p-3 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span id="selectedCount">0</span> users selected
                                </div>
                                <div>
                                    <button type="submit" name="action" value="enable-selected" class="btn btn-success me-2">
                                        <i class="fas fa-check-circle me-1"></i>Enable for Selected
                                    </button>
                                    <button type="submit" name="action" value="disable-selected" class="btn btn-danger">
                                        <i class="fas fa-times-circle me-1"></i>Disable for Selected
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select all checkbox functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        const selectedCountElement = document.getElementById('selectedCount');
        
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelectedCount();
        });
        
        userCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedCount();
                
                // Update "select all" checkbox
                const allChecked = Array.from(userCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(userCheckboxes).some(cb => cb.checked);
                
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            });
        });
        
        function updateSelectedCount() {
            const selectedCount = Array.from(userCheckboxes).filter(cb => cb.checked).length;
            selectedCountElement.textContent = selectedCount;
        }
        
        // Feature toggle functionality
        const featureToggles = document.querySelectorAll('.feature-toggle');
        
        featureToggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const userId = this.dataset.userId;
                const feature = this.dataset.feature;
                const status = this.checked;
                const label = this.nextElementSibling;
                
                // Update label text
                label.textContent = status ? 'Enabled' : 'Disabled';
                
                // Send AJAX request to update feature status
                fetch('{{ route('features.toggle-user') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        feature: feature,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // Revert toggle if there was an error
                        this.checked = !status;
                        label.textContent = !status ? 'Enabled' : 'Disabled';
                        alert('Error updating feature status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert toggle if there was an error
                    this.checked = !status;
                    label.textContent = !status ? 'Enabled' : 'Disabled';
                    alert('Error updating feature status');
                });
            });
        });
        
        // User search functionality
        const userSearchInput = document.getElementById('userSearch');
        const usersTable = document.getElementById('usersTable');
        const tableRows = usersTable.querySelectorAll('tbody tr');
        
        userSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            tableRows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
</script>
@endpush
@endsection