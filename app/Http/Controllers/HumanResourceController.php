<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\EmployeeDetail;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HumanResourceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-employees')->only(['employees', 'showEmployee']);
        $this->middleware('permission:manage-payroll')->only(['payrolls', 'createPayroll', 'processPayroll']);
        $this->middleware('permission:manage-leave')->only(['leaveRequests', 'approveLeave', 'rejectLeave']);
    }

    // Employee Management
    public function employees()
    {
        $employees = User::with('department', 'employeeDetail')->paginate(15);
        return view('hr.employees.index', compact('employees'));
    }

    public function showEmployee(User $user)
    {
        $user->load('department', 'employeeDetail', 'leaveRequests');
        return view('hr.employees.show', compact('user'));
    }

    public function createEmployee(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'department_id' => 'required|exists:departments,id',
            'position' => 'required|string|max:100',
            'hire_date' => 'required|date',
            'employee_id' => 'required|string|max:50|unique:employee_details,employee_id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'department_id' => $validated['department_id'],
                'status' => 'active',
            ]);
            
            // Assign roles
            $user->roles()->attach($validated['roles']);
            
            // Create employee details
            EmployeeDetail::create([
                'user_id' => $user->id,
                'employee_id' => $validated['employee_id'],
                'position' => $validated['position'],
                'hire_date' => $validated['hire_date'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'emergency_contact' => $validated['emergency_contact'],
                'emergency_phone' => $validated['emergency_phone'],
            ]);
            
            DB::commit();
            
            return redirect()->route('hr.employees.show', $user)
                ->with('success', 'Employee created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()]);
        }
    }

    public function updateEmployee(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'department_id' => 'required|exists:departments,id',
            'position' => 'required|string|max:100',
            'hire_date' => 'required|date',
            'employee_id' => 'required|string|max:50|unique:employee_details,employee_id,' . $user->employeeDetail->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Update user
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'department_id' => $validated['department_id'],
                'status' => $validated['status'],
            ]);
            
            // Update roles
            $user->roles()->sync($validated['roles']);
            
            // Update employee details
            $user->employeeDetail->update([
                'employee_id' => $validated['employee_id'],
                'position' => $validated['position'],
                'hire_date' => $validated['hire_date'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'emergency_contact' => $validated['emergency_contact'],
                'emergency_phone' => $validated['emergency_phone'],
            ]);
            
            DB::commit();
            
            return redirect()->route('hr.employees.show', $user)
                ->with('success', 'Employee updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update employee: ' . $e->getMessage()]);
        }
    }

    // Leave Management
    public function leaveRequests()
    {
        $pendingRequests = LeaveRequest::where('status', 'pending')->count();
        $approvedRequests = LeaveRequest::where('status', 'approved')->count();
        $rejectedRequests = LeaveRequest::where('status', 'rejected')->count();
        
        $requests = LeaveRequest::with('user', 'leaveType')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('hr.leave.index', compact(
            'requests',
            'pendingRequests',
            'approvedRequests',
            'rejectedRequests'
        ));
    }

    public function createLeaveRequest(Request $request)
    {
        $leaveTypes = LeaveType::where('active', true)->get();
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'leave_type_id' => 'required|exists:leave_types,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'required|string',
                'contact_during_leave' => 'nullable|string|max:100',
            ]);
            
            // Calculate number of days
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $days = $startDate->diffInDays($endDate) + 1;
            
            LeaveRequest::create([
                'employee_id' => Auth::id(),
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'days' => $days,
                'reason' => $validated['reason'],
                'contact_during_leave' => $validated['contact_during_leave'] ?? null,
                'status' => 'pending',
            ]);
            
            return redirect()->route('hr.leave.my-requests')
                ->with('success', 'Leave request submitted successfully');
        }
        
        return view('hr.leave.create', compact('leaveTypes'));
    }

    public function myLeaveRequests()
    {
        $leaveRequests = LeaveRequest::with('leaveType')
            ->where('employee_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('hr.leave.my-requests', compact('leaveRequests'));
    }

    public function approveLeave(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending leave requests can be approved']);
        }
        
        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        
        // Notify employee (could be implemented with Laravel notifications)
        
        return redirect()->route('hr.leave.index')
            ->with('success', 'Leave request approved successfully');
    }

    public function rejectLeave(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending leave requests can be rejected']);
        }
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);
        
        $leaveRequest->update([
            'status' => 'rejected',
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);
        
        // Notify employee (could be implemented with Laravel notifications)
        
        return redirect()->route('hr.leave.index')
            ->with('success', 'Leave request rejected successfully');
    }

    // Payroll Management
    public function payrolls()
    {
        $payrolls = Payroll::orderBy('pay_period_end', 'desc')->paginate(15);
        return view('hr.payroll.index', compact('payrolls'));
    }

    public function createPayroll()
    {
        $employees = User::whereHas('employeeDetail')->where('status', 'active')->get();
        return view('hr.payroll.create', compact('employees'));
    }

    public function processPayroll(Request $request)
    {
        $validated = $request->validate([
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date|after:pay_period_start',
            'payment_date' => 'required|date|after_or_equal:pay_period_end',
            'employees' => 'required|array',
            'employees.*.user_id' => 'required|exists:users,id',
            'employees.*.base_salary' => 'required|numeric|min:0',
            'employees.*.overtime_hours' => 'nullable|numeric|min:0',
            'employees.*.overtime_rate' => 'nullable|numeric|min:0',
            'employees.*.bonus' => 'nullable|numeric|min:0',
            'employees.*.deductions' => 'nullable|numeric|min:0',
            'employees.*.notes' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Create payroll
            $payroll = Payroll::create([
                'pay_period_start' => $validated['pay_period_start'],
                'pay_period_end' => $validated['pay_period_end'],
                'payment_date' => $validated['payment_date'],
                'created_by' => Auth::id(),
                'status' => 'draft',
            ]);
            
            // Add payroll items
            $totalAmount = 0;
            foreach ($validated['employees'] as $employee) {
                $baseSalary = $employee['base_salary'];
                $overtimePay = ($employee['overtime_hours'] ?? 0) * ($employee['overtime_rate'] ?? 0);
                $bonus = $employee['bonus'] ?? 0;
                $deductions = $employee['deductions'] ?? 0;
                $netPay = $baseSalary + $overtimePay + $bonus - $deductions;
                
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'user_id' => $employee['user_id'],
                    'base_salary' => $baseSalary,
                    'overtime_hours' => $employee['overtime_hours'] ?? 0,
                    'overtime_rate' => $employee['overtime_rate'] ?? 0,
                    'overtime_pay' => $overtimePay,
                    'bonus' => $bonus,
                    'deductions' => $deductions,
                    'net_pay' => $netPay,
                    'notes' => $employee['notes'] ?? null,
                ]);
                
                $totalAmount += $netPay;
            }
            
            // Update payroll total
            $payroll->update([
                'total_amount' => $totalAmount,
            ]);
            
            DB::commit();
            
            return redirect()->route('hr.payroll.show', $payroll)
                ->with('success', 'Payroll created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create payroll: ' . $e->getMessage()]);
        }
    }

    public function showPayroll(Payroll $payroll)
    {
        $payroll->load('items.user', 'createdBy');
        
        return view('hr.payroll.show', compact('payroll'));
    }

    public function approvePayroll(Payroll $payroll)
    {
        if ($payroll->status !== 'processed') {
            return back()->withErrors(['error' => 'Only processed payrolls can be approved']);
        }
        
        $payroll->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        
        return redirect()->route('hr.payroll.show', $payroll)
            ->with('success', 'Payroll approved successfully');
    }

    public function generatePayslips(Payroll $payroll)
    {
        if (!in_array($payroll->status, ['processed', 'approved'])) {
            return back()->withErrors(['error' => 'Payroll must be processed or approved to generate payslips']);
        }
        
        // Generate PDF payslips using a package like barryvdh/laravel-dompdf
        // This is a placeholder for the actual implementation
        
        return view('hr.payroll.payslips', compact('payroll'));
    }

    public function finalizePayroll(Payroll $payroll)
    {
        if ($payroll->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft payrolls can be finalized']);
        }
        
        $payroll->update([
            'status' => 'finalized',
            'finalized_at' => now(),
            'finalized_by' => Auth::id(),
        ]);
        
        return redirect()->route('hr.payroll.show', $payroll)
            ->with('success', 'Payroll finalized successfully');
    }

    public function createLeaveType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:leave_types,name',
            'days_allowed' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'active' => 'required|boolean',
            'color' => 'nullable|string|max:7',
        ]);
        
        LeaveType::create($validated);
        
        return redirect()->route('hr.leave-types.index')
            ->with('success', 'Leave type created successfully');
    }

    public function updateLeaveType(Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:leave_types,name,' . $leaveType->id,
            'days_allowed' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'active' => 'required|boolean',
            'color' => 'nullable|string|max:7',
        ]);
        
        $leaveType->update($validated);
        
        return redirect()->route('hr.leave-types.index')
            ->with('success', 'Leave type updated successfully');
    }

    public function submitLeaveRequest(Request $request)
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'contact_details' => 'nullable|string',
        ]);
        
        // Calculate number of days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $days = $startDate->diffInDays($endDate) + 1; // Include both start and end days
        
        // Check if employee has enough leave days
        $leaveType = LeaveType::find($validated['leave_type_id']);
        $usedDays = LeaveRequest::where('user_id', Auth::id())
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'approved')
            ->whereYear('start_date', date('Y'))
            ->sum('days');
            
        if ($usedDays + $days > $leaveType->days_allowed) {
            return back()->withErrors(['error' => "You don't have enough {$leaveType->name} days available. You have used {$usedDays} out of {$leaveType->days_allowed} days."]);
        }
        
        // Create leave request
        LeaveRequest::create([
            'user_id' => Auth::id(),
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days' => $days,
            'reason' => $validated['reason'],
            'contact_details' => $validated['contact_details'],
            'status' => 'pending',
        ]);
        
        return redirect()->route('hr.my-leave-requests')
            ->with('success', 'Leave request submitted successfully');
    }

    public function cancelLeaveRequest(LeaveRequest $leaveRequest)
    {
        // Ensure the user can only cancel their own leave requests
        if ($leaveRequest->user_id !== Auth::id()) {
            return back()->withErrors(['error' => 'You can only cancel your own leave requests']);
        }
        
        // Ensure the leave request is still pending
        if ($leaveRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'You can only cancel pending leave requests']);
        }
        
        $leaveRequest->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        
        return redirect()->route('hr.my-leave-requests')
            ->with('success', 'Leave request cancelled successfully');
    }

    public function departments()
    {
        $departments = Department::withCount('users')->paginate(15);
        return view('hr.departments.index', compact('departments'));
    }

    public function createDepartment(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:departments,name',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
        ]);
        
        Department::create($validated);
        
        return redirect()->route('hr.departments.index')
            ->with('success', 'Department created successfully');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:departments,name,' . $department->id,
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
        ]);
        
        $department->update($validated);
        
        return redirect()->route('hr.departments.index')
            ->with('success', 'Department updated successfully');
    }

    public function showDepartment(Department $department)
    {
        $department->load('manager', 'users');
        
        return view('hr.departments.show', compact('department'));
    }
}


