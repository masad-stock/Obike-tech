# API Documentation

## Overview

This document outlines the REST API endpoints available in the Obike Tech System. Our API follows RESTful principles and uses JSON for data exchange.

## Authentication

All API endpoints require authentication using Laravel Sanctum. Include your API token in the request header:

```
Authorization: Bearer YOUR_API_TOKEN
```

To obtain a token, use the login endpoint described below.

## Available Endpoints

### Authentication

#### Login

```
POST /api/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "your_password",
  "device_name": "Browser"
}
```

**Response:**
```json
{
  "token": "YOUR_API_TOKEN",
  "user": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "department": "Engineering",
    "roles": ["admin", "manager"]
  }
}
```

#### Logout

```
POST /api/logout
```

**Headers:**
```
Authorization: Bearer YOUR_API_TOKEN
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

### Projects

#### List Projects

```
GET /api/projects
```

**Query Parameters:**
- `status` (optional): Filter by status (active, completed, on-hold)
- `client_id` (optional): Filter by client ID
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Project Name",
      "description": "Project description",
      "status": "active",
      "client": {
        "id": 1,
        "name": "Client Name"
      },
      "start_date": "2023-01-01",
      "expected_end_date": "2023-12-31",
      "budget": 10000
    }
  ],
  "links": {
    "first": "http://example.com/api/projects?page=1",
    "last": "http://example.com/api/projects?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://example.com/api/projects",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

#### Get Project Details

```
GET /api/projects/{id}
```

**Response:**
```json
{
  "id": 1,
  "name": "Project Name",
  "description": "Project description",
  "status": "active",
  "client": {
    "id": 1,
    "name": "Client Name"
  },
  "manager": {
    "id": 1,
    "name": "Manager Name"
  },
  "start_date": "2023-01-01",
  "expected_end_date": "2023-12-31",
  "budget": 10000,
  "members": [
    {
      "id": 1,
      "user": {
        "id": 2,
        "name": "Team Member"
      },
      "role": "developer"
    }
  ],
  "tasks": [
    {
      "id": 1,
      "title": "Task Title",
      "description": "Task description",
      "status": "in-progress",
      "due_date": "2023-06-30"
    }
  ]
}
```

### Clients

#### List Clients

```
GET /api/clients
```

**Query Parameters:**
- `status` (optional): Filter by status (active, inactive, potential)
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Client Name",
      "company_email": "client@example.com",
      "phone": "+1234567890",
      "status": "active"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

### Equipment

#### List Equipment

```
GET /api/mechanical/equipment
```

**Query Parameters:**
- `category_id` (optional): Filter by category ID
- `status` (optional): Filter by status (available, in-use, maintenance)
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Equipment Name",
      "serial_number": "SN12345",
      "category": {
        "id": 1,
        "name": "Category Name"
      },
      "status": "available",
      "purchase_date": "2022-01-01",
      "purchase_cost": 5000
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

### Rental Agreements

#### List Rental Agreements

```
GET /api/rentals/agreements
```

**Query Parameters:**
- `status` (optional): Filter by status (active, completed, cancelled)
- `customer_id` (optional): Filter by customer ID
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "customer_name": "Customer Name",
      "start_date": "2023-01-01",
      "end_date": "2023-01-31",
      "status": "active",
      "total_amount": 1500
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

## Error Handling

All API endpoints return appropriate HTTP status codes:

- `200 OK`: Request succeeded
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Authentication required or failed
- `403 Forbidden`: Authenticated but not authorized
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `500 Server Error`: Server-side error

Error responses include a message and, for validation errors, details about the validation failures:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password field is required."
    ]
  }
}
```

## Rate Limiting

API requests are subject to rate limiting to prevent abuse. The default limit is 60 requests per minute per authenticated user.

Rate limit headers are included in all API responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1626962240
```

## Versioning

The current API version is v1. All endpoints are prefixed with `/api/v1/`. When breaking changes are introduced, a new version will be created.