<?php

use Lalaz\Data\Query\InsertQueryBuilder;

describe('InsertQueryBuilder', function () {
    it('builds insert statement with single row bindings', function () {
        $builder = (new InsertQueryBuilder())
            ->into('users')
            ->values([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ]);

        $sql = $builder->build();

        expect($sql)->toBe('INSERT INTO users (name, email) VALUES (:name, :email)');
        expect($builder->getBindings())->toBe([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);
    });

    it('builds batch insert with unique placeholders per row', function () {
        $builder = (new InsertQueryBuilder())
            ->into('logs')
            ->columns(['message', 'level'])
            ->batchValues([
                ['message' => 'boot', 'level' => 'info'],
                ['message' => 'shutdown', 'level' => 'warning'],
            ]);

        $sql = $builder->build();

        expect($sql)->toBe('INSERT INTO logs (message, level) VALUES (:message_0, :level_0), (:message_1, :level_1)');
        expect($builder->getBindings())->toBe([
            'message_0' => 'boot',
            'level_0' => 'info',
            'message_1' => 'shutdown',
            'level_1' => 'warning',
        ]);
    });

    it('applies on duplicate key update bindings', function () {
        $builder = (new InsertQueryBuilder())
            ->into('metrics')
            ->values([
                'name' => 'requests',
                'count' => 5,
            ])
            ->onDuplicateKeyUpdate([
                'count' => 'VALUES(count) + 1',
                'updated_at' => 'NOW()',
            ]);

        $sql = $builder->build();

        expect($sql)->toBe('INSERT INTO metrics (name, count) VALUES (:name, :count) ON DUPLICATE KEY UPDATE count = VALUES(count) + 1, updated_at = :update_updated_at');
        expect($builder->getBindings())->toBe([
            'name' => 'requests',
            'count' => 5,
            'update_updated_at' => 'NOW()',
        ]);
    });
});
