# Middleware

Middleware provides a way to filter HTTP requests entering your application. Use them for authentication, logging, CORS, security headers, and more.

## Creating Middleware

### Generate Middleware

```bash
php lalaz g:middleware AuthMiddleware
```

Creates `app/Middleware/AuthMiddleware.php`:

```php
<?php

namespace App\Middleware;

use Lalaz\Http\Middleware;
use Lalaz\Http\Request;
use Closure;

class AuthMiddleware extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        // Code before controller
        
        $response = $next($request);
        
        // Code after controller
        
        return $response;
    }
}
```

## Authentication Example

```php
<?php

namespace App\Middleware;

use Lalaz\Http\Middleware;
use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Closure;

class AuthMiddleware extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session->has('user_id')) {
            return Response::redirect('/login');
        }
        
        return $next($request);
    }
}
```

## Applying Middleware

### On Individual Routes

```php
use App\Middleware\AuthMiddleware;

$router->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(AuthMiddleware::class);
```

### On Multiple Routes

```php
$router->get('/profile', [ProfileController::class, 'show'])
    ->middleware([AuthMiddleware::class, LogMiddleware::class]);
```

### On Route Groups

```php
$router->group(['middleware' => [AuthMiddleware::class]], function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->get('/settings', [SettingsController::class, 'edit']);
});
```

## Built-in Middleware

### CSRF Protection

```php
use Lalaz\Security\Middleware\CsrfMiddleware;

$router->post('/users', [UserController::class, 'store'])
    ->middleware(CsrfMiddleware::class);
```

In your form:

```html
<form method="POST" action="/users">
    <?= csrf_field() ?>
    <!-- form fields -->
</form>
```

### Security Headers

```php
use Lalaz\Security\Middleware\SecurityHeadersMiddleware;

// Apply globally or per route
$router->group(['middleware' => [SecurityHeadersMiddleware::class]], function($router) {
    // All routes protected
});
```

**Presets**:

```php
// In your middleware
SecurityHeadersMiddleware::usePreset('strict');
```

Available presets: `minimal`, `recommended`, `strict`, `api`.

## Common Middleware Examples

### Logging Middleware

```php
class LogRequestMiddleware extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = round((microtime(true) - $start) * 1000, 2);
        
        Log::info("Request processed", [
            'method' => $request->method,
            'uri' => $request->uri,
            'duration' => $duration . 'ms',
            'status' => $response->statusCode
        ]);
        
        return $response;
    }
}
```

### CORS Middleware

```php
class CorsMiddleware extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->method === 'OPTIONS') {
            return Response::json([], 200)
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
        
        $response = $next($request);
        
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
}
```

### Rate Limiting

```php
class RateLimitMiddleware extends Middleware
{
    private const MAX_REQUESTS = 60;
    private const PERIOD = 60; // seconds
    
    public function handle(Request $request, Closure $next)
    {
        $key = 'rate_limit:' . $request->ip();
        $cache = app('cache');
        
        $requests = (int) $cache->get($key, 0);
        
        if ($requests >= self::MAX_REQUESTS) {
            return Response::json([
                'error' => 'Too many requests'
            ], 429);
        }
        
        $cache->set($key, $requests + 1, self::PERIOD);
        
        $response = $next($request);
        
        return $response
            ->withHeader('X-RateLimit-Limit', self::MAX_REQUESTS)
            ->withHeader('X-RateLimit-Remaining', self::MAX_REQUESTS - $requests - 1);
    }
}
```

### JSON Only API Middleware

```php
class JsonOnlyMiddleware extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('Content-Type') !== 'application/json') {
            return Response::json([
                'error' => 'Content-Type must be application/json'
            ], 415);
        }
        
        return $next($request);
    }
}
```

## Middleware with Parameters

```php
class RoleMiddleware extends Middleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->session->get('user');
        
        if (!$user || $user['role'] !== $role) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        return $next($request);
    }
}
```

Usage:

```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware([AuthMiddleware::class, RoleMiddleware::class . ':admin']);
```

## Middleware Execution Order

Middleware executes in the order defined:

```php
$router->get('/users', [UserController::class, 'index'])
    ->middleware([
        LogMiddleware::class,      // 1st
        AuthMiddleware::class,     // 2nd
        RoleMiddleware::class      // 3rd
    ]);
```

**Flow**:
```
Request → Log → Auth → Role → Controller → Role → Auth → Log → Response
```

## Best Practices

### Keep Middleware Focused

```php
// ✅ Good - single responsibility
class AuthMiddleware extends Middleware { }
class LogMiddleware extends Middleware { }
class CorsMiddleware extends Middleware { }

// ❌ Bad - doing too much
class MasterMiddleware extends Middleware {
    // authentication + logging + CORS
}
```

### Early Returns

```php
public function handle(Request $request, Closure $next)
{
    // Validate early, return fast
    if (!$this->isValid($request)) {
        return Response::json(['error' => 'Invalid'], 400);
    }
    
    return $next($request);
}
```

### Use Dependency Injection

```php
class AuthMiddleware extends Middleware
{
    public function __construct(
        private SessionManager $session,
        private Logger $logger
    ) {}
    
    public function handle(Request $request, Closure $next)
    {
        if (!$this->session->has('user_id')) {
            $this->logger->warning('Unauthorized access attempt');
            return Response::redirect('/login');
        }
        
        return $next($request);
    }
}
```

### Group Related Routes

```php
// API routes with common middleware
$router->group([
    'prefix' => '/api',
    'middleware' => [CorsMiddleware::class, JsonOnlyMiddleware::class]
], function($router) {
    $router->get('/users', [ApiUserController::class, 'index']);
    $router->post('/users', [ApiUserController::class, 'store']);
});

// Admin routes
$router->group([
    'prefix' => '/admin',
    'middleware' => [AuthMiddleware::class, RoleMiddleware::class . ':admin']
], function($router) {
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
    $router->get('/users', [AdminUserController::class, 'index']);
});
```

## Testing Middleware

```php
use Pest;
use App\Middleware\AuthMiddleware;

it('redirects unauthenticated users', function () {
    $request = new Request('GET', '/dashboard');
    $middleware = new AuthMiddleware();
    
    $response = $middleware->handle($request, fn($req) => Response::ok());
    
    expect($response->statusCode)->toBe(302);
    expect($response->headers['Location'])->toBe('/login');
});

it('allows authenticated users', function () {
    $request = new Request('GET', '/dashboard');
    $request->session->set('user_id', 1);
    
    $middleware = new AuthMiddleware();
    $response = $middleware->handle($request, fn($req) => Response::ok());
    
    expect($response->statusCode)->toBe(200);
});
```

## Troubleshooting

### Middleware Not Executing

**Problem**: Route doesn't respect middleware.

```php
// ❌ Wrong - forgot to register
$router->get('/users', [UserController::class, 'index']);
```

**Solution**: Add middleware explicitly:

```php
// ✅ Correct
$router->get('/users', [UserController::class, 'index'])
    ->middleware(AuthMiddleware::class);
```

### Headers Already Sent

**Problem**: Cannot set headers after output.

**Solution**: Don't echo/print before middleware completes:

```php
// ❌ Wrong
public function handle(Request $request, Closure $next) {
    echo "Debug info"; // Outputs too early
    return $next($request);
}

// ✅ Correct
public function handle(Request $request, Closure $next) {
    Log::debug("Debug info"); // Use logging
    return $next($request);
}
```

### Middleware Order Issues

**Problem**: Auth fails because logging consumes request body.

**Solution**: Place non-destructive middleware first:

```php
// ✅ Correct order
->middleware([
    CorsMiddleware::class,     // Headers first
    LogMiddleware::class,      // Logging
    AuthMiddleware::class,     // Then auth
    ValidationMiddleware::class // Finally validation
]);
```

---

**Next**: [Database Guide](05-database.md) →
