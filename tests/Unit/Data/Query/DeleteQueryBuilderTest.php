<?php

use Lalaz\Data\Query\DeleteQueryBuilder;

describe('DeleteQueryBuilder', function () {
    it('builds delete statement with where clauses', function () {
        $builder = (new DeleteQueryBuilder())
            ->from('sessions')
            ->where('user_id = :user_id', 'last_seen < :cutoff');

        $sql = $builder->build();

        expect($sql)->toBe('DELETE FROM sessions WHERE user_id = :user_id AND last_seen < :cutoff');
    });

    it('throws when attempting to build without target table', function () {
        $builder = new DeleteQueryBuilder();

        expect(fn () => $builder->build())->toThrow(LogicException::class);
    });
});
