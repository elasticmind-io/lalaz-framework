# Views & Templates

Lalaz provides a flexible view system with support for multiple template engines. Use native PHP templates or Twig for advanced features.

## Basic Usage

### Rendering Views

```php
public function index(Request $request)
{
    return Response::view('home', [
        'title' => 'Welcome',
        'user' => $request->session->get('user')
    ]);
}
```

### View Files

Create `resources/views/home.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
</head>
<body>
    <h1>Welcome, <?= $user['name'] ?></h1>
</body>
</html>
```

## Template Engines

### Native PHP

Default engine, no configuration needed:

```php
<!-- resources/views/welcome.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= esc($title) ?></title>
</head>
<body>
    <h1><?= $greeting ?></h1>
    <?php foreach ($items as $item): ?>
        <p><?= esc($item) ?></p>
    <?php endforeach; ?>
</body>
</html>
```

### Twig

Install Twig:

```bash
composer require twig/twig
```

Configure in `bootstrap/app.php`:

```php
use Lalaz\View\Providers\TwigTemplateEngine;

$app->register('view', function() {
    return new TwigTemplateEngine(__DIR__ . '/../resources/views');
});
```

Create `resources/views/welcome.twig`:

```twig
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
</head>
<body>
    <h1>{{ greeting }}</h1>
    {% for item in items %}
        <p>{{ item }}</p>
    {% endfor %}
</body>
</html>
```

## View Helpers

Lalaz includes powerful template-agnostic helpers.

### CSRF Protection

```php
<!-- PHP templates -->
<?= csrf_field() ?>

<!-- Twig templates -->
{{ csrf_field() }}
```

Outputs:

```html
<input type="hidden" name="_token" value="...">
```

### Old Input (Form Repopulation)

```php
<!-- PHP templates -->
<input type="text" name="email" value="<?= old('email') ?>">

<!-- Twig templates -->
<input type="text" name="email" value="{{ old('email') }}">
```

After validation error, repopulates form with previous input.

### Error Messages

```php
<!-- PHP templates -->
<?php if (has_error('email')): ?>
    <p class="error"><?= error('email') ?></p>
<?php endif; ?>

<!-- Twig templates -->
{% if has_error('email') %}
    <p class="error">{{ error('email') }}</p>
{% endif %}
```

### Asset URLs

```php
<!-- PHP templates -->
<img src="<?= asset('images/logo.png') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">

<!-- Twig templates -->
<img src="{{ asset('images/logo.png') }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
```

### Route URLs

```php
<!-- Named routes -->
<a href="<?= route('user.show', ['id' => 1]) ?>">View User</a>

<!-- Twig -->
<a href="{{ route('user.show', {id: 1}) }}">View User</a>
```

### Escaping Output

```php
<!-- PHP templates -->
<?= esc($userInput) ?>
<?= e($userInput) ?> <!-- Alias -->

<!-- Twig (auto-escapes) -->
{{ userInput }}
{{ userInput|raw }} <!-- Disable escaping -->
```

## Layouts & Partials

### PHP Layouts

`resources/views/layouts/main.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'My App' ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
    <?php include 'partials/header.php'; ?>
    
    <main>
        <?= $content ?>
    </main>
    
    <?php include 'partials/footer.php'; ?>
</body>
</html>
```

`resources/views/home.php`:

```php
<?php ob_start(); ?>
    <h1>Welcome Home</h1>
    <p>This is the home page.</p>
<?php $content = ob_get_clean(); ?>

<?php include 'layouts/main.php'; ?>
```

### Twig Layouts

`resources/views/layouts/main.twig`:

```twig
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}My App{% endblock %}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    {% include 'partials/header.twig' %}
    
    <main>
        {% block content %}{% endblock %}
    </main>
    
    {% include 'partials/footer.twig' %}
</body>
</html>
```

`resources/views/home.twig`:

```twig
{% extends 'layouts/main.twig' %}

{% block title %}Home{% endblock %}

{% block content %}
    <h1>Welcome Home</h1>
    <p>This is the home page.</p>
{% endblock %}
```

### Partials

`resources/views/partials/header.twig`:

```twig
<header>
    <nav>
        <a href="{{ route('home') }}">Home</a>
        <a href="{{ route('about') }}">About</a>
    </nav>
</header>
```

## Forms

### Complete Form Example

```php
<form method="POST" action="/users">
    <?= csrf_field() ?>
    
    <div>
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= old('name') ?>">
        <?php if (has_error('name')): ?>
            <span class="error"><?= error('name') ?></span>
        <?php endif; ?>
    </div>
    
    <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>">
        <?php if (has_error('email')): ?>
            <span class="error"><?= error('email') ?></span>
        <?php endif; ?>
    </div>
    
    <button type="submit">Create User</button>
</form>
```

### Twig Form Example

```twig
<form method="POST" action="/users">
    {{ csrf_field() }}
    
    <div>
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}">
        {% if has_error('name') %}
            <span class="error">{{ error('name') }}</span>
        {% endif %}
    </div>
    
    <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}">
        {% if has_error('email') %}
            <span class="error">{{ error('email') }}</span>
        {% endif %}
    </div>
    
    <button type="submit">Create User</button>
</form>
```

## Custom View Helpers

### Registering Helpers

In `bootstrap/app.php`:

```php
use Lalaz\View\ViewContext;

ViewContext::registerFunction('currency', function($value) {
    return '$' . number_format($value, 2);
});

ViewContext::registerFunction('date_format', function($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
});
```

### Using Custom Helpers

```php
<!-- PHP templates -->
<p>Price: <?= currency(99.99) ?></p>
<p>Date: <?= date_format($order->created_at, 'F j, Y') ?></p>

<!-- Twig templates -->
<p>Price: {{ currency(99.99) }}</p>
<p>Date: {{ date_format(order.created_at, 'F j, Y') }}</p>
```

## Nested Views

```php
// resources/views/users/index.php
return Response::view('users/index', ['users' => $users]);
```

Organize views in subdirectories:

```
resources/views/
    layouts/
        main.php
        admin.php
    partials/
        header.php
        footer.php
    users/
        index.php
        show.php
        create.php
    posts/
        index.php
        show.php
```

## View Composition

### Sharing Data

Share data across all views:

```php
// In bootstrap/app.php or middleware
ViewContext::share('appName', 'Lalaz App');
ViewContext::share('currentYear', date('Y'));
```

Available in all views:

```php
<footer>
    © <?= $currentYear ?> <?= $appName ?>
</footer>
```

## Complete Example

### Controller

```php
<?php

namespace App\Controllers;

use Lalaz\Http\Controller;
use Lalaz\Http\Request;
use Lalaz\Http\Response;
use App\Models\Post;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::orderBy('created_at', 'desc')->limit(10)->get();
        
        return Response::view('posts/index', [
            'posts' => $posts,
            'title' => 'Latest Posts'
        ]);
    }
    
    public function show(Request $request, int $id)
    {
        $post = Post::findOrFail($id);
        
        return Response::view('posts/show', [
            'post' => $post,
            'title' => $post->title
        ]);
    }
}
```

### View (Twig)

`resources/views/posts/index.twig`:

```twig
{% extends 'layouts/main.twig' %}

{% block title %}{{ title }}{% endblock %}

{% block content %}
    <h1>{{ title }}</h1>
    
    {% if posts is empty %}
        <p>No posts found.</p>
    {% else %}
        <div class="posts">
            {% for post in posts %}
                <article>
                    <h2>
                        <a href="{{ route('post.show', {id: post.id}) }}">
                            {{ post.title }}
                        </a>
                    </h2>
                    <p>{{ post.excerpt }}</p>
                    <small>Posted on {{ date_format(post.created_at) }}</small>
                </article>
            {% endfor %}
        </div>
    {% endif %}
{% endblock %}
```

`resources/views/posts/show.twig`:

```twig
{% extends 'layouts/main.twig' %}

{% block title %}{{ post.title }}{% endblock %}

{% block content %}
    <article>
        <h1>{{ post.title }}</h1>
        <p class="meta">
            By {{ post.author.name }} on {{ date_format(post.created_at, 'F j, Y') }}
        </p>
        
        <div class="content">
            {{ post.content|raw }}
        </div>
        
        <a href="{{ route('posts.index') }}">← Back to posts</a>
    </article>
{% endblock %}
```

## Best Practices

### Escape User Input

```php
<!-- ✅ Good - escaped -->
<p><?= esc($userInput) ?></p>

<!-- ❌ Bad - XSS vulnerability -->
<p><?= $userInput ?></p>
```

### Use Layouts

```php
// ✅ Good - reusable layout
{% extends 'layouts/main.twig' %}

// ❌ Bad - repeated HTML
<!DOCTYPE html>... (repeated in every view)
```

### Keep Views Thin

```php
// ✅ Good - logic in controller
$posts = Post::published()->recent()->get();
return Response::view('posts', ['posts' => $posts]);

// ❌ Bad - logic in view
<?php $posts = Post::where('published', true)->get(); ?>
```

### Use Named Routes

```php
<!-- ✅ Good - named route -->
<a href="<?= route('user.show', ['id' => $user->id]) ?>">View</a>

<!-- ❌ Bad - hardcoded URL -->
<a href="/users/<?= $user->id ?>">View</a>
```

---

**Next**: [Validation](08-validation.md) →
