<?php declare(strict_types=1);

use Lalaz\Data\DatabaseHelper;
use Lalaz\Data\Adapters\SQLiteAdapter;

describe('DatabaseHelper', function () {

    beforeEach(function () {
        $this->adapter = new SQLiteAdapter(['path' => ':memory:']);
    });

    describe('quoteIdentifier()', function () {

        it('quotes SQLite identifiers with double quotes', function () {
            $result = DatabaseHelper::quoteIdentifier('users', $this->adapter);
            expect($result)->toBe('"users"');
        });

        it('rejects identifiers with double quotes', function () {
            // Double quotes should be rejected as invalid characters
            expect(fn() => DatabaseHelper::quoteIdentifier('table"with"quotes', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception for invalid identifiers', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier('users; DROP TABLE students--', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('rejects identifiers with semicolons', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier('users;', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('rejects identifiers with hyphens (SQL comments)', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier('users--', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('rejects identifiers with special SQL characters', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier("users'OR'1'='1", $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('accepts valid identifiers with underscores', function () {
            $result = DatabaseHelper::quoteIdentifier('user_profiles', $this->adapter);
            expect($result)->toBe('"user_profiles"');
        });

        it('accepts valid identifiers with numbers', function () {
            $result = DatabaseHelper::quoteIdentifier('table123', $this->adapter);
            expect($result)->toBe('"table123"');
        });

        it('accepts valid qualified identifiers', function () {
            // quoteIdentifier treats dots as part of the identifier, not separators
            $result = DatabaseHelper::quoteIdentifier('schema.table', $this->adapter);
            expect($result)->toBe('"schema.table"');
        });
    });

    describe('quoteIdentifiers()', function () {

        it('quotes multiple identifiers', function () {
            $result = DatabaseHelper::quoteIdentifiers(['id', 'name', 'email'], ', ', $this->adapter);
            expect($result)->toBe('"id", "name", "email"');
        });

        it('uses custom separator', function () {
            $result = DatabaseHelper::quoteIdentifiers(['id', 'name'], ' AND ', $this->adapter);
            expect($result)->toBe('"id" AND "name"');
        });

        it('throws exception if any identifier is invalid', function () {
            expect(fn() => DatabaseHelper::quoteIdentifiers(['id', 'name; DROP TABLE users--'], ', ', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('quoteQualifiedIdentifier()', function () {

        it('quotes schema.table format', function () {
            $result = DatabaseHelper::quoteQualifiedIdentifier('public.users', $this->adapter);
            expect($result)->toBe('"public"."users"');
        });

        it('quotes table.column format', function () {
            $result = DatabaseHelper::quoteQualifiedIdentifier('users.email', $this->adapter);
            expect($result)->toBe('"users"."email"');
        });

        it('quotes database.schema.table format', function () {
            $result = DatabaseHelper::quoteQualifiedIdentifier('mydb.public.users', $this->adapter);
            expect($result)->toBe('"mydb"."public"."users"');
        });
    });

    describe('buildDropTableIfExists()', function () {

        it('builds safe DROP TABLE statement', function () {
            $result = DatabaseHelper::buildDropTableIfExists('users', $this->adapter);
            expect($result)->toBe('DROP TABLE IF EXISTS "users"');
        });

        it('quotes table name in DROP TABLE statement', function () {
            $result = DatabaseHelper::buildDropTableIfExists('user_profiles', $this->adapter);
            expect($result)->toBe('DROP TABLE IF EXISTS "user_profiles"');
        });

        it('throws exception for malicious table names', function () {
            expect(fn() => DatabaseHelper::buildDropTableIfExists('users; DROP TABLE students--', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('buildTableExistsCheck()', function () {

        it('builds safe table existence check', function () {
            $result = DatabaseHelper::buildTableExistsCheck('users', $this->adapter);
            expect($result)->toBe('SELECT 1 FROM "users" LIMIT 1');
        });

        it('quotes table name in existence check', function () {
            $result = DatabaseHelper::buildTableExistsCheck('user_profiles', $this->adapter);
            expect($result)->toBe('SELECT 1 FROM "user_profiles" LIMIT 1');
        });
    });

    describe('SQL Injection Prevention', function () {

        it('prevents SQL injection via table names', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier("users' OR '1'='1", $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('prevents SQL injection via command injection', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier('users; DROP TABLE students', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('prevents SQL injection via comment injection', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier('users-- DROP TABLE students', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('prevents SQL injection via union injection', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier('users UNION SELECT * FROM passwords', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('allows safe special case: dot for qualified names', function () {
            // Use quoteQualifiedIdentifier for splitting on dots
            $result = DatabaseHelper::quoteQualifiedIdentifier('schema.table', $this->adapter);
            expect($result)->toBe('"schema"."table"');
        });
    });

    describe('Edge Cases', function () {

        it('handles empty identifier', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier('', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('handles whitespace-only identifier', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier('   ', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('handles identifier with leading/trailing spaces', function () {
            expect(fn() => DatabaseHelper::quoteIdentifier(' users ', $this->adapter))
                ->toThrow(InvalidArgumentException::class);
        });

        it('handles very long identifiers', function () {
            $longName = str_repeat('a', 200);
            $result = DatabaseHelper::quoteIdentifier($longName, $this->adapter);
            expect($result)->toBe("\"{$longName}\"");
        });
    });

    describe('Performance', function () {

        it('quotes thousands of identifiers efficiently', function () {
            $identifiers = array_map(fn($i) => "table_{$i}", range(1, 1000));

            $start = microtime(true);
            DatabaseHelper::quoteIdentifiers($identifiers, ', ', $this->adapter);
            $elapsed = microtime(true) - $start;

            expect($elapsed)->toBeLessThan(0.1);
        });
    });
});
