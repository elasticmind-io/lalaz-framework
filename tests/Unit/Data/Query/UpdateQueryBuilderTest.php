<?php

use Lalaz\Data\Query\UpdateQueryBuilder;

describe('UpdateQueryBuilder', function () {
    it('builds parameterized update statements', function () {
        $builder = (new UpdateQueryBuilder())
            ->table('users')
            ->set([
                'name' => 'Bob',
                'email' => 'bob@example.com',
            ])
            ->where('id = :id');

        $sql = $builder->build();

        expect($sql)->toBe('UPDATE users SET name = :name, email = :email WHERE id = :id');
        expect($builder->getBindings())->toBe([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);
    });

    it('supports raw expressions alongside bound values', function () {
        $builder = (new UpdateQueryBuilder())
            ->table('stats')
            ->set('count', 10)
            ->setRaw('updated_at', 'NOW()');

        $sql = $builder->build();

        expect($sql)->toBe('UPDATE stats SET count = :count, updated_at = NOW()');
        expect($builder->getBindings())->toBe([
            'count' => 10,
        ]);
    });

    it('overrides raw assignments when setting the same column', function () {
        $builder = (new UpdateQueryBuilder())
            ->table('jobs')
            ->setRaw('attempts', 'attempts + 1')
            ->set('attempts', 0);

        $sql = $builder->build();

        expect($sql)->toBe('UPDATE jobs SET attempts = :attempts');
        expect($builder->getBindings())->toBe([
            'attempts' => 0,
        ]);
    });
});
