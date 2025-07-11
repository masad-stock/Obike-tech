@extends('layouts.app')

@section('title', 'Create Task')

@section('header', 'Create New Task')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="d-flex align-items-center">
            <h1 class="h3 mb-0">Create New Task for {{ $project->name }}</h1>
            <span class="badge bg-{{ $project->status == 'active' ? 'success' : ($project->status == 'completed' ? 'primary' : ($project->status == 'on-hold' ? 'warning' : 'secondary')) }} ms-2">
                {{ ucfirst($project->status) }}
            </span>
        </div>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Back to Project
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Task Details</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('projects.tasks.store', $project) }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Task Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label for="assigned_to" class="form-label">Assign To</label>
                    <select class="form-select @error('assigned_to') is-invalid @enderror" id="assigned_to" name="assigned_to">
                        <option value="">-- Select Team Member --</option>
                        @foreach($project->members as $member)
                            <option value="{{ $member->user_id }}" {{ old('assigned_to') == $member->user_id ? 'selected' : '' }}>
                                {{ $member->user->name }} ({{ ucfirst($member->role) }})
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ old('due_date') }}" required>
                    @error('due_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                    <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                        <option value="">-- Select Priority --</option>
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                    @error('priority')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="estimated_hours" class="form-label">Estimated Hours</label>
                    <input type="number" step="0.5" min="0" class="form-control @error('estimated_hours') is-invalid @enderror" id="estimated_hours" name="estimated_hours" value="{{ old('estimated_hours') }}">
                    @error('estimated_hours')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in-progress" {{ old('status') == 'in-progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="review" {{ old('status') == 'review' ? 'selected' : '' }}>Review</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="mb-3">
                <label for="attachments" class="form-label">Attachments</label>
                <input type="file" class="form-control @error('attachments.*') is-invalid @enderror" id="attachments" name="attachments[]" multiple>
                <div class="form-text">You can upload multiple files. Maximum size per file: 10MB</div>
                @error('attachments.*')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="mb-3">
                <label class="form-label">Dependencies</label>
                <div class="card">
                    <div class="card-body p-2">
                        <div class="row">
                            @forelse($project->tasks as $existingTask)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dependencies[]" value="{{ $existingTask->id }}" id="task-{{ $existingTask->id }}" {{ (old('dependencies') && in_array($existingTask->id, old('dependencies'))) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="task-{{ $existingTask->id }}">
                                            {{ $existingTask->name }}
                                            <span class="badge bg-{{ $existingTask->status == 'completed' ? 'success' : ($existingTask->status == 'in-progress' ? 'primary' : ($existingTask->status == 'review' ? 'info' : ($existingTask->status == 'cancelled' ? 'secondary' : 'warning'))) }}">
                                                {{ ucfirst($existingTask->status) }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted mb-0">No existing tasks to set as dependencies.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags</label>
                        <input type="text" class="form-control @error('tags') is-invalid @enderror" id="tags" name="tags" value="{{ old('tags') }}" placeholder="Enter tags separated by commas">
                        @error('tags')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="milestone" class="form-label">Milestone</label>
                        <select class="form-select @error('milestone') is-invalid @enderror" id="milestone" name="milestone">
                            <option value="">-- None --</option>
                            <option value="planning" {{ old('milestone') == 'planning' ? 'selected' : '' }}>Planning</option>
                            <option value="design" {{ old('milestone') == 'design' ? 'selected' : '' }}>Design</option>
                            <option value="development" {{ old('milestone') == 'development' ? 'selected' : '' }}>Development</option>
                            <option value="testing" {{ old('milestone') == 'testing' ? 'selected' : '' }}>Testing</option>
                            <option value="deployment" {{ old('milestone') == 'deployment' ? 'selected' : '' }}>Deployment</option>
                            <option value="review" {{ old('milestone') == 'review' ? 'selected' : '' }}>Review</option>
                        </select>
                        @error('milestone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end mt-4">
                <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Task</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Set default due date to tomorrow
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.getElementById('due_date').value) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('due_date').value = tomorrow.toISOString().split('T')[0];
        }
    });
</script>
@endsection
