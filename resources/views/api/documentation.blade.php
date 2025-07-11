@extends('layouts.app')

@section('title', 'API Documentation')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">API Endpoints</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="#authentication" class="list-group-item list-group-item-action">Authentication</a>
                        <a href="#projects" class="list-group-item list-group-item-action">Projects</a>
                        <a href="#clients" class="list-group-item list-group-item-action">Clients</a>
                        <a href="#equipment" class="list-group-item list-group-item-action">Equipment</a>
                        <a href="#rentals" class="list-group-item list-group-item-action">Rental Agreements</a>
                        <a href="#errors" class="list-group-item list-group-item-action">Error Handling</a>
                        <a href="#rate-limiting" class="list-group-item list-group-item-action">Rate Limiting</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">API Documentation</h4>
                    <div>
                        <a href="{{ route('api.documentation.download') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i>Download
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <h5>Overview</h5>
                    <p>
                        This documentation outlines the REST API endpoints available in the Obike Tech System. 
                        Our API follows RESTful principles and uses JSON for data exchange.
                    </p>
                    
                    <h5>Authentication</h5>
                    <p>
                        All API endpoints require authentication using Laravel Sanctum. Include your API token in the request header:
                    </p>
                    <pre><code>Authorization: Bearer YOUR_API_TOKEN</code></pre>
                    
                    <div id="authentication" class="mt-4">
                        <h5>Authentication Endpoints</h5>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <span class="badge bg-success me-2">POST</span>
                                <code>/api/login</code>
                            </div>
                            <div class="card-body">
                                <h6>Request Body:</h6>
                                <pre><code>{
  "email": "user@example.com",
  "password": "your_password",
  "device_name": "Browser"
}</code></pre>
                                
                                <h6>Response:</h6>
                                <pre><code>{
  "token": "YOUR_API_TOKEN",
  "user": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "department": "Engineering",
    "roles": ["admin", "manager"]
  }
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <span class="badge bg-success me-2">POST</span>
                                <code>/api/logout</code>
                            </div>
                            <div class="card-body">
                                <h6>Headers:</h6>
                                <pre><code>Authorization: Bearer YOUR_API_TOKEN</code></pre>
                                
                                <h6>Response:</h6>
                                <pre><code>{
  "message": "Logged out successfully"
}</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional endpoint documentation sections would go here -->
                    
                    <div id="errors" class="mt-4">
                        <h5>Error Handling</h5>
                        <p>
                            All API endpoints return appropriate HTTP status codes:
                        </p>
                        <ul>
                            <li><code>200 OK</code>: Request succeeded</li>
                            <li><code>201 Created</code>: Resource created successfully</li>
                            <li><code>400 Bad Request</code>: Invalid request parameters</li>
                            <li><code>401 Unauthorized</code>: Authentication required or failed</li>
                            <li><code>403 Forbidden</code>: Authenticated but not authorized</li>
                            <li><code>404 Not Found</code>: Resource not found</li>
                            <li><code>422 Unprocessable Entity</code>: Validation errors</li>
                            <li><code>500 Server Error</code>: Server-side error</li>
                        </ul>
                    </div>
                    
                    <div id="rate-limiting" class="mt-4">
                        <h5>Rate Limiting</h5>
                        <p>
                            API requests are subject to rate limiting to prevent abuse. The default limit is 60 requests per minute per authenticated user.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection