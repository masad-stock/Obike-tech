@extends('layouts.app')

@section('title', 'Feature Management')

@section('header', 'Feature Management')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Feature Flags</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Feature flags allow you to gradually roll out new features to users. 
                        This helps reduce risk and allows for controlled testing of new functionality.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Available Features</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Feature</th>
                                    <th>Description</th>
                                    <th>Enabled For</th>
                                    <th>Percentage</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($features as $key => $feature)
                                <tr>
                                    <td class="fw-bold">{{ $feature['name'] }}</td>
                                    <td>{{ $feature['description'] }}</td>
                                    <td>{{ $feature['total_users'] }} / {{ $totalUsers }} users</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                style="width: {{ $feature['percentage'] }}%;" 
                                                aria-valuenow="{{ $feature['percentage'] }}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                                {{ $feature['percentage'] }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('features.show', $key) }}" class="btn btn-sm btn-primary">
                                            Manage
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection