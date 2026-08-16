# Logging

Lalaz implements PSR-3 logging for tracking application events, debugging, and monitoring.

## Quick Start

```php
use Lalaz\Logging\Log;

Log::info('User logged in', ['user_id' => 123]);
Log::error('Payment failed', ['order_id' => 456, 'amount' => 99.99]);
Log::warning('Low disk space', ['available' => '5GB']);
```

## Log Levels

PSR-3 compliant log levels (from least to most severe):

```php
Log::debug('Detailed debugging information');
Log::info('Interesting events (user login, SQL logs)');
Log::notice('Normal but significant events');
Log::warning('Exceptional occurrences that are not errors');
Log::error('Runtime errors that do not require immediate action');
Log::critical('Critical conditions (application component unavailable)');
Log::alert('Action must be taken immediately');
Log::emergency('System is unusable');
```

## Configuration

### File Driver (Default)

In `.env`:

```env
LOG_DRIVER=file
LOG_PATH=storage/logs
LOG_LEVEL=info
```

### Log Rotation

Logs automatically rotate daily:

```
storage/logs/
    app-2024-01-15.log
    app-2024-01-16.log
    app-2024-01-17.log
```

### Set Minimum Level

Only logs at or above the configured level are written:

```env
LOG_LEVEL=warning  # Only warning, error, critical, alert, emergency
```

## Usage Examples

### Basic Logging

```php
Log::info('Application started');
Log::error('Database connection failed');
```

### With Context

```php
Log::info('User registered', [
    'user_id' => $user->id,
    'email' => $user->email,
    'ip' => $request->ip()
]);

Log::error('Payment processing failed', [
    'order_id' => $order->id,
    'amount' => $order->total,
    'gateway' => 'stripe',
    'error' => $exception->getMessage()
]);
```

### Exception Logging

```php
try {
    $result = $api->call();
} catch (Exception $e) {
    Log::error('API call failed', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
    
    throw $e;
}
```

### Performance Monitoring

```php
$start = microtime(true);

// Perform operation
$result = $this->expensiveOperation();

$duration = round((microtime(true) - $start) * 1000, 2);

Log::info('Expensive operation completed', [
    'duration_ms' => $duration,
    'result_count' => count($result)
]);

if ($duration > 1000) {
    Log::warning('Slow operation detected', [
        'duration_ms' => $duration,
        'threshold_ms' => 1000
    ]);
}
```

## Custom Loggers

### Create Custom Logger

```php
<?php

namespace App\Logging;

use Lalaz\Logging\Contracts\LoggerInterface;

class DatabaseLogger implements LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void
    {
        DB::table('logs')->insert([
            'level' => $level,
            'message' => $message,
            'context' => json_encode($context),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }
    
    // Implement other PSR-3 methods...
}
```

### Register Custom Logger

In `bootstrap/app.php`:

```php
$app->register('logger', function() {
    return new DatabaseLogger();
});
```

## Logging in Controllers

```php
<?php

namespace App\Controllers;

use Lalaz\Http\Controller;
use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Logging\Log;
use App\Models\User;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([...]);
        
        try {
            $user = User::create($data);
            
            Log::info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);
            
            return Response::json($user, 201);
            
        } catch (Exception $e) {
            Log::error('User creation failed', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::json([
                'error' => 'Failed to create user'
            ], 500);
        }
    }
}
```

## Logging Middleware

```php
<?php

namespace App\Middleware;

use Lalaz\Http\Middleware;
use Lalaz\Http\Request;
use Lalaz\Logging\Log;
use Closure;

class LogRequestMiddleware extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        
        Log::info('Request received', [
            'method' => $request->method,
            'uri' => $request->uri,
            'ip' => $request->ip()
        ]);
        
        $response = $next($request);
        
        $duration = round((microtime(true) - $start) * 1000, 2);
        
        Log::info('Request processed', [
            'method' => $request->method,
            'uri' => $request->uri,
            'status' => $response->statusCode,
            'duration_ms' => $duration
        ]);
        
        if ($duration > 1000) {
            Log::warning('Slow request detected', [
                'uri' => $request->uri,
                'duration_ms' => $duration
            ]);
        }
        
        return $response;
    }
}
```

## Log Format

Default format:

```
[2024-01-15 14:32:10] INFO: User logged in {"user_id":123,"ip":"192.168.1.1"}
[2024-01-15 14:32:15] ERROR: Payment failed {"order_id":456,"amount":99.99,"error":"Insufficient funds"}
```

## Best Practices

### 1. Use Appropriate Levels

```php
// ✅ Good - correct levels
Log::info('User logged in');           // Informational
Log::warning('Disk space low');        // Warning
Log::error('Payment failed');          // Error

// ❌ Bad - incorrect levels
Log::error('User logged in');          // Not an error
Log::info('Database connection lost'); // Should be error
```

### 2. Include Context

```php
// ✅ Good - helpful context
Log::error('Order processing failed', [
    'order_id' => $order->id,
    'user_id' => $user->id,
    'amount' => $order->total,
    'error' => $exception->getMessage()
]);

// ❌ Bad - no context
Log::error('Order processing failed');
```

### 3. Don't Log Sensitive Data

```php
// ✅ Good - safe logging
Log::info('User authenticated', [
    'user_id' => $user->id,
    'email' => $user->email
]);

// ❌ Bad - logs password
Log::info('User authenticated', [
    'email' => $user->email,
    'password' => $password  // NEVER LOG PASSWORDS
]);
```

### 4. Log Exceptions Properly

```php
// ✅ Good - complete exception info
try {
    // ...
} catch (Exception $e) {
    Log::error('Operation failed', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

// ❌ Bad - insufficient info
try {
    // ...
} catch (Exception $e) {
    Log::error('Error');
}
```

### 5. Monitor Performance

```php
// ✅ Good - track slow operations
$start = microtime(true);
$result = $this->operation();
$duration = (microtime(true) - $start) * 1000;

if ($duration > 500) {
    Log::warning('Slow operation', [
        'duration_ms' => $duration,
        'threshold_ms' => 500
    ]);
}
```

## Viewing Logs

### Tail Logs in Real-Time

```bash
tail -f storage/logs/app-$(date +%Y-%m-%d).log
```

### Search Logs

```bash
# Find errors
grep "ERROR" storage/logs/*.log

# Find specific user
grep "user_id\":123" storage/logs/*.log

# Find slow requests
grep "Slow request" storage/logs/*.log
```

### Parse JSON Context

```bash
# Extract JSON context from logs
grep "ERROR" storage/logs/app-*.log | grep -oP '\{.*\}' | jq .
```

## Troubleshooting

### Logs Not Writing

**Check permissions**:

```bash
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs
```

**Verify configuration**:

```php
// .env
LOG_DRIVER=file
LOG_PATH=storage/logs
```

### Log Files Too Large

Implement cleanup:

```bash
# Delete logs older than 30 days
find storage/logs -name "*.log" -mtime +30 -delete
```

Or use logrotate:

```bash
# /etc/logrotate.d/lalaz
/path/to/app/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

---

**Next**: [Events & Jobs](11-events-jobs.md) →
