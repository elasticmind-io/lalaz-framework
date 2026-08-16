# Performance Optimization

Make your Lalaz application blazing fast with these proven optimization strategies.

## Router Cache (50x Faster)

Pre-compile routes for production to achieve **50x performance boost** (1ms → 0.02ms).

### Enable Route Caching

```bash
php lalaz route:cache
```

Output:
```
🔄 Compiling routes...
✅ Router cache created successfully!
📁 Cache file: storage/cache/routes.php
📊 Cached 42 route(s)
```

### Configure in .env

```env
ROUTE_CACHE_ENABLED=true
```

### Clear Cache

```bash
php lalaz route:cache-clear
```

**Benchmark**: Reduces routing time from ~1ms to ~0.02ms (50x faster).

## Config Cache (300x Faster)

Pre-compile configuration for **300x performance boost** (2-3ms → 0.01ms).

### Enable Config Caching

```bash
php lalaz config:cache
```

Output:
```
⚡ Compiling config cache...
✅ Config cached successfully!
📁 Cache file: storage/cache/config.php
📊 Cached 24 config variable(s)
```

### Configure in .env

```env
CONFIG_CACHE_ENABLED=true
```

### Clear Cache

```bash
php lalaz config:cache-clear
```

**Benchmark**: Reduces config loading from ~2-3ms to ~0.01ms (300x faster).

## Database Optimization

### Use Query Builder Efficiently

```php
// ✅ Good - single query with eager loading
$users = User::with('posts')
    ->where('active', true)
    ->limit(10)
    ->get();

// ❌ Bad - N+1 query problem
$users = User::where('active', true)->limit(10)->get();
foreach ($users as $user) {
    $posts = $user->posts()->get(); // Extra query per user
}
```

### Index Database Columns

```php
// In migration
$table->string('email')->unique();
$table->index('user_id');
$table->index(['user_id', 'created_at']);
```

### Use Pagination

```php
// ✅ Good - paginated results
$users = User::paginate(20);

// ❌ Bad - loads everything
$users = User::all(); // 10,000 records
```

### Optimize Queries

```php
// ✅ Good - only needed columns
$users = User::select(['id', 'name', 'email'])->get();

// ❌ Bad - selects everything
$users = User::all();
```

### Use Transactions

```php
// ✅ Good - atomic operation
DB::transaction(function() {
    $order = Order::create([...]);
    Inventory::decrement('stock', 1);
    Payment::create([...]);
});

// ❌ Bad - separate operations
$order = Order::create([...]);
Inventory::decrement('stock', 1);
Payment::create([...]);
```

## Caching Strategies

### Cache Expensive Operations

```php
use Lalaz\Core\Cache;

public function getStats()
{
    return Cache::remember('dashboard.stats', 3600, function() {
        return [
            'users' => User::count(),
            'orders' => Order::count(),
            'revenue' => Order::sum('total')
        ];
    });
}
```

### Cache API Responses

```php
public function getWeather(string $city)
{
    $cacheKey = "weather.{$city}";
    
    return Cache::remember($cacheKey, 1800, function() use ($city) {
        return $this->weatherApi->fetch($city);
    });
}
```

### Invalidate Cache

```php
// Clear specific cache
Cache::forget('dashboard.stats');

// Clear all cache
Cache::flush();
```

## Asset Optimization

### Combine & Minify Assets

```bash
# Install tools
npm install -D vite

# Build for production
npm run build
```

`vite.config.js`:
```javascript
export default {
    build: {
        minify: 'terser',
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'axios']
                }
            }
        }
    }
}
```

### Use CDN

```html
<!-- ✅ Good - CDN -->
<script src="https://cdn.jsdelivr.net/npm/vue@3"></script>

<!-- ❌ Bad - local large file -->
<script src="/js/vue.js"></script>
```

### Optimize Images

```bash
# Install optimizer
npm install -D imagemin imagemin-webp

# Convert to WebP
imagemin images/*.{jpg,png} --plugin=webp --out-dir=images/optimized
```

```html
<picture>
    <source srcset="image.webp" type="image/webp">
    <img src="image.jpg" alt="Fallback">
</picture>
```

## HTTP Optimization

### Enable Compression

In nginx:

```nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
gzip_comp_level 6;
```

### Enable Browser Caching

```nginx
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### Use HTTP/2

```nginx
server {
    listen 443 ssl http2;
    # ...
}
```

## PHP Optimization

### Use OPcache

In `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

Verify:

```bash
php -i | grep opcache.enable
```

### Optimize Autoloader

```bash
composer dump-autoload --optimize --classmap-authoritative
```

### Use PHP 8.2+

PHP 8.2 provides ~25% performance improvement over PHP 7.4.

## Session Optimization

### Use Database/Redis Sessions

In `.env`:

```env
# Instead of file sessions
SESSION_DRIVER=database

# Or Redis (fastest)
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Queue Optimization

### Run Multiple Workers

```bash
# Run 4 workers in parallel
php lalaz jobs:run & \
php lalaz jobs:run & \
php lalaz jobs:run & \
php lalaz jobs:run
```

Or with Supervisor:

```ini
[program:lalaz-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/lalaz jobs:run
numprocs=4
```

## Code Optimization

### Lazy Loading

```php
use Lalaz\Core\LazyValue;

class ExpensiveService
{
    private LazyValue $heavyResource;
    
    public function __construct()
    {
        $this->heavyResource = new LazyValue(function() {
            return $this->loadHeavyResource();
        });
    }
    
    public function use()
    {
        return $this->heavyResource->get(); // Only loaded when needed
    }
}
```

### Minimize Middleware

```php
// ✅ Good - only needed middleware
$router->get('/api/users', [...])
    ->middleware([AuthMiddleware::class]);

// ❌ Bad - unnecessary middleware
$router->get('/api/users', [...])
    ->middleware([
        LogMiddleware::class,
        CorsMiddleware::class,
        AuthMiddleware::class,
        RateLimitMiddleware::class,
        AnalyticsMiddleware::class
    ]);
```

### Avoid Heavy Constructors

```php
// ✅ Good - lazy initialization
class UserService
{
    private ?Database $db = null;
    
    private function getDb(): Database
    {
        return $this->db ??= app('database');
    }
}

// ❌ Bad - always initializes
class UserService
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = new Database();
    }
}
```

## Monitoring Performance

### Measure Response Times

```php
class PerformanceMiddleware extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = round((microtime(true) - $start) * 1000, 2);
        
        $response->withHeader('X-Response-Time', $duration . 'ms');
        
        if ($duration > 1000) {
            Log::warning('Slow request', [
                'uri' => $request->uri,
                'duration_ms' => $duration
            ]);
        }
        
        return $response;
    }
}
```

### Profiling

```php
// Enable Xdebug profiler
// php.ini:
xdebug.mode=profile
xdebug.output_dir=/tmp/xdebug

// Analyze with tools like Webgrind or QCacheGrind
```

## Production Checklist

```bash
# 1. Enable OPcache
php -i | grep opcache.enable

# 2. Optimize autoloader
composer dump-autoload --optimize --classmap-authoritative

# 3. Cache routes
php lalaz route:cache

# 4. Cache config
php lalaz config:cache

# 5. Set production environment
# .env
APP_ENV=production
APP_DEBUG=false
ROUTE_CACHE_ENABLED=true
CONFIG_CACHE_ENABLED=true

# 6. Enable compression
# nginx: gzip on;

# 7. Optimize database
# Add indexes, analyze queries

# 8. Run workers
supervisorctl start lalaz-worker:*

# 9. Enable browser caching
# nginx: expires headers

# 10. Monitor performance
tail -f storage/logs/app-*.log | grep "Slow"
```

## Performance Benchmarks

| Optimization | Before | After | Improvement |
|--------------|--------|-------|-------------|
| Route Cache | 1ms | 0.02ms | **50x** |
| Config Cache | 2-3ms | 0.01ms | **300x** |
| OPcache | 100ms | 75ms | **1.33x** |
| Autoloader Optimization | 10ms | 3ms | **3.3x** |
| HTTP/2 | 500ms | 200ms | **2.5x** |
| Gzip Compression | 500KB | 100KB | **5x smaller** |

## Best Practices

### 1. Cache Everything Cacheable

```php
// ✅ Good
Cache::remember('key', 3600, fn() => expensiveOperation());

// ❌ Bad
expensiveOperation(); // Every request
```

### 2. Optimize Database Queries

```php
// ✅ Good
User::select(['id', 'name'])->with('posts')->limit(10)->get();

// ❌ Bad
User::all();
```

### 3. Use Async Jobs

```php
// ✅ Good
job(SendEmailJob::class, ['email' => $email]);

// ❌ Bad
mail($email, ...); // Blocks response
```

### 4. Enable All Caches in Production

```env
ROUTE_CACHE_ENABLED=true
CONFIG_CACHE_ENABLED=true
opcache.enable=1
```

---

**Next**: [Deployment Guide](14-deployment.md) →
