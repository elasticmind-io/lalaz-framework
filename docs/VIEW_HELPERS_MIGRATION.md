# View Helpers Migration Guide

## Overview

The framework now provides **template-engine-agnostic** view helpers through the `ViewHelpers` class. This allows you to use the same helper functions with any template engine (Twig, Blade, Plates, etc.) without coupling the framework to a specific implementation.

## Why This Change?

**Before:** The `Utils` class was tightly coupled to Twig:
```php
use Twig\TwigFunction;

public static function asset(): TwigFunction {
    return new TwigFunction('asset', function($path) { ... });
}
```

**After:** The `ViewHelpers` class is framework-agnostic:
```php
public static function asset(): ViewFunction {
    return new ViewFunction('asset', function($path) { ... });
}
```

## Usage in Your Project

### Option 1: Use in Template Provider (Recommended)

Create or update your template engine provider to adapt the helpers:

```php
// In your project: App/Providers/TwigProvider.php

namespace App\Providers;

use Lalaz\View\ViewHelpers;
use Lalaz\View\Contracts\TemplateEngineInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TwigProvider implements TemplateEngineInterface
{
    private Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../Views');
        $this->twig = new Environment($loader, [
            'cache' => __DIR__ . '/../../storage/cache/views',
            'auto_reload' => true,
        ]);

        // Register all framework helpers
        $this->registerFrameworkHelpers();
        
        // Register your custom helpers
        $this->registerCustomHelpers();
    }

    private function registerFrameworkHelpers(): void
    {
        foreach (ViewHelpers::all() as $helper) {
            // Adapt framework helper to Twig
            $this->twig->addFunction(new TwigFunction(
                $helper->getName(),
                $helper->getCallable(),
                $helper->getOptions()
            ));
        }
    }

    private function registerCustomHelpers(): void
    {
        // Add your project-specific helpers here
        $this->twig->addFunction(new TwigFunction('money', function($value) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        }));
    }

    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }
}
```

### Option 2: Use Individual Helpers

You can also register helpers individually:

```php
// Register only specific helpers
$assetHelper = ViewHelpers::asset();
$this->twig->addFunction(new TwigFunction(
    $assetHelper->getName(),      // 'asset'
    $assetHelper->getCallable(),  // function($path) { ... }
    $assetHelper->getOptions()    // ['is_safe' => ['html']]
));

$csrfHelper = ViewHelpers::csrfField();
$this->twig->addFunction(new TwigFunction(
    $csrfHelper->getName(),
    $csrfHelper->getCallable(),
    $csrfHelper->getOptions()
));
```

### Option 3: Use with Other Template Engines

The same helpers work with any template engine:

#### Blade Example
```php
use Lalaz\View\ViewHelpers;
use Illuminate\View\Factory;

foreach (ViewHelpers::all() as $helper) {
    $name = $helper->getName();
    $callable = $helper->getCallable();
    
    Blade::directive($name, function($expression) use ($callable) {
        return "<?php echo {$callable}({$expression}); ?>";
    });
}
```

#### Plates Example
```php
use Lalaz\View\ViewHelpers;
use League\Plates\Engine;

$plates = new Engine('/path/to/templates');

foreach (ViewHelpers::all() as $helper) {
    $plates->registerFunction(
        $helper->getName(),
        $helper->getCallable()
    );
}
```

## Available Helpers

All helpers are available through `ViewHelpers::all()` or individually:

```php
ViewHelpers::asset()        // Resolve assets via manifest.json
ViewHelpers::flashMessage() // Display flash messages
ViewHelpers::routeUrl()     // Generate route URLs
ViewHelpers::conditional()  // Ternary in templates
ViewHelpers::renderIf()     // Conditional rendering
ViewHelpers::csrfToken()    // Get CSRF token
ViewHelpers::csrfField()    // Render CSRF hidden input
```

## Usage in Templates

Once registered, use them in your templates:

```twig
{# Asset helper #}
<link rel="stylesheet" href="{{ asset('css/app.css') }}">

{# CSRF protection #}
<form method="POST">
    {{ csrfField() }}
    <!-- form fields -->
</form>

{# Flash messages #}
{{ showFlashMessage('success') }}

{# Conditional rendering #}
<div class="{{ conditional(user.isAdmin, 'admin', 'user') }}">

{# Render if #}
{{ renderIf('<span class="badge">New</span>', product.isNew) }}
```

## Backward Compatibility

The old `Utils` class is still available but deprecated:

```php
// ⚠️ Deprecated (will be removed in v2.0)
use Lalaz\View\Utils;
$helpers = Utils::all();

// ✅ Use this instead
use Lalaz\View\ViewHelpers;
$helpers = ViewHelpers::all();
```

## Benefits

1. **Decoupled**: Framework is no longer tied to Twig
2. **Flexible**: Use any template engine you want
3. **Testable**: Helpers are pure PHP functions
4. **Extensible**: Easy to add custom helpers in your project
5. **Type Safe**: Full IDE support with proper interfaces

## Migration Checklist

- [ ] Update your template provider to use `ViewHelpers`
- [ ] Remove direct references to `Utils` class
- [ ] Test all view helpers in your templates
- [ ] Add custom helpers if needed
- [ ] Update documentation for your team
