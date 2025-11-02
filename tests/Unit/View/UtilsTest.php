<?php

use Lalaz\View\Utils;

// Load mock Twig classes for testing
require_once __DIR__ . '/../../Shared/Stubs/MockTwigFunction.php';

if (!class_exists('Twig\TwigFunction')) {
    class_alias('Tests\Shared\Stubs\MockTwigFunction', 'Twig\TwigFunction');
}

beforeEach(function () {
    $_SESSION = [];
});

afterEach(function () {
    $_SESSION = [];
});

describe('Utils', function () {
    describe('flashMessage', function () {
        it('returns a TwigFunction', function () {
            $result = Utils::flashMessage();
            expect($result)->toBeInstanceOf('Twig\TwigFunction');
            expect($result->getName())->toBe('showFlashMessage');
        });

        it('invokes showFlashMessage when callable is called', function () {
            $_SESSION['FLASH_MESSAGES']['success'] = [
                'message' => 'Operation successful',
                'type' => 'success'
            ];
            $twigFunc = Utils::flashMessage();
            $callable = $twigFunc->getCallable();

            $result = $callable('success');
            expect($result)->toBeArray();
            expect($result['message'])->toBe('Operation successful');
            expect($result['type'])->toBe('success');
        });

        it('returns null when no flash message exists', function () {
            $twigFunc = Utils::flashMessage();
            $callable = $twigFunc->getCallable();

            expect($callable('error'))->toBeFalse();
        });

        it('handles different message types', function () {
            $_SESSION['FLASH_MESSAGES']['success'] = [
                'message' => 'Success',
                'type' => 'success'
            ];
            $_SESSION['FLASH_MESSAGES']['error'] = [
                'message' => 'Error',
                'type' => 'error'
            ];

            $twigFunc = Utils::flashMessage();
            $callable = $twigFunc->getCallable();

            $successResult = $callable('success');
            expect($successResult)->toBeArray();
            expect($successResult['message'])->toBe('Success');

            $errorResult = $callable('error');
            expect($errorResult)->toBeArray();
            expect($errorResult['message'])->toBe('Error');
        });
    });

    describe('routeUrl', function () {
        it('returns a TwigFunction', function () {
            expect(Utils::routeUrl())->toBeInstanceOf('Twig\TwigFunction');
        });

        it('TwigFunction returns the action string unchanged', function () {
            $twigFunc = Utils::routeUrl();
            $callable = $twigFunc->getCallable();

            expect($callable('users.index'))->toBe('users.index');
        });

        it('handles different route names', function () {
            $twigFunc = Utils::routeUrl();
            $callable = $twigFunc->getCallable();

            expect($callable('home'))->toBe('home');
            expect($callable('about.us'))->toBe('about.us');
            expect($callable('/api/users'))->toBe('/api/users');
        });

        it('handles empty string', function () {
            $twigFunc = Utils::routeUrl();
            $callable = $twigFunc->getCallable();

            expect($callable(''))->toBe('');
        });
    });

    describe('conditional', function () {
        it('returns a TwigFunction', function () {
            expect(Utils::conditional())->toBeInstanceOf('Twig\TwigFunction');
        });

        it('returns first value when condition is true', function () {
            $twigFunc = Utils::conditional();
            $callable = $twigFunc->getCallable();

            expect($callable(true, 'yes', 'no'))->toBe('yes');
        });

        it('returns second value when condition is false', function () {
            $twigFunc = Utils::conditional();
            $callable = $twigFunc->getCallable();

            expect($callable(false, 'yes', 'no'))->toBe('no');
        });
    });

    describe('renderIf', function () {
        it('returns a TwigFunction', function () {
            expect(Utils::renderIf())->toBeInstanceOf('Twig\TwigFunction');
        });

        it('returns content when condition is true', function () {
            $twigFunc = Utils::renderIf();
            $callable = $twigFunc->getCallable();

            expect($callable('content', true))->toBe('content');
        });

        it('returns null when condition is false', function () {
            $twigFunc = Utils::renderIf();
            $callable = $twigFunc->getCallable();

            expect($callable('content', false))->toBeNull();
        });

        it('handles HTML content', function () {
            $twigFunc = Utils::renderIf();
            $callable = $twigFunc->getCallable();

            expect($callable('<div>HTML</div>', true))->toBe('<div>HTML</div>');
            expect($callable('<script>alert("test")</script>', false))->toBeNull();
        });
    });

    describe('asset', function () {
        it('returns a TwigFunction', function () {
            expect(Utils::asset())->toBeInstanceOf('Twig\TwigFunction');
        });

        it('returns empty string when manifest not found', function () {
            $twigFunc = Utils::asset();
            $callable = $twigFunc->getCallable();

            expect($callable('app.js'))->toBe('');
        });

        it('reads manifest file when available', function () {
            $manifestPath = './public/dist/manifest.json';

            if (!is_dir('./public/dist')) {
                mkdir('./public/dist', 0777, true);
            }

            file_put_contents($manifestPath, json_encode([
                'App/Assets/app.js' => ['file' => 'app.abc123.js'],
                'App/Assets/style.css' => ['file' => 'style.def456.css']
            ]));

            $twigFunc = Utils::asset();
            $callable = $twigFunc->getCallable();

            expect($callable('app.js'))->toBe('/public/dist/app.abc123.js');
            expect($callable('style.css'))->toBe('/public/dist/style.def456.css');

            unlink($manifestPath);
            @rmdir('./public/dist');
            @rmdir('./public');
        });

        it('returns empty string if asset not in manifest', function () {
            $manifestPath = './public/dist/manifest.json';

            if (!is_dir('./public/dist')) {
                mkdir('./public/dist', 0777, true);
            }

            file_put_contents($manifestPath, json_encode([
                'App/Assets/app.js' => ['file' => 'app.hash.js']
            ]));

            $twigFunc = Utils::asset();
            $callable = $twigFunc->getCallable();

            expect($callable('unknown.js'))->toBe('');

            unlink($manifestPath);
            @rmdir('./public/dist');
            @rmdir('./public');
        });

        it('returns empty string on invalid JSON', function () {
            $manifestPath = './public/dist/manifest.json';

            if (!is_dir('./public/dist')) {
                mkdir('./public/dist', 0777, true);
            }

            file_put_contents($manifestPath, 'invalid json');

            $twigFunc = Utils::asset();
            $callable = $twigFunc->getCallable();

            expect($callable('app.js'))->toBe('');

            unlink($manifestPath);
            @rmdir('./public/dist');
            @rmdir('./public');
        });
    });

    describe('all', function () {
        it('returns array of TwigFunctions', function () {
            $utils = Utils::all();

            expect($utils)->toBeArray();
            expect($utils)->toHaveCount(5);

            foreach ($utils as $func) {
                expect($func)->toBeInstanceOf('Twig\TwigFunction');
            }
        });

        it('includes all utility functions', function () {
            $utils = Utils::all();
            $names = array_map(fn($f) => $f->getName(), $utils);

            expect($names)->toContain('asset');
            expect($names)->toContain('showFlashMessage');
            expect($names)->toContain('routeUrl');
            expect($names)->toContain('conditional');
            expect($names)->toContain('renderIf');
        });
    });

    describe('edge cases', function () {
        it('handles special characters in route URLs', function () {
            $twigFunc = Utils::routeUrl();
            $callable = $twigFunc->getCallable();

            expect($callable('post-details'))->toBe('post-details');
            expect($callable('admin/users'))->toBe('admin/users');
        });

        it('conditional handles nested logic', function () {
            $conditional = Utils::conditional();
            $callable = $conditional->getCallable();

            $result = $callable(
                true,
                $callable(false, 'a', 'b'),
                'c'
            );

            expect($result)->toBe('b');
        });

        it('renderIf handles empty string content', function () {
            $twigFunc = Utils::renderIf();
            $callable = $twigFunc->getCallable();

            expect($callable('', true))->toBe('');
        });
    });
});
