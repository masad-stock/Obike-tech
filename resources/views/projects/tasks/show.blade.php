@extends('layouts.app')

@section('title', $task->name)

@section('header', 'Task Details')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="d-flex align-items-center">
            <h1 class="h3 mb-0">{{ $task->name }}</h1>
            <span class="badge bg-{{ $task->status == 'completed' ? 'success' : ($task->status == 'in-progress' ? 'primary' : ($task->status == 'review' ? 'info' : ($task->status == 'cancelled' ? 'secondary' : 'warning'))) }} ms-2">
                {{ ucfirst($task->status) }}
            </span>
            <span class="badge bg-{{ $task->priority == 'high' ? 'danger' : ($task->priority == 'medium' ? 'warning' : 'info') }} ms-1">
                {{ ucfirst($task->priority) }} Priority
            </span>
        </div>
        <div class="text-muted mt-1">
            <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a> &raquo; Task
        </div>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group">
            <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i>Back to Project
            </a>
            @can('update', $task)
            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>Edit Task
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Task Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Description</h6>
                    <div class="p-3 bg-light rounded">
                        {!! nl2br(e($task->description)) !!}
                    </div>
                </div>
                
                @if($task->attachments->count() > 0)
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Attachments</h6>
                    <div class="list-group">
                        @foreach($task->attachments as $attachment)
                        <a href="{{ Storage::url($attachment->file_path) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" target="_blank">
                            <div>
                                <i class="fas fa-file me-2"></i>
                                {{ $attachment->file_name }}
                                <small class="text-muted ms-2">{{ number_format($attachment->file_size / 1024, 2) }} KB</small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ Storage::url($attachment->file_path) }}" class="btn btn-outline-primary" download>
                                    <i class="fas fa-download"></i>
                                </a>
                                @can('delete', $task)
                                <form action="{{ route('tasks.attachments.delete', ['task' => $task, 'attachment' => $attachment]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this attachment?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
                
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%">Assigned To</th>
                                <td>
                                    @if($task->assignedTo)
                                        {{ $task->assignedTo->name }}
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td>{{ $task->createdBy->name }}</td>
                            </tr>
                            <tr>
                                <th>Due Date</th>
                                <td class="{{ $task->status != 'completed' && now()->gt($task->due_date) ? 'text-danger' : '' }}">
                                    {{ $task->due_date->format('M d, Y') }}
                                    @if($task->status != 'completed' && now()->gt($task->due_date))
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td>{{ $task->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%">Estimated Hours</th>
                                <td>{{ $task->estimated_hours ?? 'Not specified' }}</td>
                            </tr>
                            <tr>
                                <th>Actual Hours</th>
                                <td>{{ $task->actual_hours ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>Completed At</th>
                                <td>
                                    @if($task->completed_at)
                                        {{ $task->completed_at->format('M d, Y H:i') }}
                                    @else
                                        <span class="text-muted">Not completed</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Last Updated</th>
                                <td>{{ $task->updated_at->format('M d, Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Comments</h5>
                <span class="badge bg-secondary">{{ $task->comments->count() }}</span>
            </div>
            <div class="card-body">
                <form action="{{ route('tasks.comments.add', $task) }}" method="POST" class="mb-4">
                    @csrf
                    <div class="mb-3">
                        <label for="comment" class="form-label">Add Comment</label>
                        <textarea class="form-control @error('comment') is-invalid @enderror" id="comment" name="comment" rows="3" required>{{ old('comment') }}</textarea>
                        @error('comment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Post Comment</button>
                    </div>
                </form>
                
                <hr>
                
                <div class="comments">
                    @forelse($task->comments as $comment)
                    <div class="comment mb-3">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                @if($comment->user->profile_photo_path)
                                    <img src="{{ Storage::url($comment->user->profile_photo_path) }}" alt="{{ $comment->user->name }}" class="rounded-circle" width="40">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">{{ $comment->user->name }}</h6>
                                    <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-0">{{ $comment->comment }}</p>
                                @if(Auth::id() == $comment->user_id)
                                <div class="mt-1">
                                    <form action="{{ route('tasks.comments.delete', ['task' => $task, 'comment' => $comment]) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Are you sure you want to delete this comment?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p>No comments yet. Be the first to comment!</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @if($task->status != 'completed')
                    <form action="{{ route('tasks.update-status', $task) }}" method="POST" class="list-group-item p-3">
                        @csrf
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check me-1"></i>Mark as Completed
                        </button>
                    </form>
                    @endif
                    
                    @if($task->status == 'pending')
                    <form action="{{ route('tasks.update-status', $task) }}" method="POST" class="list-group-item p-3">
                        @csrf
                        <input type="hidden" name="status" value="in-progress">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-play me-1"></i>Start Working
                        </button>
                    </form>
                    @endif
                    
                    @if($task->status == 'in-progress')
                    <form action="{{ route('tasks.update-status', $task) }}" method="POST" class="list-group-item p-3">
                        @csrf
                        <input type="hidden" name="status" value="review">
                        <button type="submit" class="btn btn-info w-100">
                            <i class="fas fa-eye me-1"></i>Submit for Review
                        </button>
                    </form>
                    @endif
                    
                    <button type="button" class="list-group-item list-group-item-action p-3" data-bs-toggle="modal" data-bs-target="#logTimeModal">
                        <i class="fas fa-clock me-1"></i>Log Time
                    </button>
                    
                    @can('update', $task)
                    <button type="button" class="list-group-item list-group-item-action p-3" data-bs-toggle="modal" data-bs-target="#reassignModal">
                        <i class="fas fa-user-edit me-1"></i>Reassign Task
                    </button>
                    @endcan
                    
                    @can('delete', $task)
                    <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="list-group-item p-3" id="delete-task-form">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger w-100" onclick="confirmDelete()">
                            <i class="fas fa-trash me-1"></i>Delete Task
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Time Tracking</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <div>Estimated:</div>
                    <div><strong>{{ $task->estimated_hours ?? 0 }} hours</strong></div>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <div>Logged:</div>
                    <div><strong>{{ $task->actual_hours ?? 0 }} hours</strong></div>
                </div>
                
                @if($task->estimated_hours)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Progress</span>
                        @php
                            $timeProgress = $task->estimated_hours > 0 ? min(100, (($task->actual_hours ?? 0) / $task->estimated_hours) * 100) : 0;
                        @endphp
                        <span>{{ number_format($timeProgress, 1) }}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-{{ $timeProgress > 100 ? 'danger' : 'info' }}" role="progressbar" style="width: {{ min($timeProgress, 100) }}%;" aria-valuenow="{{ $timeProgress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                @endif
                
                @if($task->timeEntries && $task->timeEntries->count() > 0)
                <h6 class="mt-4 mb-2">Time Entries</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($task->timeEntries as $entry)
                            <tr>
                                <td>{{ $entry->date->format('M d, Y') }}</td>
                                <td>{{ $entry->user->name }}</td>
                                <td>{{ $entry->hours }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Log Time Modal -->
<div class="modal fade" id="logTimeModal" tabindex="-1" aria-labelledby="logTimeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tasks.log-time', $task) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="logTimeModalLabel">Log Time</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="hours" class="form-label">Hours <span class="text-danger">*</span></label>
                        <input type="number" step="0.25" min="0.25" class="form-control" id="hours" name="hours" required>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Log Time</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reassign Modal -->
<div class="modal fade" id="reassignModal" tabindex="-1" aria-labelledby="reassignModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tasks.update', $task) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="name" value="{{ $task->name }}">
                <input type="hidden" name="project_id" value="{{ $task->project_id }}">
                <input type="hidden" name="description" value="{{ $task->description }}">
                <input type="hidden" name="due_date" value="{{ $task->due_date->format('Y-m-d') }}">
                <input type="hidden" name="priority" value="{{ $task->priority }}">
                <input type="hidden" name="estimated_hours" value="{{ $task->estimated_hours }}">
                <input type="hidden" name="actual_hours" value="{{ $task->actual_hours }}">
                <input type="hidden" name="status" value="{{ $task->status }}">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="reassignModalLabel">Reassign Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label">Assign To <span class="text-danger">*</span></label>
                        <select class="form-select" id="assigned_to" name="assigned_to" required>
                            <option value="">-- Select Team Member --</option>
                            @foreach($projectMembers as $member)
                                <option value="{{ $member->id }}" {{ $task->assigned_to == $member->id ? 'selected' : '' }}>
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reassign_comment" class="form-label">Comment</label>
                        <textarea class="form-control" id="reassign_comment" name="comment" rows="3" placeholder="Add a comment about this reassignment"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reassign Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1" aria-labelledby="statusUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tasks.update-status', $task) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="statusUpdateModalLabel">Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status_update" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status_update" name="status" required>
                            <option value="pending" {{ $task->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in-progress" {{ $task->status == 'in-progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="review" {{ $task->status == 'review' ? 'selected' : '' }}>Review</option>
                            <option value="completed" {{ $task->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $task->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status_comment" class="form-label">Comment</label>
                        <textarea class="form-control" id="status_comment" name="comment" rows="3" placeholder="Add a comment about this status change"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Attachment Modal -->
<div class="modal fade" id="addAttachmentModal" tabindex="-1" aria-labelledby="addAttachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tasks.attachments.add', $task) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addAttachmentModalLabel">Add Attachment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="attachment" class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="attachment" name="attachments[]" multiple required>
                        <div class="form-text">You can upload multiple files. Maximum size per file: 10MB</div>
                    </div>
                    <div class="mb-3">
                        <label for="attachment_comment" class="form-label">Comment</label>
                        <textarea class="form-control" id="attachment_comment" name="comment" rows="3" placeholder="Add a comment about these attachments"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Attachment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Set default date to today for time logging
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.getElementById('date').value) {
            const today = new Date();
            document.getElementById('date').value = today.toISOString().split('T')[0];
        }
    });
    
    // Confirm delete
    function confirmDelete() {
        if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
            document.getElementById('delete-task-form').submit();
        }
    }
</script>
@endsection
