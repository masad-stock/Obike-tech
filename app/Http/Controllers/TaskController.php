<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\TaskComment;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-tasks')->only(['index', 'show']);
        $this->middleware('permission:create-tasks')->only(['create', 'store']);
        $this->middleware('permission:edit-tasks')->only(['edit', 'update']);
        $this->middleware('permission:delete-tasks')->only(['destroy']);
    }

    public function index()
    {
        $pendingTasks = Task::where('status', 'pending')->count();
        $inProgressTasks = Task::where('status', 'in-progress')->count();
        $completedTasks = Task::where('status', 'completed')->count();
        
        $tasks = Task::with('project', 'assignedTo')
            ->orderBy('due_date')
            ->paginate(20);
            
        return view('tasks.index', compact(
            'tasks', 
            'pendingTasks', 
            'inProgressTasks', 
            'completedTasks'
        ));
    }

    public function create(Request $request)
    {
        $projects = Project::where('status', '!=', 'completed')->orderBy('name')->get();
        
        // Pre-select project if provided in query string
        $selectedProject = null;
        if ($request->has('project_id')) {
            $selectedProject = Project::find($request->project_id);
        }
        
        return view('tasks.create', compact('projects', 'selectedProject'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,in-progress,review,completed,cancelled',
        ]);
        
        // Add created_by field
        $validated['created_by'] = Auth::id();
        
        // If status is completed, set completed_at
        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }
        
        $task = Task::create($validated);
        
        // Handle attachments if provided
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task-attachments');
                
                $task->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getMimeType(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }
        
        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task created successfully');
    }

    public function show(Task $task)
    {
        $task->load('project', 'assignedTo', 'createdBy', 'comments.user', 'attachments');
        
        // Get project members for reassignment
        $projectMembers = $task->project->members()->with('user')->get()->pluck('user');
        
        return view('tasks.show', compact('task', 'projectMembers'));
    }

    public function edit(Task $task)
    {
        $projects = Project::where('status', '!=', 'completed')->orderBy('name')->get();
        $projectMembers = $task->project->members()->with('user')->get()->pluck('user');
        
        return view('tasks.edit', compact('task', 'projects', 'projectMembers'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,in-progress,review,completed,cancelled',
        ]);
        
        // If status is changed to completed, set completed_at
        if ($task->status !== 'completed' && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }
        
        // If status is changed from completed, clear completed_at
        if ($task->status === 'completed' && $validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }
        
        $task->update($validated);
        
        // Handle attachments if provided
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task-attachments');
                
                $task->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getMimeType(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }
        
        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task updated successfully');
    }

    public function destroy(Task $task)
    {
        // Delete attachments
        foreach ($task->attachments as $attachment) {
            Storage::delete($attachment->file_path);
        }
        
        // Delete task and related records
        $task->attachments()->delete();
        $task->comments()->delete();
        $task->delete();
        
        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully');
    }

    public function addComment(Request $request, Task $task)
    {
        $validated = $request->validate([
            'comment' => 'required|string',
        ]);
        
        $task->comments()->create([
            'user_id' => Auth::id(),
            'comment' => $validated['comment'],
        ]);
        
        return redirect()->route('tasks.show', $task)
            ->with('success', 'Comment added successfully');
    }

    public function deleteComment(Task $task, TaskComment $comment)
    {
        // Only allow comment creator or admin to delete
        if ($comment->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return back()->withErrors(['error' => 'You do not have permission to delete this comment']);
        }
        
        $comment->delete();
        
        return redirect()->route('tasks.show', $task)
            ->with('success', 'Comment deleted successfully');
    }

    public function downloadAttachment(Task $task, TaskAttachment $attachment)
    {
        // Check if user has access to this task
        if (!Auth::user()->can('view-tasks') && 
            $task->assigned_to !== Auth::id() && 
            $task->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to access this attachment');
        }
        
        return Storage::download($attachment->file_path, $attachment->file_name);
    }

    public function deleteAttachment(Task $task, TaskAttachment $attachment)
    {
        // Only allow attachment uploader or admin to delete
        if ($attachment->uploaded_by !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return back()->withErrors(['error' => 'You do not have permission to delete this attachment']);
        }
        
        // Delete file from storage
        Storage::delete($attachment->file_path);
        
        // Delete record
        $attachment->delete();
        
        return redirect()->route('tasks.show', $task)
            ->with('success', 'Attachment deleted successfully');
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
            
        return view('tasks.my-tasks', compact('pendingTasks', 'completedTasks'));
    }

    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in-progress,review,completed,cancelled',
            'comment' => 'nullable|string',
        ]);
        
        // Update status
        $oldStatus = $task->status;
        
        // If status is changed to completed, set completed_at
        if ($oldStatus !== 'completed' && $validated['status'] === 'completed') {
            $task->completed_at = now();
        }
        
        // If status is changed from completed, clear completed_at
        if ($oldStatus === 'completed' && $validated['status'] !== 'completed') {
            $task->completed_at = null;
        }
        
        $task->status = $validated['status'];
        $task->save();
        
        // Add comment if provided
        if (!empty($validated['comment'])) {
            $task->comments()->create([
                'user_id' => Auth::id(),
                'comment' => $validated['comment'],
            ]);
        }
        
        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task status updated successfully');
    }

    public function logTime(Request $request, Task $task)
    {
        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.1',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);
        
        $task->timeEntries()->create([
            'user_id' => Auth::id(),
            'hours' => $validated['hours'],
            'date' => $validated['date'],
            'description' => $validated['description'] ?? null,
        ]);
        
        // Update actual hours
        $task->actual_hours = $task->timeEntries()->sum('hours');
        $task->save();
        
        return redirect()->route('tasks.show', $task)
            ->with('success', 'Time logged successfully');
    }
}