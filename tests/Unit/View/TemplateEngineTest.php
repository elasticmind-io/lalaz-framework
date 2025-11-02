<?php

use Lalaz\View\Contracts\TemplateEngineInterface;
use Lalaz\View\TemplateEngine;

// Load mock template engine
require_once __DIR__ . '/../../Shared/Stubs/MockTemplateEngine.php';

beforeEach(function () {
    // Reset static engine using Reflection
    $reflection = new ReflectionClass(TemplateEngine::class);
    $property = $reflection->getProperty('engine');
    $property->setAccessible(true);
    $property->setValue(null, null);

    unset($_ENV['TEMPLATE_PROVIDER']);
});

afterEach(function () {
    // Reset static engine using Reflection
    $reflection = new ReflectionClass(TemplateEngine::class);
    $property = $reflection->getProperty('engine');
    $property->setAccessible(true);
    $property->setValue(null, null);

    unset($_ENV['TEMPLATE_PROVIDER']);
});

describe('TemplateEngine', function () {
    describe('initialization', function () {
        it('initializes with valid template engine from config', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            TemplateEngine::init();
            $engine = TemplateEngine::getEngine();

            expect($engine)->toBeInstanceOf(TemplateEngineInterface::class);
            expect($engine)->toBeInstanceOf('Tests\\Shared\\Stubs\\MockTemplateEngine');
        });

        it('does not reinitialize if already initialized', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            TemplateEngine::init();
            $firstEngine = TemplateEngine::getEngine();

            TemplateEngine::init(); // Second init should do nothing
            $secondEngine = TemplateEngine::getEngine();

            expect($secondEngine)->toBe($firstEngine);
        });

        it('throws exception when TEMPLATE_PROVIDER not set', function () {
            unset($_ENV['TEMPLATE_PROVIDER']);

            expect(fn() => TemplateEngine::init())
                ->toThrow(\Exception::class, 'TEMPLATE_PROVIDER was not provided');
        });
    });

    describe('getEngine', function () {
        it('returns initialized engine', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            TemplateEngine::init();
            $engine = TemplateEngine::getEngine();

            expect($engine)->toBeInstanceOf(TemplateEngineInterface::class);
        });

        it('throws exception when not initialized', function () {
            expect(fn() => TemplateEngine::getEngine())
                ->toThrow(\Exception::class, 'TemplateManager não foi inicializado');
        });

        it('returns same instance on multiple calls', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            TemplateEngine::init();
            $engine1 = TemplateEngine::getEngine();
            $engine2 = TemplateEngine::getEngine();

            expect($engine2)->toBe($engine1);
        });
    });

    describe('render', function () {
        it('delegates rendering to the engine', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            TemplateEngine::init();
            $result = TemplateEngine::render('home', ['title' => 'Home']);

            expect($result)->toBe('MockRendered: home');
        });

        it('renders without data', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            TemplateEngine::init();
            $result = TemplateEngine::render('about');

            expect($result)->toBe('MockRendered: about');
        });

        it('passes data to engine', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            TemplateEngine::init();
            $result = TemplateEngine::render('profile', ['user' => 'John']);

            expect($result)->toContain('profile');
        });
    });

    describe('static behavior', function () {
        it('maintains engine state across calls', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            TemplateEngine::init();
            $engine1 = TemplateEngine::getEngine();

            TemplateEngine::render('test');
            $engine2 = TemplateEngine::getEngine();

            expect($engine2)->toBe($engine1);
        });
    });

    describe('edge cases', function () {
        it('handles templates with special characters', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            TemplateEngine::init();
            $result = TemplateEngine::render('admin/users/list');

            expect($result)->toBe('MockRendered: admin/users/list');
        });

        it('handles empty template name', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            TemplateEngine::init();
            $result = TemplateEngine::render('');

            expect($result)->toBe('MockRendered: ');
        });

        it('handles large data arrays', function () {
            $_ENV['TEMPLATE_PROVIDER'] = 'Tests\\Shared\\Stubs\\MockTemplateEngine';

            $largeData = [];
            for ($i = 0; $i < 100; $i++) {
                $largeData["key{$i}"] = "value{$i}";
            }

            TemplateEngine::init();
            $result = TemplateEngine::render('test', $largeData);

            expect($result)->toBe('MockRendered: test');
        });
    });
});
