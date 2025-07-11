<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <i class="fas fa-building me-2"></i>{{ config('app.name', 'Obike Tech System') }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            @auth
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                
                @can('view-projects')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="projectsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-project-diagram me-1"></i>Projects
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="projectsDropdown">
                        <li><a class="dropdown-item" href="{{ route('projects.index') }}">All Projects</a></li>
                        @can('create-projects')
                        <li><a class="dropdown-item" href="{{ route('projects.create') }}">New Project</a></li>
                        @endcan
                        <li><a class="dropdown-item" href="{{ route('tasks.index') }}">Tasks</a></li>
                    </ul>
                </li>
                @endcan
                
                @can('view-clients')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="clientsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-users me-1"></i>Clients
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="clientsDropdown">
                        <li><a class="dropdown-item" href="{{ route('clients.index') }}">All Clients</a></li>
                        @can('create-clients')
                        <li><a class="dropdown-item" href="{{ route('clients.create') }}">New Client</a></li>
                        @endcan
                    </ul>
                </li>
                @endcan
                
                @can('view-employees')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="hrDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-tie me-1"></i>HR
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="hrDropdown">
                        <li><a class="dropdown-item" href="{{ route('hr.employees') }}">Employees</a></li>
                        @can('manage-leave')
                        <li><a class="dropdown-item" href="{{ route('hr.leave.requests') }}">Leave Requests</a></li>
                        @endcan
                        @can('manage-payroll')
                        <li><a class="dropdown-item" href="{{ route('hr.payrolls') }}">Payroll</a></li>
                        @endcan
                    </ul>
                </li>
                @endcan
                
                @can('view-inventory')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="inventoryDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-boxes me-1"></i>Inventory
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="inventoryDropdown">
                        <li><a class="dropdown-item" href="{{ route('inventory.index') }}">All Items</a></li>
                        @can('manage-inventory')
                        <li><a class="dropdown-item" href="{{ route('inventory.create') }}">Add Item</a></li>
                        @endcan
                    </ul>
                </li>
                @endcan
                
                @can('view-equipment')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="mechanicalDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-tools me-1"></i>Equipment
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="mechanicalDropdown">
                        <li><a class="dropdown-item" href="{{ route('mechanical.equipment') }}">All Equipment</a></li>
                        @can('manage-maintenance')
                        <li><a class="dropdown-item" href="{{ route('mechanical.maintenance.schedule') }}">Maintenance Schedule</a></li>
                        @endcan
                    </ul>
                </li>
                @endcan
                
                @can('view-rentals')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="rentalsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-truck-loading me-1"></i>Rentals
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="rentalsDropdown">
                        <li><a class="dropdown-item" href="{{ route('rentals.index') }}">Agreements</a></li>
                        @can('manage-rentals')
                        <li><a class="dropdown-item" href="{{ route('rentals.create') }}">New Agreement</a></li>
                        @endcan
                    </ul>
                </li>
                @endcan
                
                @can('view-purchase-orders')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="procurementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-shopping-cart me-1"></i>Procurement
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="procurementDropdown">
                        <li><a class="dropdown-item" href="{{ route('procurement.purchase-orders.index') }}">Purchase Orders</a></li>
                        @can('create-purchase-orders')
                        <li><a class="dropdown-item" href="{{ route('procurement.purchase-orders.create') }}">New Purchase Order</a></li>
                        @endcan
                        <li><a class="dropdown-item" href="{{ route('procurement.suppliers.index') }}">Suppliers</a></li>
                    </ul>
                </li>
                @endcan
                
                @can('view-reports')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-bar me-1"></i>Reports
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                        <li><a class="dropdown-item" href="{{ route('reports.projects') }}">Projects</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.financial') }}">Financial</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.inventory') }}">Inventory</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.equipment') }}">Equipment</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.clients') }}">Clients</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.suppliers') }}">Suppliers</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.users') }}">Users</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.tasks') }}">Tasks</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.purchase-orders') }}">Purchase Orders</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.rentals') }}">Rentals</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('reports.custom') }}">Custom Report</a></li>
                    </ul>
                </li>
                @endcan
                
                @can('manage-users')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog me-1"></i>Admin
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="{{ route('users.index') }}">Users</a></li>
                        <li><a class="dropdown-item" href="{{ route('roles.index') }}">Roles & Permissions</a></li>
                        <li><a class="dropdown-item" href="{{ route('departments.index') }}">Departments</a></li>
                        @can('manage-features')
                        <li><a class="dropdown-item" href="{{ route('features.index') }}">Feature Flags</a></li>
                        @endcan
                        <li><a class="dropdown-item" href="{{ route('settings.index') }}">System Settings</a></li>
                    </ul>
                </li>
                @endcan
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i>{{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}">My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
            @endauth
        </div>
    </div>
</nav>
