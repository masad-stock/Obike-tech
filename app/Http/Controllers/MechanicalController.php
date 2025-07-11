<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceLog;
use App\Models\EquipmentAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MechanicalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-equipment')->only(['equipment', 'showEquipment']);
        $this->middleware('permission:manage-equipment')->only(['createEquipment', 'updateEquipment']);
        $this->middleware('permission:manage-maintenance')->only(['createMaintenanceLog', 'scheduleMaintenances']);
    }

    public function equipment()
    {
        $equipment = Equipment::with('category')->paginate(15);
        return view('mechanical.equipment.index', compact('equipment'));
    }

    public function showEquipment(Equipment $equipment)
    {
        $equipment->load('category', 'maintenanceSchedules', 'maintenanceLogs', 'assignments');
        return view('mechanical.equipment.show', compact('equipment'));
    }

    public function createEquipment(Request $request)
    {
        $categories = EquipmentCategory::all();
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'model_number' => 'nullable|string|max:100',
                'serial_number' => 'nullable|string|max:100',
                'equipment_category_id' => 'required|exists:equipment_categories,id',
                'purchase_date' => 'nullable|date',
                'purchase_cost' => 'nullable|numeric|min:0',
                'manufacturer' => 'nullable|string|max:100',
                'supplier' => 'nullable|string|max:100',
                'warranty_expiry' => 'nullable|date',
                'status' => 'required|in:operational,maintenance,repair,retired',
                'specifications' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            $equipment = Equipment::create($validated);

            return redirect()->route('mechanical.equipment.show', $equipment)
                ->with('success', 'Equipment created successfully');
        }

        return view('mechanical.equipment.create', compact('categories'));
    }

    public function updateEquipment(Request $request, Equipment $equipment)
    {
        $categories = EquipmentCategory::all();
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'model_number' => 'nullable|string|max:100',
                'serial_number' => 'nullable|string|max:100',
                'equipment_category_id' => 'required|exists:equipment_categories,id',
                'purchase_date' => 'nullable|date',
                'purchase_cost' => 'nullable|numeric|min:0',
                'manufacturer' => 'nullable|string|max:100',
                'supplier' => 'nullable|string|max:100',
                'warranty_expiry' => 'nullable|date',
                'status' => 'required|in:operational,maintenance,repair,retired',
                'specifications' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            $equipment->update($validated);

            return redirect()->route('mechanical.equipment.show', $equipment)
                ->with('success', 'Equipment updated successfully');
        }

        return view('mechanical.equipment.edit', compact('equipment', 'categories'));
    }

    public function createMaintenanceSchedule(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,biannually,annually,custom',
            'frequency_custom_days' => 'nullable|required_if:frequency,custom|integer|min:1',
            'next_maintenance_date' => 'required|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $equipment->maintenanceSchedules()->create($validated);

        return redirect()->route('mechanical.equipment.show', $equipment)
            ->with('success', 'Maintenance schedule created successfully.');
    }

    public function createMaintenanceLog(Request $request, Equipment $equipment)
    {
        // If this is for a specific maintenance schedule
        $scheduleId = $request->query('schedule');
        $schedule = null;
        
        if ($scheduleId) {
            $schedule = MaintenanceSchedule::findOrFail($scheduleId);
        }
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'maintenance_date' => 'required|date',
                'maintenance_type' => 'required|in:preventive,corrective,inspection',
                'maintenance_schedule_id' => 'nullable|exists:maintenance_schedules,id',
                'description' => 'required|string',
                'parts_replaced' => 'nullable|string',
                'cost' => 'nullable|numeric|min:0',
                'downtime_hours' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            $validated['performed_by'] = Auth::id();
            $validated['equipment_id'] = $equipment->id;

            $maintenanceLog = MaintenanceLog::create($validated);

            // Update the maintenance schedule if this is for a scheduled maintenance
            if (!empty($validated['maintenance_schedule_id'])) {
                $schedule = MaintenanceSchedule::find($validated['maintenance_schedule_id']);
                
                // Calculate next maintenance date based on frequency
                $nextDate = $this->calculateNextMaintenanceDate(
                    $schedule->frequency, 
                    Carbon::parse($validated['maintenance_date'])
                );
                
                $schedule->update([
                    'last_maintenance_date' => $validated['maintenance_date'],
                    'next_maintenance_date' => $nextDate,
                ]);
            }

            // Update equipment status if needed
            if ($request->has('update_status') && $request->update_status) {
                $equipment->update(['status' => 'operational']);
            }

            return redirect()->route('mechanical.equipment.show', $equipment)
                ->with('success', 'Maintenance log created successfully');
        }

        $maintenanceSchedules = $equipment->maintenanceSchedules;
        return view('mechanical.maintenance.create-log', compact('equipment', 'maintenanceSchedules', 'schedule'));
    }

    public function assignEquipment(Request $request, Equipment $equipment)
    {
        if ($equipment->status !== 'operational') {
            return back()->withErrors(['error' => 'Only operational equipment can be assigned']);
        }
        
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'assignment_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after_or_equal:assignment_date',
            'purpose' => 'required|string',
            'notes' => 'nullable|string',
        ]);
        
        $equipment->assignments()->create([
            'assigned_by' => Auth::id(),
            'assigned_to' => $validated['assigned_to'],
            'assignment_date' => $validated['assignment_date'],
            'expected_return_date' => $validated['expected_return_date'] ?? null,
            'purpose' => $validated['purpose'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'active',
        ]);
        
        return redirect()->route('mechanical.equipment.show', $equipment)
            ->with('success', 'Equipment assigned successfully');
    }

    public function returnEquipment(Request $request, EquipmentAssignment $assignment)
    {
        if ($assignment->status !== 'active') {
            return back()->withErrors(['error' => 'Only active assignments can be returned']);
        }
        
        $validated = $request->validate([
            'return_date' => 'required|date',
            'condition_on_return' => 'required|in:excellent,good,fair,poor,damaged',
            'notes' => 'nullable|string',
        ]);
        
        $assignment->update([
            'return_date' => $validated['return_date'],
            'condition_on_return' => $validated['condition_on_return'],
            'return_notes' => $validated['notes'] ?? null,
            'status' => 'returned',
        ]);
        
        // Update equipment status based on return condition
        if (in_array($validated['condition_on_return'], ['poor', 'damaged'])) {
            $assignment->equipment->update(['status' => 'repair']);
        }
        
        return redirect()->route('mechanical.equipment.show', $assignment->equipment)
            ->with('success', 'Equipment returned successfully');
    }

    public function equipmentCategories()
    {
        $categories = EquipmentCategory::withCount('equipment')->paginate(15);
        return view('mechanical.categories.index', compact('categories'));
    }

    public function createEquipmentCategory(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:equipment_categories',
                'description' => 'nullable|string',
            ]);
            
            EquipmentCategory::create($validated);
            
            return redirect()->route('mechanical.categories.index')
                ->with('success', 'Equipment category created successfully');
        }
        
        return view('mechanical.categories.create');
    }

    public function pendingMaintenances()
    {
        $today = Carbon::today();
        $upcomingMaintenances = MaintenanceSchedule::with('equipment')
            ->where('next_maintenance_date', '<=', $today->copy()->addDays(7))
            ->orderBy('next_maintenance_date')
            ->paginate(15);
            
        $overdueMaintenances = MaintenanceSchedule::with('equipment')
            ->where('next_maintenance_date', '<', $today)
            ->count();
            
        return view('mechanical.maintenance.pending', compact('upcomingMaintenances', 'overdueMaintenances'));
    }

    private function calculateNextMaintenanceDate(MaintenanceSchedule $schedule, $lastMaintenanceDate)
    {
        $lastDate = Carbon::parse($lastMaintenanceDate);
        
        switch ($schedule->frequency) {
            case 'daily':
                return $lastDate->addDay();
            case 'weekly':
                return $lastDate->addWeek();
            case 'monthly':
                return $lastDate->addMonth();
            case 'quarterly':
                return $lastDate->addMonths(3);
            case 'biannually':
                return $lastDate->addMonths(6);
            case 'annually':
                return $lastDate->addYear();
            case 'custom':
                return $lastDate->addDays($schedule->frequency_custom_days);
            default:
                return $lastDate->addMonth();
        }
    }

    public function createEquipment(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'serial_number' => 'required|string|max:100|unique:equipment,serial_number',
            'category_id' => 'required|exists:equipment_categories,id',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'status' => 'required|in:operational,maintenance,repair,retired',
            'notes' => 'nullable|string',
        ]);
        
        $equipment = Equipment::create($validated);
        
        return redirect()->route('mechanical.equipment.show', $equipment)
            ->with('success', 'Equipment created successfully.');
    }

    public function updateEquipment(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'serial_number' => 'required|string|max:100|unique:equipment,serial_number,' . $equipment->id,
            'category_id' => 'required|exists:equipment_categories,id',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'status' => 'required|in:operational,maintenance,repair,retired',
            'notes' => 'nullable|string',
        ]);
        
        $equipment->update($validated);
        
        return redirect()->route('mechanical.equipment.show', $equipment)
            ->with('success', 'Equipment updated successfully.');
    }

    public function createMaintenanceLog(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'maintenance_date' => 'required|date',
            'maintenance_type' => 'required|in:routine,repair,inspection',
            'description' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'performed_by' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
        // Add equipment ID to validated data
        $validated['equipment_id'] = $equipment->id;
        
        // Create maintenance log
        MaintenanceLog::create($validated);
        
        // Update equipment status if needed
        if ($request->has('update_status') && $request->update_status) {
            $equipment->update(['status' => 'operational']);
        }
        
        return redirect()->route('mechanical.equipment.show', $equipment)
            ->with('success', 'Maintenance log added successfully.');
    }

    public function scheduleMaintenances(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'maintenance_type' => 'required|in:routine,inspection,calibration',
            'frequency_days' => 'required|integer|min:1',
            'description' => 'required|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        // Add equipment ID and next maintenance date
        $validated['equipment_id'] = $equipment->id;
        $validated['next_maintenance_date'] = Carbon::now()->addDays($validated['frequency_days']);
        
        // Create maintenance schedule
        MaintenanceSchedule::create($validated);
        
        return redirect()->route('mechanical.equipment.show', $equipment)
            ->with('success', 'Maintenance schedule created successfully.');
    }

    public function dashboard()
    {
        $totalEquipment = Equipment::count();
        $operationalEquipment = Equipment::where('status', 'operational')->count();
        $maintenanceEquipment = Equipment::where('status', 'maintenance')->count();
        $repairEquipment = Equipment::where('status', 'repair')->count();
        
        $upcomingMaintenances = MaintenanceSchedule::with('equipment')
            ->where('next_maintenance_date', '<=', Carbon::today()->addDays(7))
            ->orderBy('next_maintenance_date')
            ->limit(5)
            ->get();
            
        $recentMaintenanceLogs = MaintenanceLog::with('equipment')
            ->orderBy('maintenance_date', 'desc')
            ->limit(5)
            ->get();
            
        $equipmentByCategory = Equipment::select('category_id', DB::raw('count(*) as count'))
            ->groupBy('category_id')
            ->with('category')
            ->get();
            
        $maintenanceCostsByMonth = MaintenanceLog::whereYear('maintenance_date', date('Y'))
            ->select(DB::raw('MONTH(maintenance_date) as month'), DB::raw('SUM(cost) as total'))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
            
        return view('mechanical.dashboard', compact(
            'totalEquipment',
            'operationalEquipment',
            'maintenanceEquipment',
            'repairEquipment',
            'upcomingMaintenances',
            'recentMaintenanceLogs',
            'equipmentByCategory',
            'maintenanceCostsByMonth'
        ));
    }

    public function maintenanceHistory()
    {
        $maintenanceLogs = MaintenanceLog::with(['equipment', 'performer', 'maintenanceSchedule'])
            ->orderBy('maintenance_date', 'desc')
            ->paginate(15);
            
        // Get statistics for charts
        $monthlyLogs = MaintenanceLog::selectRaw('MONTH(maintenance_date) as month, COUNT(*) as count')
            ->whereYear('maintenance_date', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
        $costData = MaintenanceLog::selectRaw('MONTH(maintenance_date) as month, SUM(cost) as total')
            ->whereYear('maintenance_date', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
        $maintenanceTypes = MaintenanceLog::selectRaw('maintenance_type, COUNT(*) as count')
            ->groupBy('maintenance_type')
            ->get();
            
        // Prepare chart data
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyData = array_fill(0, 12, 0);
        $costMonthlyData = array_fill(0, 12, 0);
        
        foreach ($monthlyLogs as $log) {
            $monthlyData[$log->month - 1] = $log->count;
        }
        
        foreach ($costData as $cost) {
            $costMonthlyData[$cost->month - 1] = $cost->total;
        }
        
        $typeLabels = $maintenanceTypes->pluck('maintenance_type')->map(function($type) {
            return ucfirst($type);
        })->toArray();
        
        $typeData = $maintenanceTypes->pluck('count')->toArray();
        
        return view('mechanical.maintenance.history', compact(
            'maintenanceLogs', 
            'months', 
            'monthlyData', 
            'costMonthlyData',
            'typeLabels',
            'typeData'
        ));
    }

    public function showMaintenanceLog(MaintenanceLog $maintenanceLog)
    {
        $maintenanceLog->load(['equipment', 'performer', 'maintenanceSchedule']);
        return view('mechanical.maintenance.show-log', compact('maintenanceLog'));
    }

    public function editMaintenanceLog(Request $request, MaintenanceLog $maintenanceLog)
    {
        $equipment = $maintenanceLog->equipment;
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'maintenance_date' => 'required|date',
                'maintenance_type' => 'required|in:preventive,corrective,inspection',
                'description' => 'required|string',
                'parts_replaced' => 'nullable|string',
                'cost' => 'nullable|numeric|min:0',
                'downtime_hours' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            $maintenanceLog->update($validated);

            return redirect()->route('mechanical.maintenance.show-log', $maintenanceLog)
                ->with('success', 'Maintenance log updated successfully');
        }
        
        $maintenanceSchedules = $equipment->maintenanceSchedules;
        return view('mechanical.maintenance.edit-log', compact('maintenanceLog', 'equipment', 'maintenanceSchedules'));
    }

    public function deleteMaintenanceLog(MaintenanceLog $maintenanceLog)
    {
        $equipment = $maintenanceLog->equipment;
        $maintenanceLog->delete();
        
        return redirect()->route('mechanical.equipment.show', $equipment)
            ->with('success', 'Maintenance log deleted successfully');
    }

    /**
     * Calculate the next maintenance date based on frequency
     */
    private function calculateNextMaintenanceDate($frequency, $currentDate)
    {
        switch ($frequency) {
            case 'daily':
                return $currentDate->addDay();
            case 'weekly':
                return $currentDate->addWeek();
            case 'monthly':
                return $currentDate->addMonth();
            case 'quarterly':
                return $currentDate->addMonths(3);
            case 'biannually':
                return $currentDate->addMonths(6);
            case 'annually':
                return $currentDate->addYear();
            case 'custom':
                // For custom frequency, we would need to store the custom days
                // For now, default to 30 days
                return $currentDate->addDays(30);
            default:
                return $currentDate->addMonth();
        }
    }
}


