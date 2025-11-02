<?php declare(strict_types=1);

use Lalaz\Core\Config;
use Lalaz\Http\SessionManager;

describe('SessionManager', function() {
    beforeEach(function() {
        // Set up config
        Config::set('SESSION_LIFETIME', '3600');

        // Clean up any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $_SESSION = [];
    });

    afterEach(function() {
        // Clean up session after each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $_SESSION = [];
    });    describe('Session Lifecycle', function() {
        it('starts session if not already started', function() {
            SessionManager::start();

            expect(session_status())->toBe(PHP_SESSION_ACTIVE);
        });

        it('does not restart session if already active', function() {
            SessionManager::start();
            $sessionId1 = session_id();

            SessionManager::start();
            $sessionId2 = session_id();

            expect($sessionId1)->toBe($sessionId2);
        });

        it('destroys session completely', function() {
            SessionManager::start();
            SessionManager::set('test', 'value');

            SessionManager::destroy();

            expect(session_status())->toBe(PHP_SESSION_NONE);
        });
    });

    describe('Data Storage and Retrieval', function() {
        it('sets session value', function() {
            SessionManager::set('username', 'john_doe');

            expect($_SESSION['username'])->toBe('john_doe');
        });

        it('gets session value', function() {
            SessionManager::set('user_id', 123);

            $value = SessionManager::get('user_id');

            expect($value)->toBe(123);
        });

        it('returns null for non-existent key', function() {
            $value = SessionManager::get('nonexistent');

            expect($value)->toBeNull();
        });

        it('stores different data types', function() {
            SessionManager::set('string', 'text');
            SessionManager::set('integer', 42);
            SessionManager::set('float', 3.14);
            SessionManager::set('boolean', true);
            SessionManager::set('array', ['a', 'b', 'c']);
            SessionManager::set('object', (object)['key' => 'value']);

            expect(SessionManager::get('string'))->toBe('text');
            expect(SessionManager::get('integer'))->toBe(42);
            expect(SessionManager::get('float'))->toBe(3.14);
            expect(SessionManager::get('boolean'))->toBe(true);
            expect(SessionManager::get('array'))->toBe(['a', 'b', 'c']);
            expect(SessionManager::get('object'))->toEqual((object)['key' => 'value']);
        });
    });

    describe('Data Removal', function() {
        it('removes specific session key', function() {
            SessionManager::set('temp', 'data');
            SessionManager::set('keep', 'this');

            SessionManager::unset('temp');

            expect(SessionManager::get('temp'))->toBeNull();
            expect(SessionManager::get('keep'))->toBe('this');
        });

        it('does not throw error when unsetting non-existent key', function() {
            expect(fn() => SessionManager::unset('nonexistent'))->not->toThrow(Exception::class);
        });
    });

    describe('Edge Cases', function() {
        it('handles empty string key', function() {
            SessionManager::set('', 'empty_key');

            expect(SessionManager::get(''))->toBe('empty_key');
        });

        it('handles null value', function() {
            SessionManager::set('null_value', null);

            expect(SessionManager::get('null_value'))->toBeNull();
        });

        it('overwrites existing value', function() {
            SessionManager::set('key', 'first');
            SessionManager::set('key', 'second');

            expect(SessionManager::get('key'))->toBe('second');
        });

        it('handles session across multiple operations', function() {
            SessionManager::set('counter', 0);

            for ($i = 1; $i <= 5; $i++) {
                $current = SessionManager::get('counter');
                SessionManager::set('counter', $current + 1);
            }

            expect(SessionManager::get('counter'))->toBe(5);
        });
    });

    describe('Session State Management', function() {
        it('starts session before setting value', function() {
            SessionManager::set('auto_start', 'test');

            expect(session_status())->toBe(PHP_SESSION_ACTIVE);
        });

        it('starts session before getting value', function() {
            SessionManager::get('any_key');

            expect(session_status())->toBe(PHP_SESSION_ACTIVE);
        });

        it('starts session before unsetting value', function() {
            SessionManager::unset('any_key');

            expect(session_status())->toBe(PHP_SESSION_ACTIVE);
        });
    });
});
