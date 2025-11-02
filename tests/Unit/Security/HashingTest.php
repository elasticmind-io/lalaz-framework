<?php declare(strict_types=1);

use Lalaz\Security\Hashing;

describe('Hashing', function () {
    describe('class structure', function () {
        it('is a class', function () {
            expect(class_exists(Hashing::class))->toBeTrue();
        });

        it('uses PasswordHash trait', function () {
            $traits = class_uses(Hashing::class);

            expect($traits)->toContain('Lalaz\Security\PasswordHash');
        });

        it('can be instantiated', function () {
            $hashing = new Hashing();

            expect($hashing)->toBeInstanceOf(Hashing::class);
        });
    });

    describe('generateHash()', function () {
        it('generates a hash from plain text password', function () {
            $password = 'mySecurePassword123';
            $hash = Hashing::generateHash($password);

            expect($hash)->toBeString();
            expect($hash)->not->toBe($password);
            expect(strlen($hash))->toBeGreaterThan(50);
        });

        it('generates different hashes for same password', function () {
            $password = 'testPassword';
            $hash1 = Hashing::generateHash($password);
            $hash2 = Hashing::generateHash($password);

            expect($hash1)->not->toBe($hash2);
        });

        it('uses Argon2ID algorithm', function () {
            $password = 'testPassword123';
            $hash = Hashing::generateHash($password);

            expect($hash)->toStartWith('$argon2id$');
        });

        it('handles empty string', function () {
            $hash = Hashing::generateHash('');

            expect($hash)->toBeString();
            expect($hash)->toStartWith('$argon2id$');
        });

        it('handles special characters', function () {
            $password = '!@#$%^&*()_+-=';
            $hash = Hashing::generateHash($password);

            expect($hash)->toBeString();
            expect($hash)->toStartWith('$argon2id$');
        });
    });

    describe('verifyHash()', function () {
        it('verifies correct password', function () {
            $password = 'myPassword123';
            $hash = Hashing::generateHash($password);

            $result = Hashing::verifyHash($password, $hash);

            expect($result)->toBeTrue();
        });

        it('rejects incorrect password', function () {
            $password = 'correctPassword';
            $wrongPassword = 'wrongPassword';
            $hash = Hashing::generateHash($password);

            $result = Hashing::verifyHash($wrongPassword, $hash);

            expect($result)->toBeFalse();
        });

        it('is case sensitive', function () {
            $password = 'Password123';
            $hash = Hashing::generateHash($password);

            expect(Hashing::verifyHash('password123', $hash))->toBeFalse();
            expect(Hashing::verifyHash('PASSWORD123', $hash))->toBeFalse();
            expect(Hashing::verifyHash('Password123', $hash))->toBeTrue();
        });

        it('rejects invalid hash format', function () {
            $result = Hashing::verifyHash('password', 'invalid_hash');

            expect($result)->toBeFalse();
        });
    });

    describe('static usage', function () {
        it('can be used without instantiation', function () {
            $password = 'staticTest';
            $hash = Hashing::generateHash($password);

            expect(Hashing::verifyHash($password, $hash))->toBeTrue();
        });

        it('provides convenient facade for password operations', function () {
            // Common use case: hash on registration
            $userPassword = 'newUserPassword';
            $hashedPassword = Hashing::generateHash($userPassword);

            // Common use case: verify on login
            $loginAttempt = 'newUserPassword';
            $isValid = Hashing::verifyHash($loginAttempt, $hashedPassword);

            expect($isValid)->toBeTrue();
        });
    });

    describe('security features', function () {
        it('uses memory cost parameter', function () {
            $hash = Hashing::generateHash('test');

            expect($hash)->toContain('m=2048');
        });

        it('uses time cost parameter', function () {
            $hash = Hashing::generateHash('test');

            expect($hash)->toContain('t=4');
        });

        it('uses thread parameter', function () {
            $hash = Hashing::generateHash('test');

            expect($hash)->toContain('p=3');
        });
    });
});
