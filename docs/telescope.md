# Laravel Telescope

## Overview

Laravel Telescope is a debugging assistant for the Laravel framework. It provides insight into the requests coming into your application, exceptions, log entries, database queries, queued jobs, mail, notifications, cache operations, scheduled tasks, variable dumps, and more.

## Accessing Telescope

In the local development environment, Telescope is available at:

```
http://your-app-url/telescope
```

## Available Watchers

Telescope includes several "watchers" that gather application data when a request or console command is executed:

- **Requests**: HTTP requests made to your application
- **Commands**: Artisan commands run in your application
- **Queries**: Database queries executed by your application
- **Jobs**: Background jobs processed by your application
- **Events**: Events dispatched in your application
- **Mail**: Emails sent by your application
- **Notifications**: Notifications sent by your application
- **Cache**: Cache operations performed by your application
- **Redis**: Redis operations performed by your application
- **Exceptions**: Exceptions thrown in your application
- **Dumps**: Variables dumped using the `dump()` function
- **Gates**: Authorization gate checks performed by your application
- **Logs**: Log entries written by your application
- **Models**: Eloquent model operations
- **Scheduled Tasks**: Scheduled tasks run by your application

## Configuration

Telescope's configuration file is located at `config/telescope.php`. You can customize which watchers are enabled and their specific settings.

In the `.env` file, you can control Telescope with these variables:

```
TELESCOPE_ENABLED=true
TELESCOPE_PATH=telescope
```

## Security

In production environments, Telescope is disabled by default. If you need to use it in production, you should ensure that only authorized users can access it.

Access to Telescope is controlled by the `gate` method in the `TelescopeServiceProvider`. By default, it allows access to users with the `view-telescope` permission.

## Data Pruning

Telescope data can grow quickly. To prevent your database from becoming too large, Telescope automatically prunes old entries. You can configure pruning in the `telescope.php` config file.

## Additional Resources

- [Laravel Telescope Documentation](https://laravel.com/docs/10.x/telescope)
- [GitHub Repository](https://github.com/laravel/telescope)