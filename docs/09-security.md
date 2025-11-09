# Security

Lalaz provides multiple layers of security to protect your application from common vulnerabilities.

## CSRF Protection

Cross-Site Request Forgery (CSRF) protection prevents unauthorized commands from being transmitted from a user that the application trusts.

### Enable CSRF Middleware

```php
use Lalaz\Security\Middleware\CsrfMiddleware;

$router->post('/users', [UserController::class, 'store'])
    ->middleware(CsrfMiddleware::class);
```

### Add CSRF Token to Forms

```html
<form method="POST" action="/users">
    <?= csrf_field() ?>
    <!-- form fields -->
</form>
```

This generates:

```html
<input type="hidden" name="_token" value="random_token_here">
```

### Twig Templates

```twig
<form method="POST" action="/users">
    {{ csrf_field() }}
    <!-- form fields -->
</form>
```

### AJAX Requests

```javascript
const token = document.querySelector('meta[name="csrf-token"]').content;

fetch('/api/users', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
    },
    body: JSON.stringify(data)
});
```

Add meta tag to layout:

```html
<meta name="csrf-token" content="<?= csrf_token() ?>">
```

## Security Headers

Protect against XSS, clickjacking, and other attacks with HTTP security headers.

### Apply Security Headers Middleware

```php
use Lalaz\Security\Middleware\SecurityHeadersMiddleware;

$router->group(['middleware' => [SecurityHeadersMiddleware::class]], function($router) {
    // All routes protected
});
```

### Security Presets

#### Minimal (Default)

```php
SecurityHeadersMiddleware::usePreset('minimal');
```

Headers:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`

#### Recommended

```php
SecurityHeadersMiddleware::usePreset('recommended');
```

Additional headers:
- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `Referrer-Policy: strict-origin-when-cross-origin`

#### Strict

```php
SecurityHeadersMiddleware::usePreset('strict');
```

Additional headers:
- `Content-Security-Policy: default-src 'self'`
- `Permissions-Policy: geolocation=(), microphone=(), camera=()`

#### API

```php
SecurityHeadersMiddleware::usePreset('api');
```

Optimized for APIs:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- No CSP (not needed for APIs)

### Custom Headers

```php
class CustomSecurityMiddleware extends SecurityHeadersMiddleware
{
    protected function getHeaders(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'"
        ];
    }
}
```

## Password Hashing

### Hash Passwords

```php
use Lalaz\Security\Hashing;

$hashedPassword = Hashing::make('secret123');
```

Uses bcrypt with automatic salt generation.

### Verify Passwords

```php
if (Hashing::verify('secret123', $hashedPassword)) {
    // Password correct
}
```

### CLI Hash Command

```bash
php lalaz hash:password mypassword
```

Output:
```
$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

## Input Sanitization

### Escape Output

Always escape user input before displaying:

```php
<?= esc($userInput) ?>
<?= e($userInput) ?> <!-- Alias -->
```

### Sanitize Input

```php
$cleanInput = htmlspecialchars($request->input('comment'), ENT_QUOTES, 'UTF-8');
```

### Validation

Validate all input:

```php
$data = $request->validate([
    'email' => 'required|email',
    'age' => 'required|int|min:18',
    'website' => 'url'
]);
```

## SQL Injection Protection

### Use Query Builder

```php
// ✅ Safe - parameterized query
User::where('email', $email)->first();

// ✅ Safe - parameter binding
$db->query('SELECT * FROM users WHERE email = ?', [$email]);
```

### Avoid Raw Queries

```php
// ❌ DANGEROUS - SQL injection risk
$db->query("SELECT * FROM users WHERE email = '$email'");
```

If raw queries are needed, always bind parameters:

```php
// ✅ Safe
$db->query('SELECT * FROM users WHERE email = ?', [$email]);
```

## Session Security

### Configure Sessions

In `.env`:

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

### Session Regeneration

Regenerate session ID after login:

```php
public function login(Request $request)
{
    $data = $request->validate([...]);
    
    $user = User::where('email', $data['email'])->first();
    
    if ($user && Hashing::verify($data['password'], $user->password)) {
        $request->session->regenerate(); // Prevent session fixation
        $request->session->set('user_id', $user->id);
        
        return Response::redirect('/dashboard');
    }
    
    return Response::back()->with('error', 'Invalid credentials');
}
```

### Clear Session on Logout

```php
public function logout(Request $request)
{
    $request->session->destroy();
    
    return Response::redirect('/login');
}
```

## Authentication Example

### Login Controller

```php
<?php

namespace App\Controllers;

use Lalaz\Http\Controller;
use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Security\Hashing;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Response::view('auth/login');
    }
    
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        $user = User::where('email', $data['email'])->first();
        
        if (!$user || !Hashing::verify($data['password'], $user->password)) {
            return Response::back()->with('error', 'Invalid credentials');
        }
        
        // Regenerate session to prevent session fixation
        $request->session->regenerate();
        $request->session->set('user_id', $user->id);
        
        return Response::redirect('/dashboard');
    }
    
    public function logout(Request $request)
    {
        $request->session->destroy();
        
        return Response::redirect('/login');
    }
}
```

### Auth Middleware

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

### Protected Routes

```php
use App\Middleware\AuthMiddleware;

$router->group(['middleware' => [AuthMiddleware::class]], function($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->post('/logout', [AuthController::class, 'logout']);
});
```

## File Upload Security

### Validate File Type

```php
public function upload(Request $request)
{
    $file = $request->file('avatar');
    
    if (!$file || !$file->isValid()) {
        return Response::back()->with('error', 'Invalid file');
    }
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file->getMimeType(), $allowedMimes)) {
        return Response::back()->with('error', 'Invalid file type');
    }
    
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if ($file->getSize() > $maxSize) {
        return Response::back()->with('error', 'File too large');
    }
    
    $path = $file->move('uploads/avatars');
    
    // Update user avatar
    $user = User::find($request->session->get('user_id'));
    $user->avatar = $path;
    $user->save();
    
    return Response::redirect('/profile');
}
```

## Environment Variables

### Never Commit Secrets

```bash
# ✅ Good - in .env (gitignored)
DB_PASSWORD=secret123
API_KEY=abc123xyz

# ❌ Bad - committed to git
# config/database.php with hardcoded password
```

### Access Environment Variables

```php
$dbPassword = env('DB_PASSWORD');
$apiKey = env('API_KEY', 'default_value');
```

## Best Practices

### 1. Always Escape Output

```php
<!-- ✅ Good -->
<p><?= esc($userInput) ?></p>

<!-- ❌ Bad -->
<p><?= $userInput ?></p>
```

### 2. Use CSRF Protection

```php
// ✅ Good
<form method="POST">
    <?= csrf_field() ?>
</form>

// ❌ Bad
<form method="POST">
    <!-- No CSRF token -->
</form>
```

### 3. Validate All Input

```php
// ✅ Good
$data = $request->validate([...]);

// ❌ Bad
$email = $request->input('email');
// No validation
```

### 4. Hash Passwords

```php
// ✅ Good
$user->password = Hashing::make($password);

// ❌ Bad
$user->password = $password; // Plain text
```

### 5. Use Parameterized Queries

```php
// ✅ Good
User::where('email', $email)->first();

// ❌ Bad
$db->query("SELECT * FROM users WHERE email = '$email'");
```

### 6. Regenerate Sessions

```php
// ✅ Good - after login
$request->session->regenerate();

// ❌ Bad - session fixation vulnerability
// Don't regenerate
```

### 7. Use Security Headers

```php
// ✅ Good
->middleware(SecurityHeadersMiddleware::class)

// ❌ Bad
// No security headers
```

---

**Next**: [Logging](10-logging.md) →
