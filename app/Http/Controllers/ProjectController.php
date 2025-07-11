<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use App\Models\Task;
use App\Models\ProjectMember;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-projects')->only(['index', 'show']);
        $this->middleware('permission:create-projects')->only(['create', 'store']);
        $this->middleware('permission:edit-projects')->only(['edit', 'update']);
        $this->middleware('permission:manage-project-members')->only(['addMember', 'removeMember']);
        $this->middleware('permission:manage-project-tasks')->only(['createTask', 'updateTask']);
    }

    public function index()
    {
        $activeProjects = Project::where('status', 'active')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $onHoldProjects = Project::where('status', 'on-hold')->count();
        
        $projects = Project::with('client', 'manager')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('projects.index', compact(
            'projects', 
            'activeProjects', 
            'completedProjects', 
            'onHoldProjects'
        ));
    }

    public function create()
    {
        $clients = Client::where('status', 'active')->get();
        $managers = User::role('project-manager')->get();
        
        return view('projects.create', compact('clients', 'managers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'manager_id' => 'required|exists:users,id',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'expected_end_date' => 'required|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:planning,active,on-hold,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);
        
        $project = Project::create($validated);
        
        // Add creator as a project member automatically
        $project->members()->create([
            'user_id' => Auth::id(),
            'role' => 'creator',
            'added_by' => Auth::id(),
        ]);
        
        // Add manager as a project member if different from creator
        if (Auth::id() != $validated['manager_id']) {
            $project->members()->create([
                'user_id' => $validated['manager_id'],
                'role' => 'manager',
                'added_by' => Auth::id(),
            ]);
        }
        
        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully');
    }

    public function show(Project $project)
    {
        $project->load('client', 'manager', 'members.user', 'tasks');
        
        // Calculate project statistics
        $totalTasks = $project->tasks->count();
        $completedTasks = $project->tasks->where('status', 'completed')->count();
        $taskCompletionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
        
        // Calculate days remaining
        $daysRemaining = now()->diffInDays($project->expected_end_date, false);
        $isOverdue = $daysRemaining < 0 && $project->status !== 'completed';
        
        return view('projects.show', compact(
            'project', 
            'totalTasks', 
            'completedTasks', 
            'taskCompletionRate', 
            'daysRemaining', 
            'isOverdue'
        ));
    }

    public function edit(Project $project)
    {
        $clients = Client::where('status', 'active')->get();
        $managers = User::role('project-manager')->get();
        
        return view('projects.edit', compact('project', 'clients', 'managers'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'manager_id' => 'required|exists:users,id',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'expected_end_date' => 'required|date|after_or_equal:start_date',
            'actual_end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:planning,active,on-hold,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
        ]);
        
        // If status is changed to completed and actual_end_date is not set, set it to today
        if ($validated['status'] === 'completed' && empty($validated['actual_end_date'])) {
            $validated['actual_end_date'] = now();
        }
        
        $project->update($validated);
        
        // If manager changed, update project members
        if ($project->manager_id != $validated['manager_id']) {
            // Remove old manager from members if exists
            $project->members()->where('user_id', $project->manager_id)
                ->where('role', 'manager')
                ->delete();
                
            // Add new manager as member
            $project->members()->create([
                'user_id' => $validated['manager_id'],
                'role' => 'manager',
                'added_by' => Auth::id(),
            ]);
        }
        
        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully');
    }

    public function addMember(Request $request, Project $project)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);
        
        // Check if user is already a member
        $existingMember = $project->members()->where('user_id', $validated['user_id'])->first();
        
        if ($existingMember) {
            return back()->withErrors(['error' => 'User is already a member of this project']);
        }
        
        $project->members()->create([
            'user_id' => $validated['user_id'],
            'role' => $validated['role'],
            'notes' => $validated['notes'] ?? null,
            'added_by' => Auth::id(),
        ]);
        
        return redirect()->route('projects.show', $project)
            ->with('success', 'Member added to project successfully');
    }

    public function removeMember(Project $project, ProjectMember $member)
    {
        // Prevent removing the project manager
        if ($member->user_id == $project->manager_id && $member->role == 'manager') {
            return back()->withErrors(['error' => 'Cannot remove the project manager. Assign a new manager first.']);
        }
        
        $member->delete();
        
        return redirect()->route('projects.show', $project)
            ->with('success', 'Member removed from project successfully');
    }

    public function createTask(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);
        
        $task = $project->tasks()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'due_date' => $validated['due_date'],
            'priority' => $validated['priority'],
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);
        
        return redirect()->route('projects.tasks.show', ['project' => $project, 'task' => $task])
            ->with('success', 'Task created successfully');
    }

    public function showTask(Project $project, Task $task)
    {
        $task->load('assignedTo', 'createdBy', 'comments.user');
        
        return view('projects.tasks.show', compact('project', 'task'));
    }

    public function updateTask(Request $request, Project $project, Task $task)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in-progress,review,completed,cancelled',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
        ]);
        
        // If status is changed to completed, record completion date
        if ($task->status !== 'completed' && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }
        
        $task->update($validated);
        
        return redirect()->route('projects.tasks.show', ['project' => $project, 'task' => $task])
            ->with('success', 'Task updated successfully');
    }

    public function addTaskComment(Request $request, Project $project, Task $task)
    {
        $validated = $request->validate([
            'comment' => 'required|string',
        ]);
        
        $task->comments()->create([
            'user_id' => Auth::id(),
            'comment' => $validated['comment'],
        ]);
        
        return redirect()->route('projects.tasks.show', ['project' => $project, 'task' => $task])
            ->with('success', 'Comment added successfully');
    }

    public function uploadDocument(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'document' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string',
            'document_type' => 'required|string|max:50',
        ]);
        
        $path = $request->file('document')->store('project-documents');
        
        $project->documents()->create([
            'title' => $validated['title'],
            'file_path' => $path,
            'file_name' => $request->file('document')->getClientOriginalName(),
            'file_size' => $request->file('document')->getSize(),
            'file_type' => $request->file('document')->getMimeType(),
            'description' => $validated['description'] ?? null,
            'document_type' => $validated['document_type'],
            'uploaded_by' => Auth::id(),
        ]);
        
        return redirect()->route('projects.show', $project)
            ->with('success', 'Document uploaded successfully');
    }

    public function downloadDocument(Project $project, ProjectDocument $document)
    {
        // Check if user has access to this project
        if (!$project->members()->where('user_id', Auth::id())->exists()) {
            abort(403, 'You do not have permission to access this document');
        }
        
        return Storage::download($document->file_path, $document->file_name);
    }

    public function myProjects()
    {
        $projects = Project::whereHas('members', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->with('client', 'manager')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('projects.my-projects', compact('projects'));
    }

    public function myTasks()
    {
        $pendingTasks = Task::where('assigned_to', Auth::id())
            ->whereIn('status', ['pending', 'in-progress', 'review'])
            ->with('project')
            ->orderBy('due_date')
            ->get();
            
        $completedTasks = Task::where('assigned_to', Auth::id())
            ->where('status', 'completed')
            ->with('project')
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get();
            
        return view('projects.my-tasks', compact('pendingTasks', 'completedTasks'));
    }

    public function projectTimeline(Project $project)
    {
        $project->load('tasks', 'members.user');
        
        // Prepare timeline data
        $timelineEvents = collect();
        
        // Add project start and end dates
        $timelineEvents->push([
            'date' => $project->start_date,
            'type' => 'project',
            'event' => 'Project Started',
            'description' => 'Project officially started',
        ]);
        
        if ($project->actual_end_date) {
            $timelineEvents->push([
                'date' => $project->actual_end_date,
                'type' => 'project',
                'event' => 'Project Completed',
                'description' => 'Project officially completed',
            ]);
        } elseif ($project->expected_end_date) {
            $timelineEvents->push([
                'date' => $project->expected_end_date,
                'type' => 'project',
                'event' => 'Expected Completion',
                'description' => 'Project expected completion date',
            ]);
        }
        
        // Add task dates
        foreach ($project->tasks as $task) {
            $timelineEvents->push([
                'date' => $task->created_at,
                'type' => 'task',
                'event' => 'Task Created: ' . $task->name,
                'description' => $task->description,
                'task' => $task,
            ]);
            
            if ($task->status === 'completed' && $task->completed_at) {
                $timelineEvents->push([
                    'date' => $task->completed_at,
                    'type' => 'task',
                    'event' => 'Task Completed: ' . $task->name,
                    'description' => $task->description,
                    'task' => $task,
                ]);
            }
        }
        
        // Sort timeline events by date
        $timelineEvents = $timelineEvents->sortBy('date');
        
        return view('projects.timeline', compact('project', 'timelineEvents'));
    }

    public function projectBudget(Project $project)
    {
        $project->load('expenses', 'payments');
        
        // Calculate budget metrics
        $totalBudget = $project->budget ?? 0;
        $totalExpenses = $project->expenses->sum('amount');
        $totalPayments = $project->payments->sum('amount');
        $budgetRemaining = $totalBudget - $totalExpenses;
        $budgetUtilizationPercent = $totalBudget > 0 ? ($totalExpenses / $totalBudget) * 100 : 0;
        
        // Group expenses by category
        $expensesByCategory = $project->expenses
            ->groupBy('category')
            ->map(function ($items) {
                return $items->sum('amount');
            });
            
        return view('projects.budget', compact(
            'project',
            'totalBudget',
            'totalExpenses',
            'totalPayments',
            'budgetRemaining',
            'budgetUtilizationPercent',
            'expensesByCategory'
        ));
    }

    public function addExpense(Request $request, Project $project)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'required|string|max:50',
            'receipt' => 'nullable|file|max:5120', // 5MB max
            'notes' => 'nullable|string',
        ]);
        
        $expenseData = [
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'category' => $validated['category'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ];
        
        // Handle receipt upload if provided
        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('project-receipts');
            $expenseData['receipt_path'] = $path;
            $expenseData['receipt_filename'] = $request->file('receipt')->getClientOriginalName();
        }
        
        $project->expenses()->create($expenseData);
        
        return redirect()->route('projects.budget', $project)
            ->with('success', 'Expense added successfully');
    }

    public function addPayment(Request $request, Project $project)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);
        
        $project->payments()->create([
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => Auth::id(),
        ]);
        
        return redirect()->route('projects.budget', $project)
            ->with('success', 'Payment recorded successfully');
    }

    public function generateReport(Project $project)
    {
        $project->load('client', 'manager', 'members.user', 'tasks', 'expenses', 'payments', 'documents');
        
        // Calculate project metrics
        $totalTasks = $project->tasks->count();
        $completedTasks = $project->tasks->where('status', 'completed')->count();
        $taskCompletionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
        
        $totalBudget = $project->budget ?? 0;
        $totalExpenses = $project->expenses->sum('amount');
        $budgetUtilization = $totalBudget > 0 ? ($totalExpenses / $totalBudget) * 100 : 0;
        
        // Group tasks by status
        $tasksByStatus = $project->tasks->groupBy('status');
        
        // Group expenses by category
        $expensesByCategory = $project->expenses->groupBy('category');
        
        return view('projects.report', compact(
            'project',
            'totalTasks',
            'completedTasks',
            'taskCompletionRate',
            'totalBudget',
            'totalExpenses',
            'budgetUtilization',
            'tasksByStatus',
            'expensesByCategory'
        ));
    }

    public function destroy(Project $project)
    {
        // Check if project can be deleted (e.g., no active tasks, etc.)
        $activeTasks = $project->tasks()->whereNotIn('status', ['completed', 'cancelled'])->count();
        
        if ($activeTasks > 0) {
            return back()->withErrors(['error' => 'Cannot delete project with active tasks. Please complete or cancel all tasks first.']);
        }
        
        try {
            DB::beginTransaction();
            
            // Delete related records
            $project->members()->delete();
            $project->tasks()->delete();
            $project->documents()->delete();
            $project->expenses()->delete();
            $project->payments()->delete();
            
            // Delete project
            $project->delete();
            
            DB::commit();
            
            return redirect()->route('projects.index')
                ->with('success', 'Project deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete project: ' . $e->getMessage()]);
        }
    }
}

