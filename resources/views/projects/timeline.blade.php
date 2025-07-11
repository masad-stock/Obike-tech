@extends('layouts.app')

@section('title', $project->name . ' - Timeline')

@section('header', 'Project Timeline')

@section('styles')
<style>
    .timeline {
        position: relative;
        padding: 20px 0;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        width: 4px;
        background-color: #e9ecef;
        top: 0;
        bottom: 0;
        left: 50%;
        margin-left: -2px;
    }
    
    .timeline-item {
        padding: 10px 40px;
        position: relative;
        width: 50%;
        box-sizing: border-box;
    }
    
    .timeline-item::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        background-color: white;
        border: 4px solid #007bff;
        border-radius: 50%;
        top: 15px;
        z-index: 1;
    }
    
    .timeline-item.left {
        left: 0;
    }
    
    .timeline-item.right {
        left: 50%;
    }
    
    .timeline-item.left::after {
        right: -10px;
    }
    
    .timeline-item.right::after {
        left: -10px;
    }
    
    .timeline-content {
        padding: 15px;
        background-color: white;
        border-radius: 6px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .timeline-date {
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .timeline-badge-project {
        background-color: #007bff;
    }
    
    .timeline-badge-task {
        background-color: #28a745;
    }
    
    .timeline-badge-milestone {
        background-color: #dc3545;
    }
    
    .timeline-item.left::after {
        border-color: #007bff;
    }
    
    .timeline-item.right::after {
        border-color: #28a745;
    }
    
    .timeline-item.milestone::after {
        border-color: #dc3545;
    }
</style>
@endsection

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="d-flex align-items-center">
            <h1 class="h3 mb-0">{{ $project->name }} - Timeline</h1>
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

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Project Timeline</h5>
    </div>
    <div class="card-body">
        <div class="timeline">
            @php $count = 0; @endphp
            @foreach($timelineEvents as $event)
                @php 
                    $count++;
                    $side = $count % 2 == 0 ? 'right' : 'left';
                    $badgeClass = $event['type'] == 'project' ? 'timeline-badge-project' : 'timeline-badge-task';
                    if (isset($event['milestone']) && $event['milestone']) {
                        $badgeClass = 'timeline-badge-milestone';
                        $side .= ' milestone';
                    }
                @endphp
                <div class="timeline-item {{ $side }}">
                    <div class="timeline-content">
                        <div class="timeline-date">{{ \Carbon\Carbon::parse($event['date'])->format('M d, Y') }}</div>
                        <h5>{{ $event['event'] }}</h5>
                        <p>{{ $event['description'] }}</p>
                        
                        @if(isset($event['task']))
                            <div class="mt-2">
                                <span class="badge bg-{{ $event['task']->priority == 'high' ? 'danger' : ($event['task']->priority == 'medium' ? 'warning' : 'info') }}">
                                    {{ ucfirst($event['task']->priority) }} Priority
                                </span>
                                
                                @if($event['task']->status == 'completed')
                                    <span class="badge bg-success ms-1">Completed</span>
                                @elseif($event['task']->status == 'in-progress')
                                    <span class="badge bg-primary ms-1">In Progress</span>
                                @endif
                                
                                @if($event['task']->assignedTo)
                                    <div class="mt-1 small">
                                        <i class="fas fa-user me-1"></i>Assigned to: {{ $event['task']->assignedTo->name }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection