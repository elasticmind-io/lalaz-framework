# Controllers

Controllers organize your request handling logic into classes. Instead of defining all request handling logic in route closures, you can organize this behavior using controller classes.

## Creating Controllers

### Using the CLI

Generate a controller with the CLI:

```bash
php lalaz g:controller UserController
```

This creates `app/Controllers/UserController.php`:

```php
<?php

namespace App\Controllers;

use Lalaz\Http\Controller;
use Lalaz\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Handle request
    }
}
```

### Manual Creation

Create a controller manually:

```php
<?php

namespace App\Controllers;

use Lalaz\Http\Controller;
use Lalaz\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::all();
        return $this->json($products);
    }
    
    public function show(Request $request, $id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }
        
        return $this->json($product);
    }
    
    public function store(Request $request)
    {
        $product = Product::create($request->all());
        return $this->json($product, 201);
    }
}
```

## Routing to Controllers

### Basic Controller Routing

```php
use App\Controllers\UserController;

$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
$router->get('/users/{id}', [UserController::class, 'show']);
```

### Route Parameters

Parameters are automatically injected:

```php
// Route
$router->get('/posts/{id}/comments/{comment}', [CommentController::class, 'show']);

// Controller
public function show(Request $request, $id, $comment)
{
    return "Post: {$id}, Comment: {$comment}";
}
```

## Dependency Injection

Controllers support automatic dependency injection:

```php
use Lalaz\Data\Database;
use Lalaz\Logging\Logger;

class UserController extends Controller
{
    private Database $db;
    private Logger $logger;
    
    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    public function index(Request $request)
    {
        $this->logger->info('Fetching users');
        $users = $this->db->table('users')->get();
        return $this->json($users);
    }
}
```

## Request Handling

### Accessing Request Data

```php
public function store(Request $request)
{
    // All input
    $all = $request->all();
    
    // Specific field
    $email = $request->input('email');
    
    // With default value
    $name = $request->input('name', 'Guest');
    
    // Check if field exists
    if ($request->has('email')) {
        // ...
    }
    
    // Get specific fields only
    $data = $request->only(['name', 'email']);
    
    // Get all except specific fields
    $data = $request->except(['_token', 'password_confirmation']);
}
```

### Query Parameters

```php
public function index(Request $request)
{
    // Get query parameter: /users?page=2
    $page = $request->query('page', 1);
    
    // All query parameters
    $queryParams = $request->query();
}
```

### File Uploads

```php
public function upload(Request $request)
{
    if ($request->hasFile('avatar')) {
        $file = $request->file('avatar');
        
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        
        // Move to permanent location
        $file->move('/uploads/avatars/', $fileName);
    }
}
```

## Responses

### JSON Responses

```php
// Simple JSON
public function index()
{
    return $this->json(['message' => 'Hello']);
}

// With status code
public function store()
{
    return $this->json(['created' => true], 201);
}

// Error response
public function show($id)
{
    return $this->json(['error' => 'Not found'], 404);
}
```

### View Responses

```php
public function index()
{
    return $this->view('users.index', [
        'users' => User::all(),
        'title' => 'User List'
    ]);
}
```

### Redirect Responses

```php
// Simple redirect
public function store()
{
    return $this->redirect('/users');
}

// Redirect with flash message
public function update()
{
    session()->flash('success', 'User updated!');
    return $this->redirect('/users');
}

// Redirect to named route
public function destroy()
{
    return $this->redirect()->route('users.index');
}
```

### Plain Text Responses

```php
public function health()
{
    return $this->text('OK', 200);
}
```

### Download Responses

```php
public function download()
{
    $filePath = storage_path('reports/report.pdf');
    return $this->download($filePath, 'monthly-report.pdf');
}
```

## RESTful Resource Controllers

Implement full CRUD operations:

```php
class PostController extends Controller
{
    // GET /posts
    public function index(Request $request)
    {
        $posts = Post::all();
        return $this->json($posts);
    }
    
    // GET /posts/{id}
    public function show(Request $request, $id)
    {
        $post = Post::find($id);
        
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }
        
        return $this->json($post);
    }
    
    // POST /posts
    public function store(Request $request)
    {
        $post = Post::create($request->all());
        return $this->json($post, 201);
    }
    
    // PUT /posts/{id}
    public function update(Request $request, $id)
    {
        $post = Post::find($id);
        
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }
        
        $post->update($request->all());
        return $this->json($post);
    }
    
    // DELETE /posts/{id}
    public function destroy(Request $request, $id)
    {
        $post = Post::find($id);
        
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }
        
        $post->delete();
        return $this->json(['message' => 'Post deleted'], 200);
    }
}
```

## Best Practices

### 1. Keep Controllers Thin

Move business logic to services or models:

```php
// Bad
public function store(Request $request)
{
    $user = new User();
    $user->name = $request->input('name');
    $user->email = $request->input('email');
    $user->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
    $user->save();
    
    // Send welcome email
    $mailer = new Mailer();
    $mailer->send($user->email, 'Welcome', 'welcome-template');
    
    // Create user profile
    // ... lots more logic
}

// Good
public function store(Request $request, UserService $userService)
{
    $user = $userService->createUser($request->all());
    return $this->json($user, 201);
}
```

### 2. Use Type Hints

```php
use App\Models\User;
use Lalaz\Http\Request;

public function show(Request $request, int $id): Response
{
    $user = User::find($id);
    return $this->json($user);
}
```

### 3. Validate Input

Always validate before processing:

```php
public function store(Request $request)
{
    $validator = new UserValidator($request->all());
    
    if (!$validator->validate()) {
        return $this->json([
            'errors' => $validator->getErrors()
        ], 422);
    }
    
    $user = User::create($validator->validated());
    return $this->json($user, 201);
}
```

### 4. Handle Exceptions

```php
public function show(Request $request, $id)
{
    try {
        $user = User::findOrFail($id);
        return $this->json($user);
    } catch (ModelNotFoundException $e) {
        return $this->json(['error' => 'User not found'], 404);
    } catch (\Exception $e) {
        $this->logger->error('Error fetching user', ['id' => $id, 'error' => $e->getMessage()]);
        return $this->json(['error' => 'Internal server error'], 500);
    }
}
```

### 5. Use Resource Controllers

Group related actions in one controller:

```php
// Good: UserController handles all user operations
class UserController extends Controller
{
    public function index() { }
    public function show($id) { }
    public function store() { }
    public function update($id) { }
    public function destroy($id) { }
}

// Avoid: Separate controllers for each action
class UserIndexController { }
class UserShowController { }
class UserStoreController { }
```

---

**Next**: Learn about [Middleware](04-middleware.md) →
