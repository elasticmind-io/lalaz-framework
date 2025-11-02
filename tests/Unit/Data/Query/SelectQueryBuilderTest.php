<?php

use Lalaz\Data\Query\Queries;
use Lalaz\Data\Query\SelectQueryBuilder;

describe('SelectQueryBuilder', function() {

    describe('Basic SELECT', function() {
        it('builds simple SELECT query', function() {
            $query = Queries::select('id', 'name', 'email')
                ->from('users');

            $sql = $query->build();

            expect($sql)
                ->toContain('SELECT id, name, email')
                ->toContain('FROM users');
        });

        it('builds SELECT with single field', function() {
            $query = Queries::select('COUNT(*)')
                ->from('users');

            expect($query->build())
                ->toContain('SELECT COUNT(*)');
        });

        it('builds SELECT with table alias', function() {
            $query = Queries::select('u.id', 'u.name')
                ->from('users', 'u');

            $sql = $query->build();

            expect($sql)
                ->toContain('SELECT u.id, u.name')
                ->toContain('FROM users AS u');
        });

        it('adds fields with select method', function() {
            $query = Queries::select('id')
                ->select('name', 'email')
                ->from('users');

            expect($query->build())
                ->toContain('SELECT id, name, email');
        });
    });

    describe('WHERE Conditions', function() {
        it('builds WHERE clause', function() {
            $query = Queries::select('*')
                ->from('users')
                ->where('id = 1');

            expect($query->build())
                ->toContain('WHERE id = 1');
        });

        it('builds WHERE with AND', function() {
            $query = Queries::select('*')
                ->from('users')
                ->where('active = 1')
                ->andWhere('verified = 1');

            $sql = $query->build();

            expect($sql)
                ->toContain('WHERE active = 1')
                ->toContain('AND verified = 1');
        });

        it('builds WHERE with OR', function() {
            $query = Queries::select('*')
                ->from('users')
                ->where('role = "admin"')
                ->orWhere('role = "moderator"');

            $sql = $query->build();

            expect($sql)
                ->toContain('WHERE role = "admin"')
                ->toContain('OR role = "moderator"');
        });

        it('builds WHERE with mixed AND/OR', function() {
            $query = Queries::select('*')
                ->from('users')
                ->where('active = 1')
                ->andWhere('verified = 1')
                ->orWhere('trusted = 1');

            $sql = $query->build();

            expect($sql)
                ->toContain('WHERE')
                ->toContain('AND')
                ->toContain('OR');
        });

        it('builds WHERE with multiple conditions', function() {
            $query = (new SelectQueryBuilder(['*']))
                ->from('users')
                ->where('id > 10', 'status = "active"');

            expect($query->build())
                ->toContain('WHERE id > 10 AND status = "active"');
        });
    });

    describe('JOIN Operations', function() {
        it('builds INNER JOIN', function() {
            $query = Queries::select('u.name', 'p.title')
                ->from('users', 'u')
                ->innerJoin('posts p ON u.id = p.user_id');

            expect($query->build())
                ->toContain('INNER JOIN posts p ON u.id = p.user_id');
        });

        it('builds LEFT JOIN', function() {
            $query = Queries::select('*')
                ->from('users')
                ->leftJoin('profiles ON users.id = profiles.user_id');

            expect($query->build())
                ->toContain('LEFT JOIN profiles ON users.id = profiles.user_id');
        });

        it('builds RIGHT JOIN', function() {
            $query = Queries::select('*')
                ->from('orders')
                ->rightJoin('payments ON orders.id = payments.order_id');

            expect($query->build())
                ->toContain('RIGHT JOIN payments ON orders.id = payments.order_id');
        });

        it('builds multiple JOINs', function() {
            $query = Queries::select('*')
                ->from('users')
                ->innerJoin('posts ON users.id = posts.user_id')
                ->leftJoin('comments ON posts.id = comments.post_id');

            $sql = $query->build();

            expect($sql)
                ->toContain('INNER JOIN posts')
                ->toContain('LEFT JOIN comments');
        });
    });

    describe('ORDER BY', function() {
        it('builds ORDER BY ASC', function() {
            $query = Queries::select('*')
                ->from('users')
                ->orderBy('name', 'ASC');

            expect($query->build())
                ->toContain('ORDER BY name ASC');
        });

        it('builds ORDER BY DESC', function() {
            $query = Queries::select('*')
                ->from('users')
                ->orderBy('created_at', 'DESC');

            expect($query->build())
                ->toContain('ORDER BY created_at DESC');
        });

        it('builds multiple ORDER BY', function() {
            $query = Queries::select('*')
                ->from('users')
                ->orderBy('role', 'ASC')
                ->orderBy('name', 'ASC');

            expect($query->build())
                ->toContain('ORDER BY role ASC, name ASC');
        });

        it('defaults to ASC when direction not specified', function() {
            $query = Queries::select('*')
                ->from('users')
                ->orderBy('name');

            expect($query->build())
                ->toContain('ORDER BY name ASC');
        });
    });

    describe('GROUP BY and HAVING', function() {
        it('builds GROUP BY', function() {
            $query = Queries::select('role', 'COUNT(*) as total')
                ->from('users')
                ->groupBy('role');

            expect($query->build())
                ->toContain('GROUP BY role');
        });

        it('builds GROUP BY with multiple columns', function() {
            $query = Queries::select('role', 'status', 'COUNT(*)')
                ->from('users')
                ->groupBy('role', 'status');

            expect($query->build())
                ->toContain('GROUP BY role, status');
        });

        it('builds HAVING clause', function() {
            $query = Queries::select('role', 'COUNT(*) as total')
                ->from('users')
                ->groupBy('role')
                ->having('COUNT(*) > 10');

            $sql = $query->build();

            expect($sql)
                ->toContain('GROUP BY role')
                ->toContain('HAVING COUNT(*) > 10');
        });

        it('builds HAVING with multiple conditions', function() {
            $query = (new SelectQueryBuilder(['role', 'COUNT(*)']))
                ->from('users')
                ->groupBy('role')
                ->having('COUNT(*) > 5', 'COUNT(*) < 100');

            expect($query->build())
                ->toContain('HAVING COUNT(*) > 5 AND COUNT(*) < 100');
        });
    });

    describe('LIMIT and OFFSET', function() {
        it('builds LIMIT clause', function() {
            $query = Queries::select('*')
                ->from('users')
                ->limit(10);

            expect($query->build())
                ->toContain('LIMIT 10');
        });

        it('builds OFFSET clause', function() {
            $query = Queries::select('*')
                ->from('users')
                ->offset(20);

            expect($query->build())
                ->toContain('OFFSET 20');
        });

        it('builds LIMIT with OFFSET', function() {
            $query = Queries::select('*')
                ->from('users')
                ->limit(10)
                ->offset(20);

            $sql = $query->build();

            expect($sql)
                ->toContain('LIMIT 10')
                ->toContain('OFFSET 20');
        });
    });

    describe('DISTINCT', function() {
        it('builds SELECT DISTINCT', function() {
            $query = Queries::select('role')
                ->from('users')
                ->distinct();

            expect($query->build())
                ->toContain('SELECT DISTINCT role');
        });

        it('builds DISTINCT with multiple columns', function() {
            $query = Queries::select('role', 'status')
                ->from('users')
                ->distinct();

            expect($query->build())
                ->toContain('SELECT DISTINCT role, status');
        });
    });

    describe('Subqueries', function() {
        it('builds WHERE EXISTS subquery', function() {
            $subquery = Queries::select('1')
                ->from('posts')
                ->where('posts.user_id = users.id');

            $query = Queries::select('*')
                ->from('users')
                ->whereExists($subquery);

            expect($query->build())
                ->toContain('WHERE EXISTS');
        });

        it('builds WHERE NOT EXISTS subquery', function() {
            $subquery = Queries::select('1')
                ->from('posts')
                ->where('posts.user_id = users.id');

            $query = Queries::select('*')
                ->from('users')
                ->whereNotExists($subquery);

            expect($query->build())
                ->toContain('WHERE NOT EXISTS');
        });

        it('builds WHERE IN subquery', function() {
            $subquery = Queries::select('user_id')
                ->from('posts')
                ->where('published = 1');

            $query = Queries::select('*')
                ->from('users')
                ->whereInSubquery('id', $subquery);

            expect($query->build())
                ->toContain('WHERE id IN');
        });

        it('builds WHERE NOT IN subquery', function() {
            $subquery = Queries::select('user_id')
                ->from('banned_users');

            $query = Queries::select('*')
                ->from('users')
                ->whereNotInSubquery('id', $subquery);

            expect($query->build())
                ->toContain('WHERE id NOT IN');
        });

        it('builds FROM subquery', function() {
            $subquery = Queries::select('user_id', 'COUNT(*) as post_count')
                ->from('posts')
                ->groupBy('user_id');

            $query = Queries::select('*')
                ->fromSubquery($subquery, 'post_stats');

            $sql = $query->build();

            expect($sql)
                ->toContain('FROM (')
                ->toContain(') AS post_stats');
        });

        it('builds SELECT scalar subquery', function() {
            $subquery = Queries::select('COUNT(*)')
                ->from('posts')
                ->where('posts.user_id = users.id');

            $query = Queries::select('users.*')
                ->selectSubquery($subquery, 'post_count')
                ->from('users');

            $sql = $query->build();

            expect($sql)
                ->toContain('SELECT users.*, (')
                ->toContain(') AS post_count');
        });

        it('builds JOIN with subquery', function() {
            $subquery = Queries::select('user_id', 'COUNT(*) as total')
                ->from('orders')
                ->groupBy('user_id');

            $query = Queries::select('users.name', 'stats.total')
                ->from('users')
                ->joinSubquery($subquery, 'stats', 'users.id = stats.user_id');

            $sql = $query->build();

            expect($sql)
                ->toContain('JOIN (')
                ->toContain(') AS stats ON users.id = stats.user_id');
        });
    });

    describe('Complex Scenarios', function() {
        it('builds complete query with all clauses', function() {
            $query = Queries::select('u.id', 'u.name', 'COUNT(p.id) as post_count')
                ->from('users', 'u')
                ->leftJoin('posts p ON u.id = p.user_id')
                ->where('u.active = 1')
                ->andWhere('u.verified = 1')
                ->groupBy('u.id', 'u.name')
                ->having('COUNT(p.id) > 0')
                ->orderBy('post_count', 'DESC')
                ->limit(10)
                ->offset(0);

            $sql = $query->build();

            expect($sql)
                ->toContain('SELECT u.id, u.name, COUNT(p.id) as post_count')
                ->toContain('FROM users AS u')
                ->toContain('LEFT JOIN posts p ON u.id = p.user_id')
                ->toContain('WHERE u.active = 1')
                ->toContain('AND u.verified = 1')
                ->toContain('GROUP BY u.id, u.name')
                ->toContain('HAVING COUNT(p.id) > 0')
                ->toContain('ORDER BY post_count DESC')
                ->toContain('LIMIT 10')
                ->toContain('OFFSET 0');
        });

        it('handles nested subqueries', function() {
            $innerSubquery = Queries::select('category_id')
                ->from('products')
                ->where('price > 100');

            $outerSubquery = Queries::select('id')
                ->from('categories')
                ->whereInSubquery('id', $innerSubquery);

            $query = Queries::select('*')
                ->from('categories')
                ->whereInSubquery('id', $outerSubquery);

            $sql = $query->build();

            expect($sql)->toContain('WHERE id IN');
        });
    });

    describe('Method Chaining', function() {
        it('allows fluent interface', function() {
            $query = Queries::select('*')
                ->from('users')
                ->where('active = 1')
                ->orderBy('name')
                ->limit(10);

            expect($query)->toBeInstanceOf(SelectQueryBuilder::class);
        });

        it('can be cloned and modified', function() {
            $base = Queries::select('*')->from('users');

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
    });

    describe('Edge Cases', function() {
        it('throws when no FROM clause', function() {
            $query = Queries::select('*');

            expect(fn() => $query->build())
                ->toThrow(Exception::class);
        });

        it('handles special characters in field names', function() {
            $query = Queries::select('`user-name`', '`user.email`')
                ->from('users');

            expect($query->build())
                ->toContain('`user-name`')
                ->toContain('`user.email`');
        });

        it('handles aggregate functions', function() {
            $query = Queries::select(
                'COUNT(*) as total',
                'MAX(age) as max_age',
                'MIN(age) as min_age',
                'AVG(score) as avg_score'
            )->from('users');

            $sql = $query->build();

            expect($sql)
                ->toContain('COUNT(*)')
                ->toContain('MAX(age)')
                ->toContain('MIN(age)')
                ->toContain('AVG(score)');
        });
    });
});
