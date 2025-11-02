<?php

use Lalaz\Data\Query\Queries;
use Lalaz\Data\Query\InsertQueryBuilder;

describe('InsertQueryBuilder', function() {
    
    describe('Simple INSERT', function() {
        it('builds basic INSERT query', function() {
            $query = Queries::insert('users')
                ->values(['name' => 'John', 'email' => 'john@example.com']);
            
            $sql = $query->build();
            
            expect($sql)
                ->toContain('INSERT INTO users')
                ->toContain('(name, email)')
                ->toContain('VALUES')
                ->toContain(':name')
                ->toContain(':email');
        });

        it('generates parameter placeholders', function() {
            $query = Queries::insert('users')
                ->values(['name' => 'John', 'email' => 'john@example.com']);
            
            $sql = $query->build();
            
            expect($sql)
                ->toContain(':name')
                ->toContain(':email');
        });

        it('handles null values', function() {
            $query = Queries::insert('users')
                ->values(['name' => 'John', 'email' => null]);
            
            $sql = $query->build();
            
            expect($sql)->toContain(':email');
        });
    });

    describe('Batch INSERT', function() {
        it('builds batch INSERT query', function() {
            $query = Queries::insert('users')
                ->batchValues([
                    ['name' => 'User1', 'email' => 'user1@example.com'],
                    ['name' => 'User2', 'email' => 'user2@example.com'],
                    ['name' => 'User3', 'email' => 'user3@example.com'],
                ]);
            
            $sql = $query->build();
            
            expect($sql)
                ->toContain('INSERT INTO users')
                ->toContain('(name, email)')
                ->toContain('VALUES');
            
            // Verifica múltiplos conjuntos de valores
            expect(substr_count($sql, ':name'))->toBe(3)
                ->and(substr_count($sql, ':email'))->toBe(3);
        });

        it('handles batch with getValues()', function() {
            $query = Queries::insert('users')
                ->batchValues([
                    ['name' => 'User1'],
                    ['name' => 'User2'],
                ]);
            
            $values = $query->getValues();
            
            expect($values)->toHaveCount(2)
                ->and($values[0])->toHaveKey('name', 'User1')
                ->and($values[1])->toHaveKey('name', 'User2');
        });

        it('handles large batch inserts', function() {
            $data = [];
            for ($i = 0; $i < 100; $i++) {
                $data[] = ['name' => "User{$i}", 'email' => "user{$i}@example.com"];
            }
            
            $query = Queries::insert('users')->batchValues($data);
            $sql = $query->build();
            
            expect(substr_count($sql, ':name'))->toBe(100)
                ->and(substr_count($sql, ':email'))->toBe(100);
        });
    });

    describe('MySQL ON DUPLICATE KEY UPDATE', function() {
        it('builds INSERT with ON DUPLICATE KEY UPDATE', function() {
            $query = Queries::insert('users')
                ->values(['id' => 1, 'name' => 'John', 'email' => 'john@example.com'])
                ->onDuplicateKeyUpdate(['name' => 'John Updated', 'email' => 'john.updated@example.com']);
            
            $sql = $query->build();
            
            expect($sql)
                ->toContain('INSERT INTO users')
                ->toContain('ON DUPLICATE KEY UPDATE')
                ->toContain('name = :update_name')
                ->toContain('email = :update_email');
        });

        it('verifies update data structure', function() {
            $query = Queries::insert('users')
                ->values(['id' => 1, 'name' => 'John'])
                ->onDuplicateKeyUpdate(['name' => 'John Updated']);
            
            $updateData = $query->getOnDuplicateKeyUpdate();
            
            expect($updateData)
                ->toHaveKey('name', 'John Updated');
        });

        it('works with batch inserts', function() {
            $query = Queries::insert('users')
                ->batchValues([
                    ['id' => 1, 'name' => 'User1'],
                    ['id' => 2, 'name' => 'User2'],
                ])
                ->onDuplicateKeyUpdate(['name' => 'Updated']);
            
            $sql = $query->build();
            
            expect($sql)
                ->toContain('VALUES')
                ->toContain('ON DUPLICATE KEY UPDATE')
                ->toContain('name = :update_name');
        });
    });

    describe('PostgreSQL RETURNING', function() {
        it('builds INSERT with RETURNING clause', function() {
            $query = Queries::insert('users')
                ->values(['name' => 'John', 'email' => 'john@example.com'])
                ->returning('id', 'created_at');
            
            $sql = $query->build();
            
            expect($sql)
                ->toContain('INSERT INTO users')
                ->toContain('RETURNING id, created_at');
        });

        it('returns all columns with asterisk', function() {
            $query = Queries::insert('users')
                ->values(['name' => 'John'])
                ->returning('*');
            
            $sql = $query->build();
            
            expect($sql)->toContain('RETURNING *');
        });

        it('works with ON DUPLICATE KEY UPDATE', function() {
            $query = Queries::insert('users')
                ->values(['id' => 1, 'name' => 'John'])
                ->onDuplicateKeyUpdate(['name' => 'Updated'])
                ->returning('id', 'name');
            
            $sql = $query->build();
            
            expect($sql)
                ->toContain('ON DUPLICATE KEY UPDATE')
                ->toContain('RETURNING id, name');
        });
    });

    describe('Table Name Escaping', function() {
        it('handles table names with schema', function() {
            $query = Queries::insert('public.users')
                ->values(['name' => 'John']);
            
            $sql = $query->build();
            
            expect($sql)->toContain('INSERT INTO public.users');
        });

        it('handles quoted table names', function() {
            $query = Queries::insert('`users`')
                ->values(['name' => 'John']);
            
            $sql = $query->build();
            
            expect($sql)->toContain('INSERT INTO `users`');
        });
    });

    describe('Method Chaining', function() {
        it('allows fluent interface', function() {
            $query = Queries::insert('users')
                ->values(['name' => 'John'])
                ->onDuplicateKeyUpdate(['name' => 'Updated'])
                ->returning('id');
            
            expect($query)->toBeInstanceOf(InsertQueryBuilder::class);
        });

        it('can reset and reuse builder', function() {
            $builder = Queries::insert('users');
            
            $query1 = clone $builder;
            $query1->values(['name' => 'User1']);
            
            $query2 = clone $builder;
            $query2->values(['name' => 'User2']);
            
            $values1 = $query1->getValues();
            $values2 = $query2->getValues();
            
            expect($values1[0])->toHaveKey('name', 'User1')
                ->and($values2[0])->toHaveKey('name', 'User2');
        });
    });
});
