# Installation Guide

This guide will walk you through installing Lalaz Framework and creating your first application.

## 📋 Requirements

Before you begin, ensure your system meets these requirements:

### PHP Version
- **PHP 8.1** or higher
- PHP 8.2 or 8.3 recommended for best performance

### Required PHP Extensions
- `pdo` - Database connectivity
- `json` - JSON encoding/decoding
- `mbstring` - Multi-byte string support
- `openssl` - Secure hashing and encryption

### Database (Choose One)
- **MySQL** 5.7+ or MySQL 8.0+
- **PostgreSQL** 9.6+ or PostgreSQL 12+
- **SQLite** 3.x

### Recommended Tools
- **Composer** 2.x - PHP dependency manager
- **Git** - Version control
- **Node.js** 16+ (for front-end asset compilation)

## 🚀 Quick Installation

### Using Composer

Create a new Lalaz project with Composer:

```bash
composer create-project lalaz/lalaz my-app
cd my-app
```

Or add Lalaz to an existing project:

```bash
composer require lalaz/framework
```

### Directory Structure

After installation, your project will have this structure:

```
my-app/
├── app/
│   ├── Controllers/      # Your controllers
│   ├── Models/          # Your models
│   ├── Middleware/      # Custom middleware
│   ├── Events/          # Event classes
│   └── Jobs/            # Background jobs
├── config/
│   └── app.php          # Application configuration
├── database/
│   ├── migrations/      # Database migrations
│   └── seeders/         # Database seeders
├── public/
│   ├── index.php        # Application entry point
│   └── assets/          # Public assets (CSS, JS, images)
├── resources/
│   └── views/           # View templates
├── routes/
│   ├── web.php          # Web routes
│   └── api.php          # API routes
├── storage/
│   ├── cache/           # Cache files
│   └── logs/            # Log files
├── vendor/              # Composer dependencies
├── .env                 # Environment configuration
├── composer.json        # Project dependencies
└── lalaz                # CLI executable
```

## ⚙️ Configuration

### Environment Setup

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```env
# Application
APP_NAME="My Lalaz App"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lalaz_db
DB_USERNAME=root
DB_PASSWORD=

# Cache (Production)
ROUTE_CACHE_ENABLED=false
CONFIG_CACHE_ENABLED=false

# Session
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Logging
LOG_CHANNEL=file
LOG_LEVEL=debug
```

### Database Configuration

#### MySQL

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_database
DB_USERNAME=my_user
DB_PASSWORD=my_password
```

Create your database:

```sql
CREATE DATABASE my_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### PostgreSQL

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=my_database
DB_USERNAME=my_user
DB_PASSWORD=my_password
```

#### SQLite

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

Create the database file:

```bash
touch database/database.sqlite
```

### File Permissions

Ensure these directories are writable:

```bash
chmod -R 775 storage/
chmod -R 775 storage/cache/
chmod -R 775 storage/logs/
```

On Unix/Linux:

```bash
chmod +x lalaz
```

## 🎯 Your First Application

### Step 1: Create a Route

Open `routes/web.php` and add:

```php
<?php

use Lalaz\Lalaz;

$router = Lalaz::router();

$router->get('/', function() {
    return 'Welcome to Lalaz Framework!';
});

$router->get('/about', function() {
    return 'This is the about page';
});

$router->get('/user/{id}', function($id) {
    return "User ID: {$id}";
});
```

### Step 2: Run Development Server

Start the built-in development server:

```bash
php lalaz serve
```

Or specify a custom port:

```bash
php lalaz serve 3000
```

Visit `http://localhost:8080` in your browser! 🎉

### Step 3: Create Your First Controller

Generate a controller using the CLI:

```bash
php lalaz g:controller HomeController
```

This creates `app/Controllers/HomeController.php`:

```php
<?php

namespace App\Controllers;

use Lalaz\Http\Controller;
use Lalaz\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        return 'Welcome from HomeController!';
    }
}
```

Update your route to use the controller:

```php
$router->get('/', [HomeController::class, 'index']);
```

### Step 4: Create a Model

Generate a model:

```bash
php lalaz g:model User
```

This creates `app/Models/User.php`:

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
}
```

### Step 5: Create a Migration

Generate a migration for the users table:

```bash
php lalaz g:migration CreateUsersTable
```

Edit `database/migrations/{timestamp}_CreateUsersTable.php`:

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

Run the migration:

```bash
php lalaz migrate
```

### Step 6: Create a View

Generate a view:

```bash
php lalaz g:view home
```

Edit `resources/views/home.twig`:

```twig
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
</head>
<body>
    <h1>{{ heading }}</h1>
    <p>{{ message }}</p>
</body>
</html>
```

Update your controller to render the view:

```php
public function index(Request $request)
{
    return $this->view('home', [
        'title' => 'Welcome',
        'heading' => 'Hello from Lalaz!',
        'message' => 'This is your first view.'
    ]);
}
```

## 🔧 CLI Commands

View all available commands:

```bash
php lalaz --help
```

Get help for a specific command:

```bash
php lalaz g:controller --help
```

### Most Used Commands

```bash
# Generators
php lalaz g:controller UserController
php lalaz g:model Product
php lalaz g:middleware AuthMiddleware
php lalaz g:migration CreateProductsTable

# Database
php lalaz migrate
php lalaz migrate:rollback
php lalaz seed User

# Cache (Production)
php lalaz route:cache
php lalaz config:cache

# Development
php lalaz serve
php lalaz test
php lalaz routes
```

## 🏗️ Project Structure Best Practices

### Controllers
Place controllers in `app/Controllers/`:
- Use descriptive names: `UserController`, `ProductController`
- One resource per controller when possible
- Keep controllers thin, move logic to models/services

### Models
Place models in `app/Models/`:
- Singular names: `User`, `Product`, `Order`
- Define relationships in the model
- Use fillable/guarded for mass assignment protection

### Middleware
Place custom middleware in `app/Middleware/`:
- Descriptive names: `AuthMiddleware`, `AdminMiddleware`
- Single responsibility principle
- Return early for failed checks

### Views
Organize views in `resources/views/`:
```
resources/views/
├── layouts/
│   └── app.twig
├── components/
│   ├── header.twig
│   └── footer.twig
├── users/
│   ├── index.twig
│   ├── show.twig
│   └── edit.twig
└── home.twig
```

## 🔍 Troubleshooting

### "Class not found" Errors

Regenerate autoload files:

```bash
composer dump-autoload
```

### Permission Errors

Check directory permissions:

```bash
ls -la storage/
```

Fix permissions:

```bash
chmod -R 775 storage/
```

### Database Connection Errors

Verify your `.env` settings match your database:

```bash
# Test connection
php -r "new PDO('mysql:host=127.0.0.1;dbname=lalaz_db', 'root', '');"
```

### Port Already in Use

Use a different port:

```bash
php lalaz serve 8081
```

### Cache Issues

Clear all caches:

```bash
php lalaz route:cache-clear
php lalaz config:cache-clear
rm -rf storage/cache/*
```

## 📦 Installing Optional Dependencies

### Twig (Template Engine)

```bash
composer require twig/twig:^3.0
```

### Testing Tools

```bash
composer require --dev pestphp/pest
```

### Debug Tools

```bash
composer require --dev filp/whoops
```

## ⚡ Production Optimization

Before deploying to production:

1. **Enable Caches**:
```bash
php lalaz route:cache
php lalaz config:cache
```

2. **Update .env**:
```env
APP_ENV=production
APP_DEBUG=false
ROUTE_CACHE_ENABLED=true
CONFIG_CACHE_ENABLED=true
```

3. **Optimize Composer**:
```bash
composer install --no-dev --optimize-autoloader
```

4. **Set Proper Permissions**:
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## 🎓 Next Steps

Now that you have Lalaz installed, continue learning:

1. 📖 [Routing Guide](02-routing.md) - Learn about routing
2. 🎮 [Controllers](03-controllers.md) - Handle requests
3. 🗄️ [Database](05-database.md) - Work with databases
4. 🎨 [Views](07-views.md) - Create templates

## 💡 Tips

- Use `php lalaz --help` frequently to discover commands
- Enable `APP_DEBUG=true` during development
- Keep your `.env` file out of version control (add to `.gitignore`)
- Run `php lalaz test` regularly during development
- Check `storage/logs/` for error details

---

**Installation Complete!** 🎉 Ready to build? Head to the [Routing Guide](02-routing.md)!
