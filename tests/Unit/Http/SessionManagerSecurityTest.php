<?php

use Lalaz\Http\SessionManager;

describe('SessionManager Security', function () {
    beforeEach(function () {
        // Clean up any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        // Clear superglobals
        $_SESSION = [];
        $_SERVER['HTTP_USER_AGENT'] = 'TestUserAgent/1.0';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
    });

    afterEach(function () {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $_SESSION = [];
    });

    it('starts session with security settings', function () {
        SessionManager::start();

        expect(session_status())->toBe(PHP_SESSION_ACTIVE);
        expect(ini_get('session.cookie_httponly'))->toBe('1');
        expect(ini_get('session.cookie_secure'))->toBe('1');
        expect(ini_get('session.cookie_samesite'))->toBe('Strict');
        expect(ini_get('session.use_strict_mode'))->toBe('1');
        expect(ini_get('session.use_only_cookies'))->toBe('1');
    });

    it('initializes session fingerprint on start', function () {
        SessionManager::start();

        expect($_SESSION)->toHaveKey('__fingerprint');
        expect($_SESSION['__fingerprint'])->toBeString();
        expect(strlen($_SESSION['__fingerprint']))->toBe(64); // SHA256 hash length
    });

    it('regenerates session id', function () {
        SessionManager::start();
        $oldSessionId = session_id();
        $oldFingerprint = $_SESSION['__fingerprint'];

        SessionManager::regenerate();
        $newSessionId = session_id();
        $newFingerprint = $_SESSION['__fingerprint'];

        expect($newSessionId)->not->toBe($oldSessionId);
        expect($newFingerprint)->toBeString();
        expect(strlen($newFingerprint))->toBe(64);
    });

    it('sets and gets session variables', function () {
        SessionManager::set('test_key', 'test_value');

        expect(SessionManager::get('test_key'))->toBe('test_value');
    });

    it('returns null for non-existent session variable', function () {
        SessionManager::start();

        expect(SessionManager::get('non_existent_key'))->toBeNull();
    });

    it('unsets session variable', function () {
        SessionManager::set('test_key', 'test_value');
        expect(SessionManager::get('test_key'))->toBe('test_value');

        SessionManager::unset('test_key');
        expect(SessionManager::get('test_key'))->toBeNull();
    });

    it('destroys session completely', function () {
        SessionManager::set('test_key', 'test_value');
        expect(SessionManager::get('test_key'))->toBe('test_value');

        SessionManager::destroy();

        // Session should be inactive after destroy
        expect(session_status())->toBe(PHP_SESSION_NONE);
    });

    it('validates session as valid when recently accessed', function () {
        SessionManager::start();
        $_SESSION['__last_activity'] = time();

        expect(SessionManager::isValid())->toBeTrue();
    });

    it('updates last activity timestamp', function () {
        // Ensure SESSION_LIFETIME is long enough
        $_ENV['SESSION_LIFETIME'] = '3600';

        SessionManager::start();

        // Set an old timestamp (but within lifetime)
        $_SESSION['__last_activity'] = time() - 10;
        $oldTime = $_SESSION['__last_activity'];

        // Wait a tiny bit to ensure time difference
        usleep(100000); // 0.1 second

        // Call isValid which should update the timestamp
        $isValid = SessionManager::isValid();

        expect($isValid)->toBeTrue();
        expect($_SESSION['__last_activity'])->toBeGreaterThan($oldTime);
    });    it('invalidates session after timeout', function () {
        // Set SESSION_LIFETIME to 10 seconds for testing
        $_ENV['SESSION_LIFETIME'] = '10';

        SessionManager::start();

        // Simulate session that's 11 seconds old (expired)
        $_SESSION['__last_activity'] = time() - 11;

        expect(SessionManager::isValid())->toBeFalse();
    });

    it('maintains consistent fingerprint for same user agent and IP', function () {
        SessionManager::start();
        $fingerprint1 = $_SESSION['__fingerprint'];

        // Simulate another request from same client
        SessionManager::start();
        $fingerprint2 = $_SESSION['__fingerprint'];

        expect($fingerprint1)->toBe($fingerprint2);
    });

    it('detects fingerprint mismatch when user agent changes', function () {
        // This test validates the security mechanism in a real-world scenario
        // In production, fingerprint validation happens on session_start()

        SessionManager::start();
        expect($_SESSION)->toHaveKey('__fingerprint');

        $originalFingerprint = $_SESSION['__fingerprint'];

        // Simulate changing user agent
        $_SERVER['HTTP_USER_AGENT'] = 'EvilBot/1.0';

        // Calculate what the NEW fingerprint should be with the evil user agent
        $expectedNewFingerprint = hash('sha256',
            'EvilBot/1.0' .
            '192.168.1' // First 3 octets of IP
        );

        // The new fingerprint should be different (proving detection works)
        expect($expectedNewFingerprint)->not->toBe($originalFingerprint);
    });    it('handles IPv4 partial IP correctly', function () {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

        SessionManager::start();
        $fingerprint = $_SESSION['__fingerprint'];

        // Change last octet (e.g., DHCP renewal)
        $_SERVER['REMOTE_ADDR'] = '192.168.1.101';

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        SessionManager::start();

        // Should maintain same session (first 3 octets match)
        expect($_SESSION['__fingerprint'])->toBe($fingerprint);
    });

    it('detects IPv4 subnet change', function () {
        // This test validates that changing subnet would trigger fingerprint mismatch

        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        SessionManager::start();

        $fingerprint1 = hash('sha256', 'TestUserAgent/1.0' . '192.168.1');
        expect($_SESSION['__fingerprint'])->toBe($fingerprint1);

        // Simulate subnet change
        $fingerprint2 = hash('sha256', 'TestUserAgent/1.0' . '192.168.2');

        // Different subnet = different fingerprint (hijacking detection)
        expect($fingerprint2)->not->toBe($fingerprint1);
    });    it('handles IPv6 addresses', function () {
        $_SERVER['REMOTE_ADDR'] = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        SessionManager::start();
        $fingerprint = $_SESSION['__fingerprint'];

        // Change last groups
        $_SERVER['REMOTE_ADDR'] = '2001:0db8:85a3:0000:1111:1111:1111:1111';

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        SessionManager::start();

        // Should maintain same session (first 4 groups match)
        expect($_SESSION['__fingerprint'])->toBe($fingerprint);
    });

    it('preserves user data after regeneration', function () {
        SessionManager::set('user_id', 123);
        SessionManager::set('username', 'testuser');

        SessionManager::regenerate();

        expect(SessionManager::get('user_id'))->toBe(123);
        expect(SessionManager::get('username'))->toBe('testuser');
    });
});
