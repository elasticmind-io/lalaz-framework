<?php

use Lalaz\Data\Query\SelectQueryBuilder;

describe('SelectQueryBuilder', function () {
    it('builds rich select queries with chaining', function () {
        $builder = (new SelectQueryBuilder(['u.id']))
            ->select('u.email')
            ->from('users', 'u')
            ->where('u.active = 1', 'u.deleted_at IS NULL')
            ->groupBy('u.role')
            ->having('COUNT(u.id) > 1')
            ->orderBy('u.email')
            ->distinct()
            ->paginate(20, 10);

        $sql = $builder->build();

        expect($sql)->toBe('SELECT DISTINCT u.id, u.email FROM users AS u WHERE u.active = 1 AND u.deleted_at IS NULL GROUP BY u.role HAVING COUNT(u.id) > 1 ORDER BY u.email ASC LIMIT 10 OFFSET 20');
    });

    it('supports subqueries and combined logical conditions', function () {
        $recentOrders = (new SelectQueryBuilder(['1']))
            ->from('orders', 'o')
            ->where('o.user_id = u.id')
            ->andWhere('o.created_at > NOW() - INTERVAL 7 DAY');

        $vipUsers = (new SelectQueryBuilder(['vip.user_id']))
            ->from('vip_users', 'vip')
            ->where('vip.level >= 3');

        $builder = (new SelectQueryBuilder(['u.id']))
            ->select('order_stats.total_orders')
            ->from('users', 'u')
            ->joinSubquery(
                (new SelectQueryBuilder(['o.user_id', 'COUNT(*) AS total_orders']))
                    ->from('orders', 'o')
                    ->groupBy('o.user_id'),
                'order_stats',
                'order_stats.user_id = u.id',
                'left'
            )
            ->whereExists($recentOrders)
            ->orWhere("u.role = 'admin'")
            ->whereNotInSubquery('u.id', $vipUsers)
            ->selectSubquery(
                (new SelectQueryBuilder(['AVG(rating)']))
                    ->from('reviews', 'r')
                    ->where('r.user_id = u.id'),
                'avg_rating'
            );

        $sql = $builder->build();

        expect($sql)->toBe(
            'SELECT u.id, order_stats.total_orders, (SELECT AVG(rating) FROM reviews AS r WHERE r.user_id = u.id) AS avg_rating '
            . 'FROM users AS u LEFT JOIN (SELECT o.user_id, COUNT(*) AS total_orders FROM orders AS o GROUP BY o.user_id) AS order_stats ON order_stats.user_id = u.id '
            . 'WHERE EXISTS (SELECT 1 FROM orders AS o WHERE o.user_id = u.id AND o.created_at > NOW() - INTERVAL 7 DAY) OR u.role = \'admin\' '
            . 'AND u.id NOT IN (SELECT vip.user_id FROM vip_users AS vip WHERE vip.level >= 3)'
        );
    });

    it('throws when building without defining a source table', function () {
        $builder = new SelectQueryBuilder(['id']);

        expect(fn () => $builder->build())->toThrow(LogicException::class);
    });
});
