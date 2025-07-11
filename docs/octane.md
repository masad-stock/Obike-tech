# Laravel Octane

## Overview

Laravel Octane supercharges your application's performance by serving your application using high-powered application servers like Swoole. Octane boots your application once, keeps it in memory, and then feeds it requests at supersonic speeds.

## Benefits

- **Improved Performance**: Octane can handle more requests per second than traditional PHP-FPM.
- **Reduced Latency**: By keeping the application in memory, Octane eliminates the need to bootstrap the application for each request.
- **Concurrent Processing**: Octane enables true concurrency with Swoole, allowing multiple requests to be processed simultaneously.
- **WebSockets Support**: Swoole provides built-in WebSocket support for real-time applications.

## Server Requirements

- PHP 8.1 or higher
- Swoole PHP extension

## Starting Octane

To start the Octane server:

```bash
php artisan octane:start
```

For production environments, specify the number of workers:

```bash
php artisan octane:start --workers=4 --task-workers=2
```

To watch for file changes during development:

```bash
php artisan octane:start --watch
```

## Concurrency Features

### Concurrent Tasks

Octane allows you to execute operations concurrently using the `Octane::concurrently` method:

```php
use Laravel\Octane\Facades\Octane;

[$users, $orders] = Octane::concurrently([
    fn () => User::all(),
    fn () => Order::all(),
]);
```

### Octane Cache

Octane provides a high-performance cache driver powered by Swoole tables:

```php
Cache::store('octane')->put('key', 'value', 30);
```

### Preventing Overlapping Operations

Our application includes a `ConcurrencyService` that provides methods to prevent overlapping operations:

```php
$concurrencyService->withoutOverlapping('key', function () {
    // This code will not execute concurrently
});
```

## Best Practices

1. **Avoid Statics**: Don't use static properties to store state between requests.
2. **Reset State**: Reset any global state between requests.
3. **Dependency Injection**: Be careful when injecting the application container or request into constructors.
4. **Memory Management**: Monitor memory usage to prevent leaks.

## Monitoring

Use the `octane:status` command to check the status of your Octane server:

```bash
php artisan octane:status
```

## Production Deployment

For production deployment, consider using a process manager like Supervisor to ensure Octane stays running:

```
[program:octane]
process_name=%(program_name)s
command=php /path/to/your/project/artisan octane:start --server=swoole --host=0.0.0.0 --port=8000 --workers=4 --task-workers=2
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/octane.log
stopwaitsecs=3600
```

## Handling Stateful Applications

Since Octane keeps your application in memory between requests, you need to be careful with state:

1. **Reset Singletons**: Reset any singleton instances that should not persist between requests.
2. **Avoid Static Properties**: Don't use static properties to store request-specific state.
3. **Use Request Lifecycle Hooks**: Use Octane's lifecycle hooks to reset state between requests.

## Using Our ConcurrencyService

Our application includes a `ConcurrencyService` that provides several methods for handling concurrent operations:

1. **runConcurrently**: Execute multiple operations concurrently
2. **withLock**: Execute an operation with a distributed lock
3. **withoutOverlapping**: Prevent overlapping tasks
4. **throttle**: Rate limit operations

Example usage:

```php
// Execute multiple operations concurrently
[$users, $projects, $tasks] = $concurrencyService->runConcurrently([
    fn() => User::all(),
    fn() => Project::all(),
    fn() => Task::all(),
]);

// Prevent overlapping operations
$concurrencyService->withoutOverlapping('generate-report', function() {
    // This will only execute once at a time
    $this->generateLargeReport();
});

// Rate limit operations
$concurrencyService->throttle('api-calls', 10, 60, function() {
    // Limited to 10 calls per minute
    return $this->makeExternalApiCall();
});
```

## Troubleshooting

If you encounter issues with Octane, check the following:

1. **Memory Leaks**: Monitor memory usage to detect leaks.
2. **Swoole Extension**: Ensure the Swoole extension is properly installed.
3. **Port Conflicts**: Make sure the port Octane is using isn't already in use.
4. **File Permissions**: Ensure proper file permissions for logs and storage.

For more information, refer to the [official Laravel Octane documentation](https://laravel.com/docs/10.x/octane).
