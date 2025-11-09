# Validation

Validate incoming requests with intuitive rules and automatic error handling.

## Quick Start

```php
use Lalaz\Http\Request;
use Lalaz\Exceptions\ValidationException;

public function store(Request $request)
{
    $data = $request->validate([
        'name' => 'required|min:3|max:255',
        'email' => 'required|email|unique:users,email',
        'age' => 'required|int|min:18',
        'password' => 'required|min:8|confirmed'
    ]);
    
    // Validation passed, create user
    User::create($data);
    
    return Response::redirect('/users');
}
```

## Available Rules

### required
Field must be present and not empty:
```php
'name' => 'required'
```

### email
Must be valid email format:
```php
'email' => 'email'
```

### min / max
Minimum/maximum value or length:
```php
'age' => 'min:18|max:99',
'name' => 'min:3|max:100',
'price' => 'min:0.01'
```

### int / numeric
Must be integer or numeric:
```php
'age' => 'int',
'price' => 'numeric'
```

### string
Must be string:
```php
'name' => 'string'
```

### confirmed
Must match `{field}_confirmation`:
```php
'password' => 'confirmed'
// Expects 'password_confirmation' field
```

### unique
Must be unique in database table:
```php
'email' => 'unique:users,email',
'username' => 'unique:users,username,{id}' // Except ID
```

### in
Must be in list of values:
```php
'status' => 'in:draft,published,archived',
'role' => 'in:admin,user,guest'
```

### url
Must be valid URL:
```php
'website' => 'url'
```

### date
Must be valid date:
```php
'birth_date' => 'date',
'published_at' => 'date|after:today'
```

### regex
Must match regex pattern:
```php
'phone' => 'regex:/^\d{3}-\d{3}-\d{4}$/',
'code' => 'regex:/^[A-Z0-9]{6}$/'
```

## Validation Examples

### User Registration

```php
public function register(Request $request)
{
    $data = $request->validate([
        'name' => 'required|string|min:2|max:100',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'age' => 'required|int|min:18',
        'terms' => 'required' // Checkbox acceptance
    ]);
    
    $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    $user = User::create($data);
    
    return Response::redirect('/dashboard');
}
```

### Update Profile

```php
public function update(Request $request, int $id)
{
    $user = User::findOrFail($id);
    
    $data = $request->validate([
        'name' => 'required|string|min:2|max:100',
        'email' => 'required|email|unique:users,email,' . $id, // Exclude current user
        'bio' => 'string|max:500',
        'website' => 'url'
    ]);
    
    $user->update($data);
    
    return Response::redirect('/profile');
}
```

### File Upload

```php
public function upload(Request $request)
{
    $data = $request->validate([
        'file' => 'required',
        'title' => 'required|string|max:255'
    ]);
    
    $file = $request->file('file');
    
    if ($file && $file->isValid()) {
        $path = $file->move('uploads');
        
        Document::create([
            'title' => $data['title'],
            'path' => $path
        ]);
    }
    
    return Response::redirect('/documents');
}
```

## Error Handling

### Automatic Redirection

On validation failure, automatically redirects back with errors and old input:

```php
try {
    $data = $request->validate([...]);
} catch (ValidationException $e) {
    // Automatically redirects with:
    // - Errors in session
    // - Old input values
}
```

### Display Errors in Views

```php
<!-- Check for specific error -->
<?php if (has_error('email')): ?>
    <span class="error"><?= error('email') ?></span>
<?php endif; ?>

<!-- Display all errors -->
<?php if (!empty($errors)): ?>
    <ul class="errors">
        <?php foreach ($errors as $field => $message): ?>
            <li><?= esc($message) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
```

### Twig Templates

```twig
{% if has_error('email') %}
    <span class="error">{{ error('email') }}</span>
{% endif %}

{% if errors %}
    <ul class="errors">
        {% for field, message in errors %}
            <li>{{ message }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

## Custom Error Messages

```php
$data = $request->validate([
    'email' => 'required|email'
], [
    'email.required' => 'Please provide your email address',
    'email.email' => 'Email format is invalid'
]);
```

## Manual Validation

```php
use Lalaz\Http\Validation\Validator;

$validator = new Validator($request->all(), [
    'email' => 'required|email',
    'age' => 'required|int|min:18'
]);

if ($validator->fails()) {
    $errors = $validator->errors();
    
    return Response::json([
        'message' => 'Validation failed',
        'errors' => $errors
    ], 422);
}

$data = $validator->validated();
```

## API Validation

```php
public function store(Request $request)
{
    try {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'published' => 'required|in:0,1'
        ]);
        
        $post = Post::create($data);
        
        return Response::json($post, 201);
        
    } catch (ValidationException $e) {
        return Response::json([
            'message' => 'Validation failed',
            'errors' => $e->errors
        ], 422);
    }
}
```

## Form Object Pattern

### Create Form Class

```php
<?php

namespace App\Forms;

use Lalaz\Http\Request;

class UserForm
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ];
    }
    
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.email' => 'Invalid email format',
            'password.min' => 'Password must be at least 8 characters'
        ];
    }
    
    public function validate(Request $request): array
    {
        return $request->validate($this->rules(), $this->messages());
    }
}
```

### Use Form Class

```php
public function store(Request $request)
{
    $form = new UserForm();
    $data = $form->validate($request);
    
    User::create($data);
    
    return Response::redirect('/users');
}
```

## Best Practices

### Validate Early

```php
// ✅ Good - validate immediately
public function store(Request $request)
{
    $data = $request->validate([...]);
    User::create($data);
}

// ❌ Bad - validation too late
public function store(Request $request)
{
    $user = new User();
    $user->email = $request->input('email');
    // What if email is invalid?
    $user->save();
}
```

### Use Form Objects

```php
// ✅ Good - reusable form
$data = (new UserForm())->validate($request);

// ❌ Bad - repeated validation
$data = $request->validate([...]);  // In store()
$data = $request->validate([...]);  // In update()
```

### Type Hints

```php
// ✅ Good - type safety
'age' => 'required|int|min:18',
'price' => 'required|numeric|min:0'

// ❌ Bad - no type validation
'age' => 'required',
'price' => 'required'
```

---

**Next**: [Security](09-security.md) →
