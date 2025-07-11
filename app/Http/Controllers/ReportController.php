<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Project;
use App\Models\Task;
use App\Models\PurchaseOrder;
use App\Models\Transaction;
use App\Models\RentalAgreement;
use App\Models\InventoryItem;
use App\Models\Equipment;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use PDF;
use Laravel\Pennant\Feature;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-reports');
    }

    public function index()
    {
        // Basic reports available to all users
        $reportTypes = [
            'rental-summary' => 'Rental Summary Report',
            'inventory-status' => 'Inventory Status Report',
            'customer-activity' => 'Customer Activity Report',
        ];
        
        // Advanced reports only available to users with the feature flag
        if (Feature::active('advanced-reporting')) {
            $reportTypes = array_merge($reportTypes, [
                'revenue-analysis' => 'Revenue Analysis Report',
                'equipment-utilization' => 'Equipment Utilization Report',
                'customer-segmentation' => 'Customer Segmentation Analysis',
                'maintenance-cost' => 'Maintenance Cost Analysis',
                'procurement-efficiency' => 'Procurement Efficiency Report',
                'staff-performance' => 'Staff Performance Metrics',
            ]);
        }
        
        return view('reports.index', compact('reportTypes'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel,csv',
        ]);
        
        // Check if advanced report is requested but feature flag is not enabled
        $advancedReports = [
            'revenue-analysis',
            'equipment-utilization',
            'customer-segmentation',
            'maintenance-cost',
            'procurement-efficiency',
            'staff-performance',
        ];
        
        if (in_array($validated['report_type'], $advancedReports) && !Feature::active('advanced-reporting')) {
            return back()->withErrors(['error' => 'Advanced reporting is not available for your account.']);
        }
        
        // Generate the requested report
        switch ($validated['report_type']) {
            case 'rental-summary':
                return $this->generateRentalSummaryReport($validated);
            case 'inventory-status':
                return $this->generateInventoryStatusReport($validated);
            case 'customer-activity':
                return $this->generateCustomerActivityReport($validated);
            case 'revenue-analysis':
                return $this->generateRevenueAnalysisReport($validated);
            case 'equipment-utilization':
                return $this->generateEquipmentUtilizationReport($validated);
            case 'customer-segmentation':
                return $this->generateCustomerSegmentationReport($validated);
            case 'maintenance-cost':
                return $this->generateMaintenanceCostReport($validated);
            case 'procurement-efficiency':
                return $this->generateProcurementEfficiencyReport($validated);
            case 'staff-performance':
                return $this->generateStaffPerformanceReport($validated);
            default:
                return back()->withErrors(['error' => 'Invalid report type selected.']);
        }
    }

    public function projectReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $status = $request->input('status');
        $clientId = $request->input('client_id');
        
        $query = Project::with('client', 'manager');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($clientId) {
            $query->where('client_id', $clientId);
        }
        
        $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        $projects = $query->get();
        
        // Calculate statistics
        $totalProjects = $projects->count();
        $totalBudget = $projects->sum('budget');
        $avgDuration = $projects->avg(function ($project) {
            $start = Carbon::parse($project->start_date);
            $end = $project->end_date ? Carbon::parse($project->end_date) : Carbon::now();
            return $start->diffInDays($end);
        });
        
        // Group by status
        $projectsByStatus = $projects->groupBy('status');
        
        // Group by client
        $projectsByClient = $projects->groupBy('client.name');
        
        $clients = Client::orderBy('name')->get();
        
        return view('reports.projects', compact(
            'projects',
            'startDate',
            'endDate',
            'status',
            'clientId',
            'totalProjects',
            'totalBudget',
            'avgDuration',
            'projectsByStatus',
            'projectsByClient',
            'clients'
        ));
    }

    public function financialReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $category = $request->input('category');
        $departmentId = $request->input('department_id');
        
        $query = Transaction::with('budget.department');
        
        if ($category) {
            $query->where('category', $category);
        }
        
        if ($departmentId) {
            $query->whereHas('budget', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        
        $query->whereBetween('transaction_date', [$startDate, $endDate]);
        
        $transactions = $query->get();
        
        // Calculate statistics
        $totalIncome = $transactions->where('amount', '>', 0)->sum('amount');
        $totalExpenses = abs($transactions->where('amount', '<', 0)->sum('amount'));
        $netProfit = $totalIncome - $totalExpenses;
        
        // Group by category
        $expensesByCategory = $transactions->where('amount', '<', 0)
            ->groupBy('category')
            ->map(function ($items) {
                return abs($items->sum('amount'));
            });
        
        // Group by department
        $expensesByDepartment = $transactions->where('amount', '<', 0)
            ->groupBy('budget.department.name')
            ->map(function ($items) {
                return abs($items->sum('amount'));
            });
        
        // Monthly breakdown
        $monthlyData = [];
        foreach ($transactions as $transaction) {
            $month = Carbon::parse($transaction->transaction_date)->format('Y-m');
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'income' => 0,
                    'expenses' => 0,
                ];
            }
            
            if ($transaction->amount > 0) {
                $monthlyData[$month]['income'] += $transaction->amount;
            } else {
                $monthlyData[$month]['expenses'] += abs($transaction->amount);
            }
        }
        
        $departments = Department::orderBy('name')->get();
        $categories = Transaction::select('category')->distinct()->pluck('category');
        
        return view('reports.financial', compact(
            'transactions',
            'startDate',
            'endDate',
            'category',
            'departmentId',
            'totalIncome',
            'totalExpenses',
            'netProfit',
            'expensesByCategory',
            'expensesByDepartment',
            'monthlyData',
            'departments',
            'categories'
        ));
    }

    public function inventoryReport(Request $request)
    {
        $category = $request->input('category');
        $lowStock = $request->input('low_stock', false);
        $supplierId = $request->input('supplier_id');
        
        $query = InventoryItem::with('category', 'supplier');
        
        if ($category) {
            $query->whereHas('category', function($q) use ($category) {
                $q->where('id', $category);
            });
        }
        
        if ($lowStock) {
            $query->where('quantity', '<=', DB::raw('reorder_level'));
        }
        
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        
        $items = $query->get();
        
        // Calculate statistics
        $totalItems = $items->count();
        $totalValue = $items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
        $lowStockItems = $items->where('quantity', '<=', DB::raw('reorder_level'))->count();
        
        // Group by category
        $itemsByCategory = $items->groupBy('category.name');
        
        // Value by category
        $valueByCategory = [];
        foreach ($itemsByCategory as $categoryName => $categoryItems) {
            $valueByCategory[$categoryName] = $categoryItems->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });
        }
        
        $categories = InventoryCategory::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        
        return view('reports.inventory', compact(
            'items',
            'category',
            'lowStock',
            'supplierId',
            'totalItems',
            'totalValue',
            'lowStockItems',
            'itemsByCategory',
            'valueByCategory',
            'categories',
            'suppliers'
        ));
    }

    public function equipmentReport(Request $request)
    {
        $category = $request->input('category');
        $status = $request->input('status');
        
        $query = Equipment::with('category', 'maintenanceSchedules', 'maintenanceLogs');
        
        if ($category) {
            $query->where('category_id', $category);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $equipment = $query->get();
        
        // Calculate statistics
        $totalEquipment = $equipment->count();
        $operationalEquipment = $equipment->where('status', 'operational')->count();
        $maintenanceEquipment = $equipment->where('status', 'maintenance')->count();
        $repairEquipment = $equipment->where('status', 'repair')->count();
        
        // Group by category
        $equipmentByCategory = $equipment->groupBy('category.name');
        
        // Maintenance costs
        $maintenanceCosts = 0;
        foreach ($equipment as $item) {
            $maintenanceCosts += $item->maintenanceLogs->sum('cost');
        }
        
        // Equipment requiring maintenance soon
        $needsMaintenance = [];
        foreach ($equipment as $item) {
            $nextMaintenance = $item->maintenanceSchedules->sortBy('next_maintenance_date')->first();
            if ($nextMaintenance && Carbon::parse($nextMaintenance->next_maintenance_date)->lte(Carbon::now()->addDays(30))) {
                $needsMaintenance[] = [
                    'equipment' => $item,
                    'date' => $nextMaintenance->next_maintenance_date,
                ];
            }
        }
        
        $categories = EquipmentCategory::orderBy('name')->get();
        
        return view('reports.equipment', compact(
            'equipment',
            'category',
            'status',
            'totalEquipment',
            'operationalEquipment',
            'maintenanceEquipment',
            'repairEquipment',
            'equipmentByCategory',
            'maintenanceCosts',
            'needsMaintenance',
            'categories'
        ));
    }

    public function clientReport(Request $request)
    {
        $status = $request->input('status');
        $startDate = $request->input('start_date', Carbon::now()->subYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        
        $query = Client::withCount('projects');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        $clients = $query->get();
        
        // Calculate statistics
        $totalClients = $clients->count();
        $activeClients = $clients->where('status', 'active')->count();
        $inactiveClients = $clients->where('status', 'inactive')->count();
        $potentialClients = $clients->where('status', 'potential')->count();
        
        // Projects per client
        $projectsPerClient = $clients->pluck('projects_count', 'name');
        
        // Top clients by project value
        $topClients = [];
        foreach ($clients as $client) {
            $projectValue = $client->projects->sum('budget');
            $topClients[$client->name] = $projectValue;
        }
        arsort($topClients);
        $topClients = array_slice($topClients, 0, 5, true);
        
        return view('reports.clients', compact(
            'clients',
            'status',
            'startDate',
            'endDate',
            'totalClients',
            'activeClients',
            'inactiveClients',
            'potentialClients',
            'projectsPerClient',
            'topClients'
        ));
    }

    public function supplierReport(Request $request)
    {
        $status = $request->input('status');
        $startDate = $request->input('start_date', Carbon::now()->subYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        
        $query = Supplier::withCount('purchaseOrders');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $suppliers = $query->get();
        
        // Calculate statistics
        $totalSuppliers = $suppliers->count();
        $activeSuppliers = $suppliers->where('status', 'active')->count();
        $inactiveSuppliers = $suppliers->where('status', 'inactive')->count();
        
        // Purchase orders per supplier
        $ordersPerSupplier = $suppliers->pluck('purchase_orders_count', 'name');
        
        // Top suppliers by purchase value
        $topSuppliers = [];
        foreach ($suppliers as $supplier) {
            $purchaseValue = $supplier->purchaseOrders
                ->where('status', 'completed')
                ->whereBetween('order_date', [$startDate, $endDate])
                ->sum('total_amount');
            $topSuppliers[$supplier->name] = $purchaseValue;
        }
        arsort($topSuppliers);
        $topSuppliers = array_slice($topSuppliers, 0, 5, true);
        
        return view('reports.suppliers', compact(
            'suppliers',
            'status',
            'startDate',
            'endDate',
            'totalSuppliers',
            'activeSuppliers',
            'inactiveSuppliers',
            'ordersPerSupplier',
            'topSuppliers'
        ));
    }

    public function userReport(Request $request)
    {
        $role = $request->input('role');
        $departmentId = $request->input('department_id');
        $status = $request->input('status');
        
        $query = User::with('roles', 'department');
        
        if ($role) {
            $query->whereHas('roles', function($q) use ($role) {
                $q->where('name', $role);
            });
        }
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $users = $query->get();
        
        // Calculate statistics
        $totalUsers = $users->count();
        $activeUsers = $users->where('status', 'active')->count();
        $inactiveUsers = $users->where('status', 'inactive')->count();
        
        // Users by department
        $usersByDepartment = $users->groupBy('department.name');
        
        // Users by role
        $usersByRole = [];
        foreach ($users as $user) {
            foreach ($user->roles as $role) {
                if (!isset($usersByRole[$role->name])) {
                    $usersByRole[$role->name] = 0;
                }
                $usersByRole[$role->name]++;
            }
        }
        
        $departments = Department::orderBy('name')->get();
        $roles = DB::table('roles')->pluck('name');
        
        return view('reports.users', compact(
            'users',
            'role',
            'departmentId',
            'status',
            'totalUsers',
            'activeUsers',
            'inactiveUsers',
            'usersByDepartment',
            'usersByRole',
            'departments',
            'roles'
        ));
    }

    public function taskReport(Request $request)
    {
        $status = $request->input('status');
        $projectId = $request->input('project_id');
        $assignedTo = $request->input('assigned_to');
        $startDate = $request->input('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        
        $query = Task::with('project', 'assignedTo');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        
        if ($assignedTo) {
            $query->where('assigned_to', $assignedTo);
        }
        
        $query->whereBetween('due_date', [$startDate, $endDate]);
        
        $tasks = $query->get();
        
        // Calculate statistics
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $pendingTasks = $tasks->where('status', 'pending')->count();
        $inProgressTasks = $tasks->where('status', 'in-progress')->count();
        
        // Completion rate
        $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
        
        // Tasks by project
        $tasksByProject = $tasks->groupBy('project.name');
        
        // Tasks by assignee
        $tasksByAssignee = $tasks->groupBy('assignedTo.name');
        
        // Overdue tasks
        $overdueTasks = $tasks->filter(function($task) {
            return $task->status != 'completed' && Carbon::parse($task->due_date)->lt(Carbon::now());
        });
        
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        
        return view('reports.tasks', compact(
            'tasks',
            'status',
            'projectId',
            'assignedTo',
            'startDate',
            'endDate',
            'totalTasks',
            'completedTasks',
            'pendingTasks',
            'inProgressTasks',
            'completionRate',
            'tasksByProject',
            'tasksByAssignee',
            'overdueTasks',
            'projects',
            'users'
        ));
    }

    public function purchaseOrderReport(Request $request)
    {
        $status = $request->input('status');
        $supplierId = $request->input('supplier_id');
        $startDate = $request->input('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        
        $query = PurchaseOrder::with('supplier', 'createdBy');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        
        $query->whereBetween('order_date', [$startDate, $endDate]);
        
        $purchaseOrders = $query->get();
        
        // Calculate statistics
        $totalOrders = $purchaseOrders->count();
        $pendingOrders = $purchaseOrders->where('status', 'pending')->count();
        $approvedOrders = $purchaseOrders->where('status', 'approved')->count();
        $completedOrders = $purchaseOrders->where('status', 'completed')->count();
        
        // Total amount
        $totalAmount = $purchaseOrders->where('status', 'completed')->sum('total_amount');
        
        // Orders by supplier
        $ordersBySupplier = $purchaseOrders->groupBy('supplier.name');
        
        // Monthly spending
        $monthlySpending = [];
        foreach ($purchaseOrders->where('status', 'completed') as $order) {
            $month = Carbon::parse($order->order_date)->format('Y-m');
            if (!isset($monthlySpending[$month])) {
                $monthlySpending[$month] = 0;
            }
            $monthlySpending[$month] += $order->total_amount;
        }
        
        $suppliers = Supplier::orderBy('name')->get();
        
        return view('reports.purchase_orders', compact(
            'purchaseOrders',
            'status',
            'supplierId',
            'startDate',
            'endDate',
            'totalOrders',
            'pendingOrders',
            'approvedOrders',
            'completedOrders',
            'totalAmount',
            'ordersBySupplier',
            'monthlySpending',
            'suppliers'
        ));
    }

    public function rentalReport(Request $request)
    {
        $status = $request->input('status');
        $customerId = $request->input('customer_id');
        $startDate = $request->input('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        
        $query = RentalAgreement::with('customer', 'items.rentalItem', 'payments');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        
        $query->whereBetween('start_date', [$startDate, $endDate]);
        
        $agreements = $query->get();
        
        // Calculate statistics
        $totalAgreements = $agreements->count();
        $activeAgreements = $agreements->where('status', 'active')->count();
        $completedAgreements = $agreements->where('status', 'completed')->count();
        $overdueAgreements = $agreements->filter(function($agreement) {
            return $agreement->status == 'active' && Carbon::parse($agreement->expected_end_date)->lt(Carbon::now());
        })->count();
        
        // Total revenue
        $totalRevenue = $agreements->sum(function($agreement) {
            return $agreement->payments->sum('amount');
        });
        
        // Agreements by status
        $agreementsByStatus = $agreements->groupBy('status');
        
        // Agreements by month
        $agreementsByMonth = [];
        foreach ($agreements as $agreement) {
            $month = Carbon::parse($agreement->start_date)->format('Y-m');
            if (!isset($agreementsByMonth[$month])) {
                $agreementsByMonth[$month] = 0;
            }
            $agreementsByMonth[$month]++;
        }
        
        // Most rented items
        $rentedItems = collect();
        foreach ($agreements as $agreement) {
            foreach ($agreement->items as $item) {
                $rentedItems->push($item->rentalItem);
            }
        }
        $mostRentedItems = $rentedItems->groupBy('name')
            ->map(function($items) {
                return $items->count();
            })
            ->sortDesc()
            ->take(5);
        
        return view('reports.rentals', compact(
            'agreements',
            'totalAgreements',
            'activeAgreements',
            'completedAgreements',
            'overdueAgreements',
            'totalRevenue',
            'agreementsByStatus',
            'agreementsByMonth',
            'mostRentedItems'
        ));
    }

    public function exportReport(Request $request, $reportType)
    {
        // Reuse the existing report methods but format for export
        switch ($reportType) {
            case 'projects':
                $data = $this->projectReport($request);
                break;
            case 'financial':
                $data = $this->financialReport($request);
                break;
            case 'inventory':
                $data = $this->inventoryReport($request);
                break;
            case 'equipment':
                $data = $this->equipmentReport($request);
                break;
            case 'clients':
                $data = $this->clientReport($request);
                break;
            case 'suppliers':
                $data = $this->supplierReport($request);
                break;
            case 'users':
                $data = $this->userReport($request);
                break;
            case 'tasks':
                $data = $this->taskReport($request);
                break;
            case 'purchase_orders':
                $data = $this->purchaseOrderReport($request);
                break;
            case 'rentals':
                $data = $this->rentalReport($request);
                break;
            default:
                return back()->withErrors(['error' => 'Invalid report type.']);
        }
        
        // Generate PDF using the data
        $pdf = PDF::loadView('reports.export.' . $reportType, $data);
        
        return $pdf->download($reportType . '_report_' . date('Y-m-d') . '.pdf');
    }

    public function generateCustomReport(Request $request)
    {
        $validated = $request->validate([
            'report_name' => 'required|string|max:255',
            'data_sources' => 'required|array',
            'data_sources.*' => 'in:projects,tasks,clients,suppliers,inventory,equipment,purchase_orders,rentals,transactions,users',
            'date_range' => 'required|in:all,custom,this_month,last_month,this_quarter,last_quarter,this_year,last_year',
            'start_date' => 'required_if:date_range,custom|nullable|date',
            'end_date' => 'required_if:date_range,custom|nullable|date|after_or_equal:start_date',
            'filters' => 'nullable|array',
            'group_by' => 'nullable|array',
            'sort_by' => 'nullable|string',
            'sort_direction' => 'nullable|in:asc,desc',
        ]);
        
        // Process date range
        $startDate = null;
        $endDate = null;
        
        switch ($validated['date_range']) {
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'this_quarter':
                $startDate = Carbon::now()->startOfQuarter();
                $endDate = Carbon::now()->endOfQuarter();
                break;
            case 'last_quarter':
                $startDate = Carbon::now()->subQuarter()->startOfQuarter();
                $endDate = Carbon::now()->subQuarter()->endOfQuarter();
                break;
            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            case 'last_year':
                $startDate = Carbon::now()->subYear()->startOfYear();
                $endDate = Carbon::now()->subYear()->endOfYear();
                break;
            case 'custom':
                $startDate = Carbon::parse($validated['start_date']);
                $endDate = Carbon::parse($validated['end_date']);
                break;
        }
        
        // Build report data based on selected data sources
        $reportData = [];
        
        foreach ($validated['data_sources'] as $source) {
            switch ($source) {
                case 'projects':
                    $query = Project::query();
                    if ($startDate && $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate]);
                    }
                    $reportData['projects'] = $query->get();
                    break;
                case 'tasks':
                    $query = Task::query();
                    if ($startDate && $endDate) {
                        $query->whereBetween('due_date', [$startDate, $endDate]);
                    }
                    $reportData['tasks'] = $query->get();
                    break;
                case 'clients':
                    $reportData['clients'] = Client::all();
                    break;
                case 'suppliers':
                    $reportData['suppliers'] = Supplier::all();
                    break;
                case 'inventory':
                    $reportData['inventory'] = InventoryItem::all();
                    break;
                case 'equipment':
                    $reportData['equipment'] = Equipment::all();
                    break;
                case 'purchase_orders':
                    $query = PurchaseOrder::query();
                    if ($startDate && $endDate) {
                        $query->whereBetween('order_date', [$startDate, $endDate]);
                    }
                    $reportData['purchase_orders'] = $query->get();
                    break;
                case 'rentals':
                    $query = RentalAgreement::query();
                    if ($startDate && $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate]);
                    }
                    $reportData['rentals'] = $query->get();
                    break;
                case 'transactions':
                    $query = Transaction::query();
                    if ($startDate && $endDate) {
                        $query->whereBetween('transaction_date', [$startDate, $endDate]);
                    }
                    $reportData['transactions'] = $query->get();
                    break;
                case 'users':
                    $reportData['users'] = User::all();
                    break;
            }
        }
        
        return view('reports.custom', [
            'reportName' => $validated['report_name'],
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}







