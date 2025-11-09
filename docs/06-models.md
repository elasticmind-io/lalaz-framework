# Models & ActiveRecord

Lalaz uses ActiveRecord pattern for elegant database interaction. Models represent database tables and provide intuitive methods for CRUD operations.

## Creating Models

### Generate Model

```bash
php lalaz g:model User
```

Creates `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Lalaz\Data\Model;

class User extends Model
{
    protected string $table = 'users';
    
    protected array $fillable = [];
}
```

## Basic Usage

### Finding Records

```php
// Find by primary key
$user = User::find(1);

// Find or fail (throws exception)
$user = User::findOrFail(1);

// Find by custom column
$user = User::where('email', 'john@example.com')->first();

// Get all records
$users = User::all();

// Get with conditions
$users = User::where('active', true)->get();
```

### Creating Records

```php
// Method 1: Create and save
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();

// Method 2: Mass assignment
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_BCRYPT)
]);
```

### Updating Records

```php
// Method 1: Find and update
$user = User::find(1);
$user->name = 'Jane Doe';
$user->save();

// Method 2: Update directly
User::where('id', 1)->update([
    'name' => 'Jane Doe',
    'updated_at' => date('Y-m-d H:i:s')
]);
```

### Deleting Records

```php
// Delete instance
$user = User::find(1);
$user->delete();

// Delete by condition
User::where('active', false)->delete();

// Delete by ID
User::destroy(1);
User::destroy([1, 2, 3]);
```

## Model Configuration

### Table Name

```php
class User extends Model
{
    protected string $table = 'users'; // Default: lowercase plural
}
```

### Primary Key

```php
class User extends Model
{
    protected string $primaryKey = 'id'; // Default: 'id'
}
```

### Fillable Attributes

```php
class User extends Model
{
    protected array $fillable = [
        'name',
        'email',
        'password'
    ];
}
```

**Mass assignment**:

```php
// Only fillable attributes are set
$user = User::create($request->all());
```

### Hidden Attributes

```php
class User extends Model
{
    protected array $hidden = [
        'password',
        'remember_token'
    ];
}
```

When converting to JSON, hidden attributes are excluded:

```php
return Response::json($user); // password hidden
```

### Timestamps

```php
class User extends Model
{
    protected bool $timestamps = true; // Default: true
    
    // Uses 'created_at' and 'updated_at' columns
}
```

Disable timestamps:

```php
protected bool $timestamps = false;
```

## Relationships

### One-to-Many

```php
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}

class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

**Usage**:

```php
// Get user's posts
$user = User::find(1);
$posts = $user->posts()->get();

// Get post's user
$post = Post::find(1);
$user = $post->user()->first();
```

### Many-to-Many

```php
class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }
}

class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }
}
```

**Usage**:

```php
$user = User::find(1);
$roles = $user->roles()->get();

// Check if user has role
$hasAdminRole = $user->roles()
    ->where('name', 'admin')
    ->exists();
```

### One-to-One

```php
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }
}

class Profile extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

**Usage**:

```php
$user = User::find(1);
$profile = $user->profile()->first();
```

## Query Scopes

### Local Scopes

```php
class User extends Model
{
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    
    public function scopePopular($query)
    {
        return $query->where('followers', '>', 1000);
    }
}
```

**Usage**:

```php
// Get active users
$users = User::active()->get();

// Chain scopes
$users = User::active()->popular()->get();
```

## Accessors & Mutators

### Accessors (Getters)

```php
class User extends Model
{
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
```

**Usage**:

```php
$user = User::find(1);
echo $user->full_name; // Accessor called automatically
```

### Mutators (Setters)

```php
class User extends Model
{
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_BCRYPT);
    }
}
```

**Usage**:

```php
$user = new User();
$user->password = 'secret'; // Automatically hashed
$user->save();
```

## Advanced Queries

### Ordering

```php
$users = User::orderBy('name', 'asc')->get();
$users = User::orderBy('created_at', 'desc')->get();
```

### Limiting

```php
$users = User::limit(10)->get();
$users = User::offset(10)->limit(10)->get();
```

### Aggregates

```php
$count = User::count();
$max = User::max('age');
$min = User::min('age');
$avg = User::avg('age');
$sum = User::sum('balance');
```

### Chunking

Process large datasets efficiently:

```php
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

### Exists

```php
if (User::where('email', 'john@example.com')->exists()) {
    // User exists
}
```

## Model Events

```php
class User extends Model
{
    protected function onBeforeSave()
    {
        // Called before save
        if (empty($this->slug)) {
            $this->slug = $this->generateSlug();
        }
    }
    
    protected function onAfterSave()
    {
        // Called after save
        Log::info("User {$this->id} saved");
    }
    
    protected function onBeforeDelete()
    {
        // Called before delete
        $this->posts()->delete();
    }
}
```

## Complete Example

```php
<?php

namespace App\Models;

use Lalaz\Data\Model;

class User extends Model
{
    protected string $table = 'users';
    
    protected array $fillable = [
        'name',
        'email',
        'password'
    ];
    
    protected array $hidden = [
        'password'
    ];
    
    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
    
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    
    // Mutators
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_BCRYPT);
    }
    
    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    // Events
    protected function onBeforeSave()
    {
        if (!isset($this->attributes['slug'])) {
            $this->attributes['slug'] = strtolower(
                str_replace(' ', '-', $this->name)
            );
        }
    }
}
```

**Usage**:

```php
// Create user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret' // Auto-hashed
]);

// Get active users with posts
$users = User::active()
    ->with('posts')
    ->orderBy('name')
    ->get();

// Update user
$user = User::find(1);
$user->name = 'Jane Doe';
$user->save();

// Access relationships
$posts = $user->posts()->get();
$profile = $user->profile()->first();
```

## Best Practices

### Use Fillable or Guarded

```php
// ✅ Good - explicit fillable
protected array $fillable = ['name', 'email'];

// ❌ Avoid - mass assignment vulnerability
// No $fillable defined
```

### Type Casting

```php
protected array $casts = [
    'is_admin' => 'boolean',
    'settings' => 'array',
    'created_at' => 'datetime'
];
```

### Soft Deletes

```php
class User extends Model
{
    protected bool $softDeletes = true;
    
    // Uses 'deleted_at' column
}
```

**Usage**:

```php
$user->delete(); // Soft delete (sets deleted_at)

// Include soft deleted
$users = User::withTrashed()->get();

// Only soft deleted
$users = User::onlyTrashed()->get();

// Restore
$user->restore();

// Permanent delete
$user->forceDelete();
```

---

**Next**: [Views & Templates](07-views.md) →
