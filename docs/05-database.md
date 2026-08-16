# Database

Lalaz provides a powerful query builder and migration system. Work with MySQL, PostgreSQL, or SQLite with the same fluent interface.

## Configuration

### Database Connection

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lalaz_db
DB_USERNAME=root
DB_PASSWORD=
```

### Supported Drivers

- **MySQL** - `mysql`
- **PostgreSQL** - `pgsql`  
- **SQLite** - `sqlite`

## Query Builder

### Basic Queries

```php
use Lalaz\Lalaz;

$db = Lalaz::db();

// Select all
$users = $db->table('users')->get();

// Select specific columns
$users = $db->table('users')->select(['id', 'name', 'email'])->get();

// First result
$user = $db->table('users')->where('id', 1)->first();

// Count
$count = $db->table('users')->count();
```

### Where Clauses

```php
// Simple where
$users = $db->table('users')
    ->where('status', 'active')
    ->get();

// Multiple conditions
$users = $db->table('users')
    ->where('status', 'active')
    ->where('role', 'admin')
    ->get();

// OR conditions
$users = $db->table('users')
    ->where('role', 'admin')
    ->orWhere('role', 'moderator')
    ->get();

// IN clause
$users = $db->table('users')
    ->whereIn('id', [1, 2, 3])
    ->get();

// NULL checks
$users = $db->table('users')
    ->whereNull('deleted_at')
    ->get();

// Comparisons
$users = $db->table('users')
    ->where('age', '>=', 18)
    ->get();
```

### Ordering & Limiting

```php
// Order by
$users = $db->table('users')
    ->orderBy('created_at', 'DESC')
    ->get();

// Limit
$users = $db->table('users')
    ->limit(10)
    ->get();

// Offset
$users = $db->table('users')
    ->limit(10)
    ->offset(20)
    ->get();

// Pagination
$page = 2;
$perPage = 15;
$users = $db->table('users')
    ->limit($perPage)
    ->offset(($page - 1) * $perPage)
    ->get();
```

### Inserts

```php
// Insert single record
$db->table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_BCRYPT)
]);

// Insert and get ID
$id = $db->table('users')->insertGetId([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);
```

### Updates

```php
// Update records
$db->table('users')
    ->where('id', 1)
    ->update(['name' => 'John Smith']);

// Update multiple conditions
$db->table('users')
    ->where('status', 'pending')
    ->update(['status' => 'active']);

// Increment/Decrement
$db->table('posts')
    ->where('id', 1)
    ->increment('views');
```

### Deletes

```php
// Delete by condition
$db->table('users')
    ->where('id', 1)
    ->delete();

// Delete all
$db->table('temp_data')->delete();
```

### Joins

```php
// Inner join
$posts = $db->table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->select(['posts.*', 'users.name as author'])
    ->get();

// Left join
$posts = $db->table('posts')
    ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
    ->get();
```

### Aggregates

```php
// Count
$count = $db->table('users')->count();

// Sum
$total = $db->table('orders')->sum('amount');

// Average
$avg = $db->table('products')->avg('price');

// Min/Max
$min = $db->table('products')->min('price');
$max = $db->table('products')->max('price');
```

### Raw Queries

```php
// Raw SQL
$users = $db->query("SELECT * FROM users WHERE status = ?", ['active']);

// Raw where clause
$users = $db->table('users')
    ->whereRaw("YEAR(created_at) = ?", [2024])
    ->get();
```

## Migrations

### Creating Migrations

```bash
php lalaz g:migration CreateUsersTable
```

This creates `database/migrations/{timestamp}_CreateUsersTable.php`:

```php
<?php

use Lalaz\Data\Migrations\Migration;
use Lalaz\Data\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### Available Column Types

```php
$table->id();                           // Auto-incrementing ID
$table->string('name', 100);            // VARCHAR
$table->text('description');            // TEXT
$table->integer('age');                 // INTEGER
$table->bigInteger('amount');           // BIGINT
$table->decimal('price', 10, 2);        // DECIMAL
$table->boolean('is_active');           // BOOLEAN
$table->date('birth_date');             // DATE
$table->datetime('published_at');       // DATETIME
$table->timestamp('created_at');        // TIMESTAMP
$table->json('metadata');               // JSON
$table->timestamps();                   // created_at & updated_at
```

### Column Modifiers

```php
$table->string('email')->unique();
$table->string('name')->nullable();
$table->integer('sort_order')->default(0);
$table->string('slug')->index();
```

### Foreign Keys

```php
Schema::create('posts', function($table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('content');
    $table->timestamps();
});
```

### Running Migrations

```bash
# Run all pending migrations
php lalaz migrate

# Rollback last batch
php lalaz migrate:rollback

# Reset all migrations
php lalaz migrate:reset
```

## Seeders

### Creating Seeders

```bash
php lalaz g:seeder UserSeeder
```

Edit `database/seeders/UserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Lalaz\Data\Seeders\Seeder;
use Lalaz\Lalaz;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $db = Lalaz::db();
        
        $db->table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
```

### Running Seeders

```bash
# Run specific seeder
php lalaz seed User

# Run all seeders
php lalaz seed:all
```

## Transactions

```php
$db = Lalaz::db();

try {
    $db->beginTransaction();
    
    $db->table('users')->insert(['name' => 'John']);
    $db->table('profiles')->insert(['user_id' => 1]);
    
    $db->commit();
} catch (\Exception $e) {
    $db->rollback();
    throw $e;
}
```

## Best Practices

### 1. Use Migrations

Never modify the database manually - always use migrations:

```bash
php lalaz g:migration AddStatusToUsersTable
```

### 2. Index Foreign Keys

Always index foreign key columns:

```php
$table->foreignId('user_id')->constrained()->index();
```

### 3. Use Timestamps

Track creation and updates:

```php
$table->timestamps(); // Adds created_at and updated_at
```

### 4. Soft Deletes

Consider soft deletes for important data:

```php
$table->softDeletes(); // Adds deleted_at
```

### 5. Use Query Builder

Prefer query builder over raw SQL for security:

```php
// Good
$users = $db->table('users')->where('email', $email)->get();

// Avoid
$users = $db->query("SELECT * FROM users WHERE email = '$email'");
```

---

**Next**: Learn about [Models](06-models.md) and ActiveRecord pattern →
