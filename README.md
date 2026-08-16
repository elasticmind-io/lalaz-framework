# Lalaz Framework Documentation

Welcome to **Lalaz Framework v1.0** – A modern, fast, and elegant PHP framework designed for developers who value clean code, excellent performance, and great developer experience.

## 🚀 Why Lalaz?

Lalaz Framework combines the best practices from modern PHP development with a focus on:

- **⚡ Performance** - Router cache (50x faster), Config cache (300x faster)
- **🔐 Security** - Built-in CSRF protection, security headers, bcrypt hashing
- **🎨 Clean Architecture** - PSR-compliant, dependency injection, middleware pipeline
- **📝 Great DX** - Intuitive CLI, detailed error messages, extensive documentation
- **🧪 Well Tested** - 308 tests, 847 assertions, 100% passing
- **🔧 Flexible** - Template-engine agnostic, multiple database adapters

## 📚 Documentation Structure

### Getting Started
- **[Installation Guide](docs/01-installation.md)** - Requirements, setup, and your first app
- **[Routing](docs/02-routing.md)** - Define routes, parameters, groups, and middleware
- **[Controllers](docs/03-controllers.md)** - Handle requests and return responses
- **[Middleware](docs/04-middleware.md)** - Filter and modify HTTP requests

### Core Concepts
- **[Database](docs/05-database.md)** - Query builder, migrations, and seeders
- **[Models](docs/06-models.md)** - ActiveRecord pattern and relationships
- **[Views](docs/07-views.md)** - Templates, view helpers, and rendering
- **[Validation](docs/08-validation.md)** - Form validation and error handling

### Advanced Topics
- **[Security](docs/09-security.md)** - CSRF, security headers, and authentication
- **[Logging](docs/10-logging.md)** - PSR-3 logging with multiple writers
- **[Events & Jobs](docs/11-events-jobs.md)** - Event system and background processing
- **[CLI Commands](docs/12-cli-commands.md)** - All available CLI commands

### Production
- **[Performance](docs/13-performance.md)** - Optimization tips and caching strategies
- **[Deployment](docs/14-deployment.md)** - Production setup and best practices

## ⚡ Quick Start

### Installation

```bash
composer require lalaz/framework
```

### Create Your First Route

```php
<?php
// routes/web.php

use Lalaz\Lalaz;

$router = Lalaz::router();

$router->get('/', function() {
    return 'Welcome to Lalaz Framework!';
});

$router->get('/hello/{name}', function($name) {
    return "Hello, {$name}!";
});
```

### Run Development Server

```bash
php lalaz serve
```

Visit `http://localhost:8080` and you're ready! 🎉

## 🎯 Key Features

### Routing
- Clean, expressive syntax
- Route parameters and constraints
- Route groups with shared middleware
- **50x faster with route caching**

### Database
- Fluent query builder
- ActiveRecord models with relationships
- Migration system for version control
- Support for MySQL, PostgreSQL, SQLite

### Views
- Template-engine agnostic design
- Built-in Twig provider
- View helpers for common tasks
- Layout and component support

### Security
- CSRF protection out of the box
- Security headers middleware (4 presets)
- Bcrypt password hashing
- SQL injection prevention

### Performance
- **Router cache: 50x faster** (1ms → 0.02ms)
- **Config cache: 300x faster** (2-3ms → 0.01ms)
- OPcache optimization
- ~90ms saved per request in production

### CLI Tools
- 9 code generators (controller, model, migration, etc.)
- Database migrations and seeders
- Cache management commands
- Queue/job workers
- Built-in development server

## 📦 What's Included

### Core Components
- Router with middleware support
- HTTP Request/Response objects
- Dependency injection container
- Session management
- File upload handling

### Data Layer
- Query builder with fluent interface
- ActiveRecord pattern
- Relationships (hasMany, belongsTo, belongsToMany)
- Database migrations
- Database seeders

### View Layer
- Template engine support
- View helpers (asset, route, CSRF, etc.)
- Flash messages
- Layout inheritance

### Validation
- 15+ built-in validation rules
- Custom validation callbacks
- Localized error messages
- Array and JSON serialization

### Logging
- PSR-3 compliant logger
- Multiple formatters (Text, JSON)
- Multiple writers (File, Console)
- Automatic log rotation
- Level filtering

### Queue System
- Background job processing
- Event system
- Job scheduling
- Daemon mode for continuous processing

## 🤝 Requirements

- PHP 8.1 or higher
- Composer
- PDO extension (for database)
- One of: MySQL 5.7+, PostgreSQL 9.6+, or SQLite 3

## 📖 Learning Path

### Beginners
1. [Installation Guide](docs/01-installation.md)
2. [Routing Basics](docs/02-routing.md)
3. [Controllers](docs/03-controllers.md)
4. [Views](docs/07-views.md)

### Intermediate
1. [Database](docs/05-database.md)
2. [Models & Relationships](docs/06-models.md)
3. [Validation](docs/08-validation.md)
4. [Middleware](docs/04-middleware.md)

### Advanced
1. [Security](docs/09-security.md)
2. [Events & Jobs](docs/11-events-jobs.md)
3. [Performance Optimization](docs/13-performance.md)
4. [Deployment](docs/14-deployment.md)

## 🎓 Philosophy

Lalaz Framework is built on these core principles:

- **Simplicity** - Keep things simple and intuitive
- **Performance** - Fast by default, optimized for production
- **Security** - Secure defaults, easy to maintain
- **Flexibility** - Don't force patterns, provide tools
- **Testing** - Everything should be testable
- **Documentation** - If it's not documented, it doesn't exist

## 🔗 Useful Links

- **GitHub Repository**: [elasticmind-io/lalaz-framework](https://github.com/elasticmind-io/lalaz-framework)
- **CLI Reference**: [CLI Commands Guide](docs/12-cli-commands.md)
- **API Documentation**: Coming soon
- **Community Forum**: Coming soon

## 💡 Need Help?

- 📖 Check the [detailed documentation](docs/01-installation.md)
- 💬 Ask questions in GitHub Discussions
- 🐛 Report bugs in GitHub Issues
- 📧 Email: ola@elasticmind.io

## 📝 License

Lalaz Framework is open-source software licensed under the MIT license.

---

**Ready to build something amazing?** Start with the [Installation Guide](docs/01-installation.md)! 🚀
