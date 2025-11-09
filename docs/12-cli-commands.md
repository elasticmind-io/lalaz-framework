# CLI Commands

Lalaz Framework provides a powerful command-line interface to speed up development. Access all commands with `php lalaz`.

## Getting Help

### View All Commands

```bash
php lalaz --help
```

### Command-Specific Help

```bash
php lalaz g:controller --help
php lalaz migrate --help
php lalaz config:cache --help
```

### Version Information

```bash
php lalaz --version
```

## 🎨 Generators

Generate boilerplate code instantly.

### Generate Controller

```bash
php lalaz g:controller UserController
```

Creates `app/Controllers/UserController.php`:
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

**Nested controllers**:
```bash
php lalaz g:controller Api/V1/UserController
```

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

### Generate Middleware

```bash
php lalaz g:middleware AuthMiddleware
```

Creates `app/Middleware/AuthMiddleware.php`.

### Generate Migration

```bash
php lalaz g:migration CreateUsersTable
```

Creates timestamped migration file in `database/migrations/`.

### Generate Seeder

```bash
php lalaz g:seeder UserSeeder
```

Creates `database/seeders/UserSeeder.php`.

### Generate Event

```bash
php lalaz g:event UserRegistered
```

Creates `app/Events/UserRegistered.php`.

### Generate Job

```bash
php lalaz g:job SendWelcomeEmail
```

Creates `app/Jobs/SendWelcomeEmail.php`.

### Generate Form

```bash
php lalaz g:form LoginForm
```

Creates `app/Forms/LoginForm.php`.

### Generate View

```bash
php lalaz g:view home
php lalaz g:view users/index
```

Creates Twig templates in `resources/views/`.

## 🗄️ Database Commands

### Run Migrations

Execute all pending migrations:

```bash
php lalaz migrate
```

Output:
```
🔄 Running migrations...
✅ Migration 2024_01_01_000001_CreateUsersTable executed
✅ Migration 2024_01_01_000002_CreatePostsTable executed
✅ Migrations completed successfully!
```

### Rollback Migrations

Rollback the last batch:

```bash
php lalaz migrate:rollback
```

### Reset Migrations

Drop all tables and re-run migrations:

```bash
php lalaz migrate:reset
```

**⚠️ Warning**: This deletes ALL data!

### Run Seeders

Run a specific seeder:

```bash
php lalaz seed User
```

Run all seeders:

```bash
php lalaz seed:all
```

## ⚡ Cache Commands

**Boost performance by up to 300x!**

### Route Cache

Compile routes for production (50x faster):

```bash
php lalaz route:cache
```

Output:
```
🔄 Compiling routes...
✅ Router cache created successfully!
📁 Cache file: storage/cache/routes.php
📊 Cached 42 route(s)

💡 Tip: Enable route caching by setting ROUTE_CACHE_ENABLED=true in your .env file
```

Clear route cache:

```bash
php lalaz route:cache-clear
```

### Config Cache

Compile configuration for production (300x faster):

```bash
php lalaz config:cache
```

With custom env file:

```bash
php lalaz config:cache .env.production
```

Output:
```
⚡ Compiling config cache...
✅ Config cached successfully!
📁 Cache file: storage/cache/config.php
📊 Cached 24 config variable(s)

💡 Tip: Enable config caching by setting CONFIG_CACHE_ENABLED=true in your .env file
🚀 Expected speedup: ~300x faster (2-3ms → 0.01ms)
```

Clear config cache:

```bash
php lalaz config:cache-clear
```

## 📦 Queue Commands

### Run Jobs Once

Process all pending jobs:

```bash
php lalaz jobs:once
```

### Run Queue Worker

Run in daemon mode (continuous processing):

```bash
php lalaz jobs:run
```

Output:
```
Jobs executed successfully! Waiting for next run...
Jobs executed successfully! Waiting for next run...
```

Stop with `Ctrl+C`.

## 🔧 Development Commands

### Start Development Server

Start PHP built-in server with Vite:

```bash
php lalaz serve
```

Default port: **8080**

Custom port:

```bash
php lalaz serve 3000
```

Visit `http://localhost:8080` in your browser.

### Run Tests

Execute test suite:

```bash
php lalaz test
```

This runs Pest/PHPUnit tests.

## 🛠️ Utility Commands

### List All Routes

Display routes in table format:

```bash
php lalaz routes
```

Output:
```
| #  | Method | URI              | Controller#action       | Has Middlewares |
|---------------------------------------------------------------------------|
| 1  | GET    | /                | HomeController#index    | No              |
| 2  | GET    | /users           | UserController#index    | Yes             |
| 3  | POST   | /users           | UserController#store    | Yes             |
| 4  | GET    | /users/{id}      | UserController#show     | No              |
| 5  | PUT    | /users/{id}      | UserController#update   | Yes             |
```

### Show Route Middleware

Display middleware for a specific route:

```bash
php lalaz middlewares 2
```

Output:
```
GET /users
-----------------------------------------------------------
- App\Middleware\AuthMiddleware
- App\Middleware\LogRequestMiddleware
```

### Hash Password

Generate bcrypt password hash:

```bash
php lalaz hash:password mypassword123
```

Output:
```
$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

With spaces:

```bash
php lalaz hash:password "my complex password"
```

## 🚀 Production Workflow

### Pre-Deployment Checklist

```bash
# 1. Update environment
APP_ENV=production
APP_DEBUG=false
ROUTE_CACHE_ENABLED=true
CONFIG_CACHE_ENABLED=true

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Run migrations
php lalaz migrate

# 4. Cache everything
php lalaz route:cache
php lalaz config:cache

# 5. Clear development caches
rm -rf storage/cache/views/*
```

### Deployment Script Example

```bash
#!/bin/bash

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php lalaz migrate --force

# Cache for performance
php lalaz config:cache
php lalaz route:cache

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx

echo "✅ Deployment complete!"
```

## 💡 Tips & Tricks

### Create Multiple Files

```bash
# Generate all CRUD components
php lalaz g:controller UserController
php lalaz g:model User
php lalaz g:migration CreateUsersTable
php lalaz g:seeder UserSeeder
```

### Quick Prototyping

```bash
# Generate complete resource
php lalaz g:controller ProductController
php lalaz g:model Product  
php lalaz g:migration CreateProductsTable
php lalaz g:view products/index
php lalaz g:view products/show
```

### Development Workflow

```bash
# Terminal 1: Development server
php lalaz serve

# Terminal 2: Queue worker  
php lalaz jobs:run

# Terminal 3: Run tests on changes
php lalaz test --watch
```

### Cache Management

```bash
# Clear all caches during development
php lalaz route:cache-clear
php lalaz config:cache-clear
rm -rf storage/cache/*

# Enable all caches for production
php lalaz route:cache
php lalaz config:cache
```

## 🔍 Common Scenarios

### Setting Up New Project

```bash
# 1. Create database and configure .env
# 2. Create initial migration
php lalaz g:migration CreateUsersTable

# 3. Run migration
php lalaz migrate

# 4. Create seeder
php lalaz g:seeder UserSeeder

# 5. Seed database
php lalaz seed User

# 6. Start server
php lalaz serve
```

### Adding New Feature

```bash
# 1. Generate migration
php lalaz g:migration CreatePostsTable

# 2. Generate model
php lalaz g:model Post

# 3. Generate controller
php lalaz g:controller PostController

# 4. Run migration
php lalaz migrate

# 5. Test
php lalaz test
```

### Preparing for Production

```bash
# 1. Run tests
php lalaz test

# 2. Optimize autoloader
composer dump-autoload --optimize

# 3. Cache routes and config
php lalaz route:cache
php lalaz config:cache

# 4. Verify routes
php lalaz routes

# 5. Deploy!
```

## 🎓 Command Reference

| Command | Description | Example |
|---------|-------------|---------|
| `g:controller` | Generate controller | `php lalaz g:controller UserController` |
| `g:model` | Generate model | `php lalaz g:model User` |
| `g:middleware` | Generate middleware | `php lalaz g:middleware AuthMiddleware` |
| `g:migration` | Generate migration | `php lalaz g:migration CreateUsersTable` |
| `g:seeder` | Generate seeder | `php lalaz g:seeder UserSeeder` |
| `g:event` | Generate event | `php lalaz g:event UserRegistered` |
| `g:job` | Generate job | `php lalaz g:job SendEmail` |
| `g:form` | Generate form | `php lalaz g:form LoginForm` |
| `g:view` | Generate view | `php lalaz g:view home` |
| `migrate` | Run migrations | `php lalaz migrate` |
| `migrate:rollback` | Rollback migrations | `php lalaz migrate:rollback` |
| `migrate:reset` | Reset migrations | `php lalaz migrate:reset` |
| `seed` | Run seeder | `php lalaz seed User` |
| `seed:all` | Run all seeders | `php lalaz seed:all` |
| `route:cache` | Cache routes | `php lalaz route:cache` |
| `route:cache-clear` | Clear route cache | `php lalaz route:cache-clear` |
| `config:cache` | Cache config | `php lalaz config:cache` |
| `config:cache-clear` | Clear config cache | `php lalaz config:cache-clear` |
| `jobs:once` | Run jobs once | `php lalaz jobs:once` |
| `jobs:run` | Run jobs daemon | `php lalaz jobs:run` |
| `serve` | Start dev server | `php lalaz serve 8080` |
| `test` | Run tests | `php lalaz test` |
| `routes` | List all routes | `php lalaz routes` |
| `middlewares` | Show route middleware | `php lalaz middlewares 1` |
| `hash:password` | Hash password | `php lalaz hash:password secret` |
| `--help` | Show help | `php lalaz --help` |
| `--version` | Show version | `php lalaz --version` |

---

**Next**: Learn about [Performance Optimization](13-performance.md) →
