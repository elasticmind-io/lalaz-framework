<?php

use Lalaz\Data\Query\Queries;
use Lalaz\Data\Query\UpdateQueryBuilder;

describe('UpdateQueryBuilder', function() {

    describe('Simple UPDATE', function() {
        it('builds basic UPDATE query', function() {
            $query = Queries::update('users')
                ->set(['name' => 'John Updated'])
                ->where('id', '=', 1);

            $sql = $query->build();

            expect($sql)
                ->toContain('UPDATE users')
                ->toContain('SET name = :name')
                ->toContain('WHERE');
        });

        it('updates multiple columns', function() {
            $query = Queries::update('users')
                ->set(['name' => 'John', 'email' => 'john@example.com'])
                ->where('id', '=', 1);

            $sql = $query->build();

            expect($sql)
                ->toContain('SET name = :name')
                ->toContain('email = :email');
        });

        it('throws on empty set', function() {
            $query = Queries::update('users')->where('id', '=', 1);

            expect(fn() => $query->build())->toThrow(Exception::class);
        });
    });

    describe('WHERE Conditions', function() {
        it('builds WHERE with AND', function() {
            $query = Queries::update('users')
                ->set(['active' => false])
                ->where('role', '=', 'user')
                ->andWhere('verified', '=', true);

            expect($query->build())
                ->toContain('WHERE')
                ->toContain('AND');
        });

        it('builds WHERE with OR', function() {
            $query = Queries::update('users')
                ->set(['verified' => true])
                ->where('email_verified', '=', true)
                ->orWhere('phone_verified', '=', true);

            expect($query->build())
                ->toContain('WHERE')
                ->toContain('OR');
        });
    });

    describe('JOIN Operations', function() {
        it('builds UPDATE with INNER JOIN', function() {
            $query = Queries::update('users')
                ->set(['verified' => true])
                ->innerJoin('profiles', 'users.id = profiles.user_id');

            expect($query->build())
                ->toContain('INNER JOIN profiles ON');
        });

        it('builds UPDATE with multiple JOINs', function() {
            $query = Queries::update('users')
                ->set(['tier' => 'gold'])
                ->innerJoin('orders', 'users.id = orders.user_id')
                ->leftJoin('payments', 'orders.id = payments.order_id');

            $sql = $query->build();

            expect($sql)
                ->toContain('INNER JOIN orders')
                ->toContain('LEFT JOIN payments');
        });
    });

    describe('PostgreSQL RETURNING', function() {
        it('builds UPDATE with RETURNING', function() {
            $query = Queries::update('users')
                ->set(['active' => true])
                ->where('id', '=', 1)
                ->returning('id', 'name');

            expect($query->build())
                ->toContain('RETURNING id, name');
        });
    });

    describe('Method Chaining', function() {
        it('allows fluent interface', function() {
            $query = Queries::update('users')
                ->set(['name' => 'John'])
                ->where('id', '=', 1)
                ->returning('id');

            expect($query)->toBeInstanceOf(UpdateQueryBuilder::class);
        });
    });
});
