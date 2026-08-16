<?php declare(strict_types=1);

use Lalaz\Routing\Router;
use Lalaz\Http\Controller;
use Lalaz\Core\Config;

// Mock controllers for testing
class MockHomeController extends Controller {
    public function index($req, $res) {}
}

class MockUserController extends Controller {
    public function index($req, $res) {}
    public function store($req, $res) {}
    public function show($req, $res) {}
    public function update($req, $res) {}
    public function destroy($req, $res) {}
}

class MockAdminController extends Controller {
    public function index($req, $res) {}
}

class MockCommentController extends Controller {
    public function show($req, $res) {}
}

beforeEach(function () {
    $this->cacheFile = __DIR__ . '/../../../storage/cache/test_routes.php';
    if (file_exists($this->cacheFile)) {
        unlink($this->cacheFile);
    }
    Config::set('ROUTE_CACHE_ENABLED', false);

    // Helper to register routes bypassing controller lookup
    $this->registerRoute = function(Router $router, string $method, string $path, string $controller, string $function, array $middlewares = []) {
        $reflection = new \ReflectionClass($router);
        $mapMethod = $reflection->getMethod('map');
        $mapMethod->setAccessible(true);
        return $mapMethod->invoke($router, $method, $path, $controller, $function, $middlewares);
    };
});

afterEach(function () {
    if (file_exists($this->cacheFile)) {
        unlink($this->cacheFile);
    }
});

test('router cache is disabled by default', function () {
    $router = new Router();
    expect($router)->toBeInstanceOf(Router::class);
});

test('can set cache file path', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);
    expect($this->cacheFile)->toBeString();
});

test('loadFromCache returns false when cache does not exist', function () {
    Config::set('ROUTE_CACHE_ENABLED', true);
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    $result = $router->loadFromCache();

    expect($result)->toBeFalse();
});

test('loadFromCache returns false when cache is disabled', function () {
    Config::set('ROUTE_CACHE_ENABLED', false);
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    file_put_contents($this->cacheFile, "<?php return ['static' => [], 'dynamic' => []];");

    $result = $router->loadFromCache();

    expect($result)->toBeFalse();
});

test('can save routes to cache', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/', MockHomeController::class, 'index');
    ($this->registerRoute)($router, 'POST', '/users', MockUserController::class, 'store');

    $result = $router->saveToCache();

    expect($result)->toBeTrue();
    expect(file_exists($this->cacheFile))->toBeTrue();
});

test('cached file contains expected structure', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/', MockHomeController::class, 'index');
    ($this->registerRoute)($router, 'POST', '/users', MockUserController::class, 'store');
    ($this->registerRoute)($router, 'GET', '/users/{id}', MockUserController::class, 'show');

    $router->saveToCache();
    $cache = require $this->cacheFile;

    expect($cache)->toHaveKeys(['static', 'dynamic', 'hash', 'count']);
    expect($cache['static'])->toBeArray();
    expect($cache['dynamic'])->toBeArray();
    expect($cache['hash'])->toBeString();
    expect($cache['count'])->toBe(3);
});

test('static routes are stored in hash map', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/', MockHomeController::class, 'index');
    ($this->registerRoute)($router, 'POST', '/api/users', MockUserController::class, 'store');

    $router->saveToCache();
    $cache = require $this->cacheFile;

    expect($cache['static'])->toHaveKey('GET:/');
    expect($cache['static'])->toHaveKey('POST:/api/users');
    expect($cache['static']['GET:/'])->toHaveKeys(['controller', 'function', 'middlewares', 'params']);
});

test('dynamic routes are stored with pre-compiled regex', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/users/{id}', MockUserController::class, 'show');
    ($this->registerRoute)($router, 'GET', '/posts/{slug}/comments/{id}', MockCommentController::class, 'show');

    $router->saveToCache();
    $cache = require $this->cacheFile;

    expect($cache['dynamic'])->toHaveCount(2);
    expect($cache['dynamic'][0])->toHaveKey('pattern');
    expect($cache['dynamic'][0]['pattern'])->toBe('#^/users/([^/]+)$#');
    expect($cache['dynamic'][0]['params'])->toBe(['id']);
});

test('can load routes from cache', function () {
    Config::set('ROUTE_CACHE_ENABLED', true);

    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/', MockHomeController::class, 'index');
    ($this->registerRoute)($router, 'POST', '/users', MockUserController::class, 'store');

    $router->saveToCache();

    $router2 = new Router();
    $router2->setCacheFile($this->cacheFile);
    $result = $router2->loadFromCache();

    expect($result)->toBeTrue();
});

test('cache hash changes when routes change', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/', MockHomeController::class, 'index');

    $router->saveToCache();
    $cache1 = require $this->cacheFile;
    $hash1 = $cache1['hash'];

    unlink($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/', MockHomeController::class, 'index');
    ($this->registerRoute)($router, 'POST', '/users', MockUserController::class, 'store');

    $router->saveToCache();
    $cache2 = require $this->cacheFile;
    $hash2 = $cache2['hash'];

    expect($hash1)->not->toBe($hash2);
});

test('clearCache removes cache file', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/', MockHomeController::class, 'index');

    $router->saveToCache();
    expect(file_exists($this->cacheFile))->toBeTrue();

    $result = $router->clearCache();

    expect($result)->toBeTrue();
    expect(file_exists($this->cacheFile))->toBeFalse();
});

test('clearCache returns false when no cache exists', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    $result = $router->clearCache();

    expect($result)->toBeFalse();
});

test('middlewares are serialized correctly', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/protected', MockAdminController::class, 'index', ['AuthMiddleware']);

    $router->saveToCache();
    $cache = require $this->cacheFile;

    expect($cache['static']['GET:/protected']['middlewares'])->toBeArray();
    expect($cache['static']['GET:/protected']['middlewares'][0])->toHaveKey('type');
    expect($cache['static']['GET:/protected']['middlewares'][0])->toHaveKey('class');
});

test('saveToCache creates cache directory if not exists', function () {
    $nestedCacheFile = __DIR__ . '/../../../storage/cache/nested/test_routes.php';
    $nestedDir = dirname($nestedCacheFile);

    if (file_exists($nestedCacheFile)) {
        unlink($nestedCacheFile);
    }
    if (is_dir($nestedDir)) {
        rmdir($nestedDir);
    }

    $router = new Router();
    $router->setCacheFile($nestedCacheFile);

    ($this->registerRoute)($router, 'GET', '/', MockHomeController::class, 'index');

    $result = $router->saveToCache();

    expect($result)->toBeTrue();
    expect(file_exists($nestedCacheFile))->toBeTrue();
    expect(is_dir($nestedDir))->toBeTrue();

    unlink($nestedCacheFile);
    rmdir($nestedDir);
});

test('saveToCache returns false on error', function () {
    $router = new Router();

    ($this->registerRoute)($router, 'GET', '/', MockHomeController::class, 'index');

    $result = $router->saveToCache();

    expect($result)->toBeFalse();
});

test('loadFromCache validates cache structure', function () {
    Config::set('ROUTE_CACHE_ENABLED', true);

    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    file_put_contents($this->cacheFile, "<?php return ['invalid' => 'structure'];");

    $result = $router->loadFromCache();

    expect($result)->toBeFalse();
});

test('cache file has readable format', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/', MockHomeController::class, 'index');

    $router->saveToCache();

    $content = file_get_contents($this->cacheFile);

    expect($content)->toContain('<?php');
    expect($content)->toContain('auto-generated');
    expect($content)->toContain('Generated at:');
    expect($content)->toContain('return');
});

test('multiple routes with same path but different methods are cached separately', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/users', MockUserController::class, 'index');
    ($this->registerRoute)($router, 'POST', '/users', MockUserController::class, 'store');
    ($this->registerRoute)($router, 'PUT', '/users', MockUserController::class, 'update');
    ($this->registerRoute)($router, 'DELETE', '/users', MockUserController::class, 'destroy');

    $router->saveToCache();
    $cache = require $this->cacheFile;

    expect($cache['static'])->toHaveKey('GET:/users');
    expect($cache['static'])->toHaveKey('POST:/users');
    expect($cache['static'])->toHaveKey('PUT:/users');
    expect($cache['static'])->toHaveKey('DELETE:/users');
    expect($cache['count'])->toBe(4);
});

test('routes with multiple parameters are cached with correct pattern', function () {
    $router = new Router();
    $router->setCacheFile($this->cacheFile);

    ($this->registerRoute)($router, 'GET', '/users/{userId}/posts/{postId}/comments/{commentId}', MockCommentController::class, 'show');

    $router->saveToCache();
    $cache = require $this->cacheFile;

    expect($cache['dynamic'][0]['pattern'])->toBe('#^/users/([^/]+)/posts/([^/]+)/comments/([^/]+)$#');
    expect($cache['dynamic'][0]['params'])->toBe(['userId', 'postId', 'commentId']);
});
