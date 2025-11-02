<?php

use Lalaz\Data\Query\Queries;
use Lalaz\Data\Query\DeleteQueryBuilder;

describe('DeleteQueryBuilder', function() {

    describe('Basic DELETE', function() {
        it('builds simple DELETE query', function() {
            $query = Queries::delete()
                ->from('users');

            expect($query->build())
                ->toBe('DELETE FROM users');
        });

        it('builds DELETE with WHERE', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('id = 1');

            expect($query->build())
                ->toBe('DELETE FROM users WHERE id = 1');
        });

        it('builds DELETE with multiple WHERE conditions', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('active = 0', 'verified = 0');

            expect($query->build())
                ->toBe('DELETE FROM users WHERE active = 0 AND verified = 0');
        });

        it('builds DELETE with AND conditions', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('role = "guest"')
                ->where('last_login < "2023-01-01"');

            expect($query->build())
                ->toContain('DELETE FROM users')
                ->toContain('WHERE')
                ->toContain('AND');
        });
    });

    describe('Multiple Tables', function() {
        it('builds DELETE from multiple tables', function() {
            $query = Queries::delete()
                ->from('users')
                ->from('profiles');

            expect($query->build())
                ->toBe('DELETE FROM users, profiles');
        });

        it('builds DELETE from multiple tables with WHERE', function() {
            $query = Queries::delete()
                ->from('users')
                ->from('profiles')
                ->where('users.id = profiles.user_id', 'users.deleted = 1');

            $sql = $query->build();

            expect($sql)
                ->toContain('DELETE FROM users, profiles')
                ->toContain('WHERE users.id = profiles.user_id AND users.deleted = 1');
        });
    });

    describe('WHERE Conditions', function() {
        it('handles equality condition', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('id = 1');

            expect($query->build())
                ->toContain('WHERE id = 1');
        });

        it('handles comparison operators', function() {
            $query = Queries::delete()
                ->from('logs')
                ->where('created_at < "2023-01-01"');

            expect($query->build())
                ->toContain('WHERE created_at < "2023-01-01"');
        });

        it('handles LIKE operator', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('email LIKE "%@spam.com"');

            expect($query->build())
                ->toContain('WHERE email LIKE "%@spam.com"');
        });

        it('handles IN operator', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('id IN (1, 2, 3, 4, 5)');

            expect($query->build())
                ->toContain('WHERE id IN (1, 2, 3, 4, 5)');
        });

        it('handles IS NULL', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('deleted_at IS NOT NULL');

            expect($query->build())
                ->toContain('WHERE deleted_at IS NOT NULL');
        });

        it('handles complex conditions', function() {
            $query = Queries::delete()
                ->from('sessions')
                ->where('expired = 1')
                ->where('last_activity < NOW() - INTERVAL 30 DAY')
                ->where('user_id IS NULL');

            $sql = $query->build();

            expect($sql)
                ->toContain('DELETE FROM sessions')
                ->toContain('WHERE expired = 1')
                ->toContain('AND last_activity < NOW() - INTERVAL 30 DAY')
                ->toContain('AND user_id IS NULL');
        });
    });

    describe('Method Chaining', function() {
        it('allows fluent interface', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('active = 0')
                ->where('created_at < "2023-01-01"');

            expect($query)->toBeInstanceOf(DeleteQueryBuilder::class);
        });

        it('can be cloned and modified', function() {
            $base = Queries::delete()->from('users');

            $query1 = clone $base;
            $query1->where('role = "admin"');

            $query2 = clone $base;
            $query2->where('role = "user"');

            $sql1 = $query1->build();
            $sql2 = $query2->build();

            expect($sql1)->toContain('role = "admin"')
                ->and($sql2)->toContain('role = "user"')
                ->and($sql1)->not->toBe($sql2);
        });

        it('can chain multiple from and where calls', function() {
            $query = Queries::delete()
                ->from('users')
                ->from('profiles')
                ->where('users.id = profiles.user_id')
                ->where('users.active = 0');

            $sql = $query->build();

            expect($sql)
                ->toContain('users, profiles')
                ->toContain('users.id = profiles.user_id')
                ->toContain('users.active = 0');
        });
    });

    describe('Edge Cases', function() {
        it('throws when no FROM clause', function() {
            $query = Queries::delete();

            expect(fn() => $query->build())
                ->toThrow(Exception::class);
        });

        it('builds DELETE without WHERE (deletes all)', function() {
            $query = Queries::delete()
                ->from('temp_data');

            expect($query->build())
                ->toBe('DELETE FROM temp_data');
        });

        it('handles table names with schema', function() {
            $query = Queries::delete()
                ->from('public.users')
                ->where('id = 1');

            expect($query->build())
                ->toContain('DELETE FROM public.users');
        });

        it('handles quoted table names', function() {
            $query = Queries::delete()
                ->from('`users`')
                ->where('id = 1');

            expect($query->build())
                ->toContain('DELETE FROM `users`');
        });

        it('handles parameterized conditions', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('id = :id', 'email = :email');

            expect($query->build())
                ->toContain('id = :id')
                ->toContain('email = :email');
        });
    });

    describe('Real-world Scenarios', function() {
        it('deletes inactive users older than 1 year', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('active = 0')
                ->where('last_login < DATE_SUB(NOW(), INTERVAL 1 YEAR)');

            $sql = $query->build();

            expect($sql)
                ->toContain('DELETE FROM users')
                ->toContain('WHERE active = 0')
                ->toContain('AND last_login < DATE_SUB(NOW(), INTERVAL 1 YEAR)');
        });

        it('deletes orphaned records', function() {
            $query = Queries::delete()
                ->from('comments')
                ->where('post_id NOT IN (SELECT id FROM posts)');

            expect($query->build())
                ->toContain('DELETE FROM comments')
                ->toContain('WHERE post_id NOT IN (SELECT id FROM posts)');
        });

        it('deletes expired sessions', function() {
            $query = Queries::delete()
                ->from('sessions')
                ->where('expires_at < NOW()');

            expect($query->build())
                ->toBe('DELETE FROM sessions WHERE expires_at < NOW()');
        });

        it('deletes soft-deleted records permanently', function() {
            $query = Queries::delete()
                ->from('users')
                ->where('deleted_at IS NOT NULL')
                ->where('deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');

            $sql = $query->build();

            expect($sql)
                ->toContain('WHERE deleted_at IS NOT NULL')
                ->toContain('AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
        });
    });
});
