# Routing

Routing is the core of any web application. Lalaz Framework provides a powerful, expressive, and fast routing system that makes defining routes a pleasure.

## 📚 Table of Contents

- [Basic Routing](#basic-routing)
- [Route Parameters](#route-parameters)
- [Route Groups](#route-groups)
- [Route Middleware](#route-middleware)
- [Named Routes](#named-routes)
- [Route Caching](#route-caching)
- [Best Practices](#best-practices)

## Basic Routing

Routes are defined in `routes/web.php`. The most basic route accepts a URI and a closure:

```php
<?php

use Lalaz\Lalaz;

$router = Lalaz::router();

$router->get('/', function() {
    return 'Welcome to Lalaz!';
});
```

### Available HTTP Methods

Lalaz supports all standard HTTP methods:

```php
$router->get('/users', $callback);
$router->post('/users', $callback);
$router->put('/users/{id}', $callback);
$router->patch('/users/{id}', $callback);
$router->delete('/users/{id}', $callback);
```

### Controller Actions

Instead of closures, you can route to controller methods:

```php
use App\Controllers\UserController;

$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);
```

### Multiple Methods

Handle multiple HTTP methods for one route:

```php
$router->match(['GET', 'POST'], '/form', function() {
    // Handle both GET and POST
});
```

## Route Parameters

### Required Parameters

Capture dynamic segments of the URI:

```php
$router->get('/user/{id}', function($id) {
    return "User ID: {$id}";
});

$router->get('/posts/{post}/comments/{comment}', function($post, $comment) {
    return "Post: {$post}, Comment: {$comment}";
});
```

Parameters are automatically injected into your closure or controller method:

```php
$router->get('/profile/{username}', [ProfileController::class, 'show']);

// In ProfileController:
public function show($username)
{
    return "Profile: {$username}";
}
```

### Optional Parameters

Make parameters optional with a `?` suffix:

```php
$router->get('/user/{name?}', function($name = 'Guest') {
    return "Hello, {$name}!";
});
```

### Parameter Constraints

Restrict parameter format using regular expressions:

```php
// Only accept numeric IDs
$router->get('/user/{id:\d+}', function($id) {
    return "User: {$id}";
});

// Only accept alphanumeric usernames
$router->get('/profile/{username:[a-zA-Z0-9]+}', function($username) {
    return "Profile: {$username}";
});

// UUID format
$router->get('/order/{uuid:[0-9a-f\-]{36}}', function($uuid) {
    return "Order: {$uuid}";
});
```

Common regex patterns:

```php
// Numeric only
{id:\d+}

// Alphanumeric
{username:[a-zA-Z0-9_]+}

// Slug (letters, numbers, hyphens)
{slug:[a-z0-9\-]+}

// Email-like
{email:[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}}
```

## Route Groups

Group routes that share common attributes like middleware, prefixes, or namespaces.

### Basic Grouping

```php
$router->group(function($router) {
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->get('/settings', [SettingsController::class, 'index']);
    $router->post('/settings', [SettingsController::class, 'update']);
});
```

### Prefixed Groups

Add a prefix to all routes in a group:

```php
$router->prefix('/admin')->group(function($router) {
    // Matches: /admin/users
    $router->get('/users', [AdminUserController::class, 'index']);
    
    // Matches: /admin/posts
    $router->get('/posts', [AdminPostController::class, 'index']);
    
    // Matches: /admin/settings
    $router->get('/settings', [AdminSettingsController::class, 'index']);
});
```

### Middleware Groups

Apply middleware to all routes in a group:

```php
use App\Middleware\AuthMiddleware;

$router->middleware([AuthMiddleware::class])->group(function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->post('/logout', [AuthController::class, 'logout']);
});
```

### Combining Group Attributes

Combine prefix, middleware, and other attributes:

```php
$router
    ->prefix('/api/v1')
    ->middleware([ApiAuthMiddleware::class])
    ->group(function($router) {
        $router->get('/users', [Api\UserController::class, 'index']);
        $router->post('/users', [Api\UserController::class, 'store']);
        $router->get('/users/{id}', [Api\UserController::class, 'show']);
    });
```

### Nested Groups

Groups can be nested for more organization:

```php
// Admin section
$router->prefix('/admin')->middleware([AdminMiddleware::class])->group(function($router) {
    
    // User management
    $router->prefix('/users')->group(function($router) {
        $router->get('/', [Admin\UserController::class, 'index']);
        $router->post('/', [Admin\UserController::class, 'store']);
        $router->get('/{id}', [Admin\UserController::class, 'show']);
    });
    
    // Post management
    $router->prefix('/posts')->group(function($router) {
        $router->get('/', [Admin\PostController::class, 'index']);
        $router->post('/', [Admin\PostController::class, 'store']);
    });
});
```

## Route Middleware

Middleware filters HTTP requests. Apply middleware to specific routes:

### Single Middleware

```php
use App\Middleware\AuthMiddleware;

$router->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware([AuthMiddleware::class]);
```

### Multiple Middleware

Middleware executes in the order specified:

```php
$router->post('/admin/users', [AdminUserController::class, 'store'])
    ->middleware([
        AuthMiddleware::class,
        AdminMiddleware::class,
        LogRequestMiddleware::class
    ]);
```

### Middleware with Constructor Parameters

Pass parameters to middleware constructors:

```php
use App\Middleware\RoleMiddleware;

$router->get('/admin', [AdminController::class, 'index'])
    ->middleware([new RoleMiddleware('admin')]);
```

### Global Middleware

Apply middleware to all routes (configure in your bootstrap file):

```php
use Lalaz\Security\Middleware\CsrfProtection;
use App\Middleware\LoggingMiddleware;

$router->registerGlobalMiddleware([
    CsrfProtection::class,
    LoggingMiddleware::class
]);
```

## Named Routes

### Defining Named Routes

Give routes memorable names for easier reference:

```php
$router->get('/user/profile', [ProfileController::class, 'show'])
    ->name('profile.show');

$router->post('/user/profile', [ProfileController::class, 'update'])
    ->name('profile.update');
```

### Generating URLs

Generate URLs for named routes:

```php
use Lalaz\Lalaz;

// In a controller or view
$url = Lalaz::router()->route('profile.show');
// Returns: /user/profile

// With parameters
$url = Lalaz::router()->route('user.show', ['id' => 123]);
// Returns: /user/123
```

### Redirecting to Named Routes

```php
return redirect()->route('profile.show');

// With parameters
return redirect()->route('user.show', ['id' => $user->id]);
```

## Route Caching

**Boost your application by 50x with route caching!**

### Why Cache Routes?

- Route resolution: **1ms → 0.02ms** (50x faster)
- Static routes use O(1) hash maps
- Dynamic routes use pre-compiled regex
- Perfect for production environments

### Enabling Route Cache

#### Step 1: Generate Cache

```bash
php lalaz route:cache
```

This creates `storage/cache/routes.php` with optimized route data.

#### Step 2: Enable in Environment

Update `.env`:

```env
ROUTE_CACHE_ENABLED=true
```

### Cache Structure

The cache file contains:

```php
return [
    'hash' => 'abc123...', // Integrity check
    'static' => [
        'GET' => [
            '/' => [...],
            '/about' => [...],
            '/contact' => [...]
        ],
        'POST' => [
            '/contact' => [...]
        ]
    ],
    'dynamic' => [
        [
            'pattern' => '#^/user/([^/]+)$#',
            'params' => ['id'],
            // ... route data
        ]
    ]
];
```

### Clearing Cache

When routes change, clear the cache:

```bash
php lalaz route:cache-clear
```

Or delete manually:

```bash
rm storage/cache/routes.php
```

### Development vs Production

**Development**: Cache disabled for flexibility

```env
ROUTE_CACHE_ENABLED=false
```

**Production**: Cache enabled for performance

```env
ROUTE_CACHE_ENABLED=true
```

### Auto-Regeneration

The cache automatically regenerates when:
- Route definitions change (hash mismatch)
- Cache file is deleted
- Cache is manually cleared

## Route Inspection

### List All Routes

View all registered routes:

```bash
php lalaz routes
```

Output:

```
| #  | Method | URI              | Controller#action              | Has Middlewares |
|----------------------------------------------------------------------------------------|
| 1  | GET    | /                | HomeController#index           | No              |
| 2  | GET    | /about           | PageController#about           | No              |
| 3  | GET    | /users           | UserController#index           | Yes             |
| 4  | POST   | /users           | UserController#store           | Yes             |
| 5  | GET    | /users/{id}      | UserController#show            | No              |
| 6  | PUT    | /users/{id}      | UserController#update          | Yes             |
| 7  | DELETE | /users/{id}      | UserController#destroy         | Yes             |
```

### Inspect Route Middleware

See which middleware applies to a specific route:

```bash
php lalaz middlewares 3
```

Output:

```
GET /users
-----------------------------------------------------------
- App\Middleware\AuthMiddleware
- App\Middleware\LogRequestMiddleware
```

## Best Practices

### 1. Organize Routes by Resource

Group related routes together:

```php
// User routes
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);

// Post routes
$router->get('/posts', [PostController::class, 'index']);
$router->post('/posts', [PostController::class, 'store']);
// ... etc
```

### 2. Use Route Groups for API Versioning

```php
// API v1
$router->prefix('/api/v1')->group(function($router) {
    $router->get('/users', [Api\V1\UserController::class, 'index']);
});

// API v2
$router->prefix('/api/v2')->group(function($router) {
    $router->get('/users', [Api\V2\UserController::class, 'index']);
});
```

### 3. RESTful Resource Routes

Follow RESTful conventions:

```php
GET    /posts           - List all posts (index)
GET    /posts/{id}      - Show single post (show)
POST   /posts           - Create new post (store)
PUT    /posts/{id}      - Update post (update)
DELETE /posts/{id}      - Delete post (destroy)
```

### 4. Separate Web and API Routes

Keep web and API routes in separate files:

**routes/web.php**:
```php
// Browser-facing routes with CSRF protection
$router->get('/', [HomeController::class, 'index']);
```

**routes/api.php**:
```php
// API routes, JSON responses, no CSRF
$router->prefix('/api')->group(function($router) {
    // API routes here
});
```

### 5. Use Meaningful Route Names

```php
// Good
$router->get('/user/profile', $callback)->name('user.profile.show');
$router->post('/user/profile', $callback)->name('user.profile.update');

// Avoid
$router->get('/user/profile', $callback)->name('route1');
```

### 6. Parameter Validation

Always validate parameters in routes or controllers:

```php
$router->get('/user/{id:\d+}', function($id) {
    // $id is guaranteed to be numeric
    $user = User::find($id);
    
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    return response()->json($user);
});
```

### 7. Cache Routes in Production

Always enable route caching in production:

```bash
php lalaz route:cache
```

Add to deployment script:

```bash
#!/bin/bash
git pull
composer install --no-dev
php lalaz route:cache
php lalaz config:cache
```

## Common Patterns

### CRUD Resource

Complete CRUD for a resource:

```php
$router->prefix('/posts')->group(function($router) {
    $router->get('/', [PostController::class, 'index']);           // List
    $router->get('/create', [PostController::class, 'create']);    // Show form
    $router->post('/', [PostController::class, 'store']);          // Create
    $router->get('/{id}', [PostController::class, 'show']);        // Show
    $router->get('/{id}/edit', [PostController::class, 'edit']);   // Edit form
    $router->put('/{id}', [PostController::class, 'update']);      // Update
    $router->delete('/{id}', [PostController::class, 'destroy']);  // Delete
});
```

### Nested Resources

Handle nested relationships:

```php
// Posts and their comments
$router->get('/posts/{postId}/comments', [CommentController::class, 'index']);
$router->post('/posts/{postId}/comments', [CommentController::class, 'store']);
$router->delete('/posts/{postId}/comments/{id}', [CommentController::class, 'destroy']);
```

### Authentication Routes

Common auth pattern:

```php
// Public routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);

// Protected routes
$router->middleware([AuthMiddleware::class])->group(function($router) {
    $router->post('/logout', [AuthController::class, 'logout']);
    $router->get('/dashboard', [DashboardController::class, 'index']);
});
```

## Performance Tips

1. **Use Route Caching** - 50x faster route resolution
2. **Limit Middleware** - Only use necessary middleware per route
3. **Use Parameter Constraints** - Reject invalid requests early
4. **Group Routes Efficiently** - Minimize regex compilation
5. **Avoid Closures in Production** - Use controller methods for cacheability

## Troubleshooting

### Route Not Found

Check route is registered:

```bash
php lalaz routes
```

### Middleware Not Executing

Verify middleware order:

```bash
php lalaz middlewares <route-index>
```

### Cache Not Updating

Clear and regenerate:

```bash
php lalaz route:cache-clear
php lalaz route:cache
```

### Parameter Not Matching

Check regex pattern:

```php
// This won't match '/user/abc'
$router->get('/user/{id:\d+}', $callback);

// This will match both '/user/123' and '/user/abc'
$router->get('/user/{id}', $callback);
```

---

**Next**: Learn about [Controllers](03-controllers.md) to handle your routes! →
