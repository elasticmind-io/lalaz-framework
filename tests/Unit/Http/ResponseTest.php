<?php declare(strict_types=1);

use Lalaz\Http\Response;
use Lalaz\View\ViewContext;

describe('Response', function() {

    beforeEach(function() {
        // Reset session
        $_SESSION = [];
    });    describe('Constructor', function() {
        it('initializes with session data', function() {
            $_SESSION = ['user_id' => '42'];

            $response = new Response();

            expect($response)->toBeInstanceOf(Response::class);
        });
    });

    describe('View Data', function() {
        it('adds view data to ViewContext', function() {
            $response = new Response();

            $result = $response->addViewData('username', 'John');

            expect($result)->toBe($response); // Method chaining
            expect(ViewContext::get('username'))->toBe('John');
        });

        it('adds lazy-loaded view data', function() {
            $response = new Response();

            $response->addViewData('user', fn() => ['id' => 1, 'name' => 'John']);

            $user = ViewContext::get('user');
            expect($user)->toBeArray();
        });        it('allows method chaining', function() {
            $response = new Response();

            $result = $response
                ->addViewData('key1', 'value1')
                ->addViewData('key2', 'value2');

            expect($result)->toBe($response);
        });
    });

    describe('Session Management', function() {
        it('adds data to session', function() {
            $response = new Response();

            $result = $response->addSession('user_id', '42');

            expect($_SESSION['user_id'])->toBe('42');
            expect($result)->toBe($response); // Method chaining
        });

        it('adds multiple session values', function() {
            $response = new Response();

            $response->addSession('user_id', '42');
            $response->addSession('username', 'john');

            expect($_SESSION['user_id'])->toBe('42');
            expect($_SESSION['username'])->toBe('john');
        });

        it('destroys session', function() {
            // Start a session before destroying
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['test'] = 'value';

            $response = new Response();
            $result = $response->destroySession();

            expect($result)->toBe($response);
        });
    });

    describe('Cookie Management', function() {
        it('adds cookie with default parameters', function() {
            $response = new Response();

            $result = $response->addCookie('session_id', 'abc123', time() + 3600);

            expect($result)->toBe($response); // Method chaining
        });

        it('adds cookie with custom parameters', function() {
            $response = new Response();

            $result = $response->addCookie(
                'user_id',
                '42',
                time() + 7200,
                '/admin',
                'example.com',
                true,
                false
            );

            expect($result)->toBe($response);
        });

        it('deletes cookie', function() {
            $response = new Response();

            $result = $response->deleteCookie('session_id');

            expect($result)->toBe($response);
        });
    });

    describe('Flash Messages', function() {
        it('creates flash message', function() {
            $response = new Response();

            $result = $response->flash('Operation successful', 'success', 'flash');

            expect($result)->toBe($response);
            expect($_SESSION)->toHaveKey('FLASH_MESSAGES');
            expect($_SESSION['FLASH_MESSAGES'])->toHaveKey('flash');
        });

        it('creates flash message with custom name', function() {
            $response = new Response();

            $response->flash('User created', 'success', 'user_flash');

            expect($_SESSION['FLASH_MESSAGES'])->toHaveKey('user_flash');
            expect($_SESSION['FLASH_MESSAGES']['user_flash']['message'])->toBe('User created');
            expect($_SESSION['FLASH_MESSAGES']['user_flash']['type'])->toBe('success');
        });

        it('skips creating flash when parameters are empty', function() {
            $response = new Response();

            $result = $response->flash('', '', '');

            expect($result)->toBe($response);
        });

        it('allows method chaining', function() {
            $response = new Response();

            $result = $response
                ->flash('Message 1', 'success', 'flash1')
                ->flash('Message 2', 'error', 'flash2');

            expect($result)->toBe($response);
        });
    });    describe('JSON Response', function() {
        it('sends JSON response with data', function() {
            $response = new Response();

            ob_start();
            $response->json(['status' => 'success', 'data' => ['id' => 1]]);
            $output = ob_get_clean();

            $decoded = json_decode($output, true);
            expect($decoded)->toHaveKey('status');
            expect($decoded['status'])->toBe('success');
            expect($decoded['data']['id'])->toBe(1);
        });

        it('sends JSON response with custom status code', function() {
            $response = new Response();

            ob_start();
            $response->json(['error' => 'Not found'], 404);
            $output = ob_get_clean();

            $decoded = json_decode($output, true);
            expect($decoded)->toHaveKey('error');
        });

        it('sends empty JSON response', function() {
            $response = new Response();

            ob_start();
            $response->json();
            $output = ob_get_clean();

            expect($output)->toBe('[]');
        });

        it('sends JSON with array data', function() {
            $response = new Response();

            ob_start();
            $response->json([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ]);
            $output = ob_get_clean();

            $decoded = json_decode($output, true);
            expect($decoded)->toBeArray();
            expect($decoded)->toHaveCount(2);
        });
    });

    describe('Method Chaining', function() {
        it('chains session and cookie operations', function() {
            $response = new Response();

            $result = $response
                ->addSession('user_id', '42')
                ->addCookie('session', 'abc', time() + 3600);

            expect($result)->toBe($response);
            expect($_SESSION['user_id'])->toBe('42');
        });

        it('chains view data and flash messages', function() {
            $response = new Response();

            $result = $response
                ->addViewData('username', 'John')
                ->flash('Welcome!', 'success', 'welcome');

            expect($result)->toBe($response);
        });
    });

    describe('Edge Cases', function() {
        it('handles empty session additions', function() {
            $response = new Response();

            $response->addSession('empty', '');

            expect($_SESSION['empty'])->toBe('');
        });

        it('handles numeric session values', function() {
            $response = new Response();

            $response->addSession('count', 42);

            expect($_SESSION['count'])->toBe(42);
        });

        it('handles array session values', function() {
            $response = new Response();

            $response->addSession('user', ['id' => 1, 'name' => 'John']);

            expect($_SESSION['user'])->toBeArray();
            expect($_SESSION['user'])->toHaveKey('id');
        });

        it('handles JSON with special characters', function() {
            $response = new Response();

            ob_start();
            $response->json(['message' => "Hello \"World\"", 'tag' => '<script>']);
            $output = ob_get_clean();

            $decoded = json_decode($output, true);
            expect($decoded['message'])->toBe('Hello "World"');
            expect($decoded['tag'])->toBe('<script>');
        });

        it('handles JSON with null values', function() {
            $response = new Response();

            ob_start();
            $response->json(['value' => null, 'empty' => '']);
            $output = ob_get_clean();

            $decoded = json_decode($output, true);
            expect($decoded['value'])->toBeNull();
            expect($decoded['empty'])->toBe('');
        });
    });
});
