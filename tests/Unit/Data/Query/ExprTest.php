<?php declare(strict_types=1);

use Lalaz\Data\Query\Expr;

describe('Expr', function() {
    describe('Equality Operators', function() {
        it('builds eq (equals) condition', function() {
            $expr = new Expr();
            $expr->eq('status', 'active');

            expect($expr->expression())->toBe('status = :status');
            expect($expr->parameters())->toBe(['status' => 'active']);
        });

        it('builds neq (not equals) condition', function() {
            $expr = new Expr();
            $expr->neq('status', 'deleted');

            expect($expr->expression())->toBe('status <> :status');
            expect($expr->parameters())->toBe(['status' => 'deleted']);
        });
    });

    describe('Comparison Operators', function() {
        it('builds gt (greater than) condition', function() {
            $expr = new Expr();
            $expr->gt('age', 18);

            expect($expr->expression())->toBe('age > :age');
            expect($expr->parameters())->toBe(['age' => 18]);
        });

        it('builds gte (greater than or equal) condition', function() {
            $expr = new Expr();
            $expr->gte('score', 100);

            expect($expr->expression())->toBe('score >= :score');
            expect($expr->parameters())->toBe(['score' => 100]);
        });

        it('builds lt (less than) condition', function() {
            $expr = new Expr();
            $expr->lt('price', 50.99);

            expect($expr->expression())->toBe('price < :price');
            expect($expr->parameters())->toBe(['price' => 50.99]);
        });

        it('builds lte (less than or equal) condition', function() {
            $expr = new Expr();
            $expr->lte('quantity', 10);

            expect($expr->expression())->toBe('quantity <= :quantity');
            expect($expr->parameters())->toBe(['quantity' => 10]);
        });
    });

    describe('NULL Operators', function() {
        it('builds IS NULL condition', function() {
            $expr = new Expr();
            $expr->null('deleted_at');

            expect($expr->expression())->toBe('deleted_at IS NULL');
            expect($expr->parameters())->toBe([]);
        });

        it('builds IS NOT NULL condition', function() {
            $expr = new Expr();
            $expr->notNull('email_verified_at');

            expect($expr->expression())->toBe('email_verified_at IS NOT NULL');
            expect($expr->parameters())->toBe([]);
        });
    });

    describe('IN Operators', function() {
        it('builds IN condition with single value', function() {
            $expr = new Expr();
            $expr->in('status', ['active']);

            expect($expr->expression())->toBe('status IN (:status_0)');
            expect($expr->parameters())->toBe(['status_0' => 'active']);
        });

        it('builds IN condition with multiple values', function() {
            $expr = new Expr();
            $expr->in('id', [1, 2, 3, 4, 5]);

            expect($expr->expression())->toBe('id IN (:id_0, :id_1, :id_2, :id_3, :id_4)');
            expect($expr->parameters())->toBe([
                'id_0' => 1,
                'id_1' => 2,
                'id_2' => 3,
                'id_3' => 4,
                'id_4' => 5,
            ]);
        });

        it('builds NOT IN condition with multiple values', function() {
            $expr = new Expr();
            $expr->notIn('role', ['admin', 'moderator']);

            expect($expr->expression())->toBe('role NOT IN (:role_0, :role_1)');
            expect($expr->parameters())->toBe([
                'role_0' => 'admin',
                'role_1' => 'moderator',
            ]);
        });

        it('builds NOT IN condition with numeric values', function() {
            $expr = new Expr();
            $expr->notIn('category_id', [10, 20, 30]);

            expect($expr->expression())->toBe('category_id NOT IN (:category_id_0, :category_id_1, :category_id_2)');
            expect($expr->parameters())->toBe([
                'category_id_0' => 10,
                'category_id_1' => 20,
                'category_id_2' => 30,
            ]);
        });
    });

    describe('Logical Operators', function() {
        it('builds expression with AND operator', function() {
            $expr = new Expr();
            $expr->eq('status', 'active')
                ->and()
                ->gt('age', 18);

            expect($expr->expression())->toBe('status = :status AND age > :age');
            expect($expr->parameters())->toBe([
                'status' => 'active',
                'age' => 18,
            ]);
        });

        it('builds expression with OR operator', function() {
            $expr = new Expr();
            $expr->eq('role', 'admin')
                ->or()
                ->eq('role', 'moderator');

            expect($expr->expression())->toBe('role = :role OR role = :role');
            expect($expr->parameters())->toBe(['role' => 'moderator']); // Last value overwrites
        });

        it('builds expression with multiple AND operators', function() {
            $expr = new Expr();
            $expr->gte('age', 18)
                ->and()
                ->lte('age', 65)
                ->and()
                ->eq('status', 'active');

            expect($expr->expression())->toBe('age >= :age AND age <= :age AND status = :status');
            expect($expr->parameters())->toHaveKey('age');
            expect($expr->parameters())->toHaveKey('status');
        });
    });

    describe('Complex Expressions', function() {
        it('builds complex expression with mixed operators', function() {
            $expr = new Expr();
            $expr->eq('status', 'active')
                ->and()
                ->gt('score', 100)
                ->and()
                ->in('role', ['admin', 'user']);

            $expression = $expr->expression();
            expect($expression)->toContain('status = :status');
            expect($expression)->toContain('AND');
            expect($expression)->toContain('score > :score');
            expect($expression)->toContain('IN (:role_0, :role_1)');

            $params = $expr->parameters();
            expect($params)->toHaveKey('status');
            expect($params)->toHaveKey('score');
            expect($params)->toHaveKey('role_0');
            expect($params)->toHaveKey('role_1');
        });

        it('builds expression with NULL checks and comparisons', function() {
            $expr = new Expr();
            $expr->notNull('email')
                ->and()
                ->null('deleted_at')
                ->and()
                ->gte('created_at', '2024-01-01');

            $expression = $expr->expression();
            expect($expression)->toContain('email IS NOT NULL');
            expect($expression)->toContain('deleted_at IS NULL');
            expect($expression)->toContain('created_at >= :created_at');
        });

        it('builds expression with all comparison operators', function() {
            $expr = new Expr();
            $expr->gt('price', 10)
                ->and()
                ->lt('price', 100)
                ->and()
                ->neq('status', 'deleted');

            $expression = $expr->expression();
            expect($expression)->toContain('price > :price');
            expect($expression)->toContain('price < :price');
            expect($expression)->toContain('status <> :status');
        });
    });

    describe('Method Chaining', function() {
        it('allows fluent interface', function() {
            $expr = (new Expr())
                ->eq('status', 'active')
                ->and()
                ->gt('age', 18)
                ->and()
                ->notNull('email');

            expect($expr)->toBeInstanceOf(Expr::class);
            expect($expr->expression())->toBeString();
        });

        it('returns self from all condition methods', function() {
            $expr = new Expr();

            expect($expr->eq('a', 1))->toBe($expr);
            expect($expr->neq('b', 2))->toBe($expr);
            expect($expr->gt('c', 3))->toBe($expr);
            expect($expr->gte('d', 4))->toBe($expr);
            expect($expr->lt('e', 5))->toBe($expr);
            expect($expr->lte('f', 6))->toBe($expr);
            expect($expr->null('g'))->toBe($expr);
            expect($expr->notNull('h'))->toBe($expr);
            expect($expr->in('i', [1, 2]))->toBe($expr);
            expect($expr->notIn('j', [3, 4]))->toBe($expr);
            expect($expr->and())->toBe($expr);
            expect($expr->or())->toBe($expr);
        });
    });

    describe('Edge Cases', function() {
        it('handles empty expression', function() {
            $expr = new Expr();

            expect($expr->expression())->toBe('');
            expect($expr->parameters())->toBe([]);
        });

        it('handles special characters in values', function() {
            $expr = new Expr();
            $expr->eq('name', "O'Brien");

            expect($expr->expression())->toBe('name = :name');
            expect($expr->parameters())->toBe(['name' => "O'Brien"]);
        });

        it('handles numeric zero as value', function() {
            $expr = new Expr();
            $expr->eq('count', 0);

            expect($expr->expression())->toBe('count = :count');
            expect($expr->parameters())->toBe(['count' => 0]);
        });

        it('handles boolean values', function() {
            $expr = new Expr();
            $expr->eq('is_active', true)
                ->and()
                ->eq('is_deleted', false);

            $params = $expr->parameters();
            expect($params['is_active'])->toBeTrue();
            expect($params['is_deleted'])->toBeFalse();
        });

        it('handles null values in eq', function() {
            $expr = new Expr();
            $expr->eq('description', null);

            expect($expr->expression())->toBe('description = :description');
            expect($expr->parameters())->toBe(['description' => null]);
        });

        it('handles empty array in IN clause', function() {
            $expr = new Expr();
            $expr->in('id', []);

            expect($expr->expression())->toBe('id IN ()');
            expect($expr->parameters())->toBe([]);
        });
    });

    describe('Real-world Scenarios', function() {
        it('builds user search query', function() {
            $expr = new Expr();
            $expr->eq('status', 'active')
                ->and()
                ->gte('age', 18)
                ->and()
                ->in('role', ['user', 'premium', 'vip'])
                ->and()
                ->notNull('email_verified_at');

            $expression = $expr->expression();
            expect($expression)->toContain('status = :status');
            expect($expression)->toContain('age >= :age');
            expect($expression)->toContain('role IN (:role_0, :role_1, :role_2)');
            expect($expression)->toContain('email_verified_at IS NOT NULL');

            $params = $expr->parameters();
            expect($params)->toHaveCount(5); // status, age, role_0, role_1, role_2
        });

        it('builds product filtering query', function() {
            $expr = new Expr();
            $expr->gte('price', 10.00)
                ->and()
                ->lte('price', 100.00)
                ->and()
                ->neq('status', 'discontinued')
                ->and()
                ->gt('stock', 0);

            $expression = $expr->expression();
            expect($expression)->toContain('price >= :price');
            expect($expression)->toContain('price <= :price');
            expect($expression)->toContain('status <> :status');
            expect($expression)->toContain('stock > :stock');
        });

        it('builds soft delete query', function() {
            $expr = new Expr();
            $expr->null('deleted_at')
                ->and()
                ->notIn('status', ['archived', 'banned']);

            $expression = $expr->expression();
            expect($expression)->toContain('deleted_at IS NULL');
            expect($expression)->toContain('status NOT IN (:status_0, :status_1)');
        });

        it('builds date range query', function() {
            $expr = new Expr();
            $expr->gte('created_at', '2024-01-01')
                ->and()
                ->lt('created_at', '2024-12-31')
                ->and()
                ->eq('type', 'order');

            $params = $expr->parameters();
            expect($params)->toHaveKey('created_at');
            expect($params)->toHaveKey('type');
        });
    });

    describe('Parameter Binding', function() {
        it('properly binds parameters for all conditions', function() {
            $expr = new Expr();
            $expr->eq('name', 'John')
                ->and()
                ->gt('age', 25)
                ->and()
                ->in('city', ['NYC', 'LA', 'SF']);

            $params = $expr->parameters();
            expect($params)->toHaveKey('name');
            expect($params)->toHaveKey('age');
            expect($params)->toHaveKey('city_0');
            expect($params)->toHaveKey('city_1');
            expect($params)->toHaveKey('city_2');

            expect($params['name'])->toBe('John');
            expect($params['age'])->toBe(25);
            expect($params['city_0'])->toBe('NYC');
            expect($params['city_1'])->toBe('LA');
            expect($params['city_2'])->toBe('SF');
        });

        it('handles parameter overwriting with same key', function() {
            $expr = new Expr();
            $expr->eq('status', 'pending')
                ->or()
                ->eq('status', 'active'); // Overwrites previous status parameter

            $params = $expr->parameters();
            expect($params['status'])->toBe('active'); // Last value wins
        });
    });
});
