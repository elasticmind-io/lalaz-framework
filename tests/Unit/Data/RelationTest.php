<?php

use Lalaz\Data\Relation;
use Lalaz\Data\Query\SelectQueryBuilder;

beforeEach(function () {
    RelationTestRelatedModel::resetFakes();
});

it('retrieves hasMany relation applying soft delete constraint', function () {
    RelationTestRelatedModel::enableSoftDeletes();
    RelationTestRelatedModel::fakeQueryAll([
        ['id' => 10, 'user_id' => 42, 'name' => 'First'],
        ['id' => 11, 'user_id' => 42, 'name' => 'Second'],
    ]);

    $relation = new Relation(RelationTestRelatedModel::class, 'user_id', 42, 'hasMany', 'id');

    $models = $relation->get();

    expect($models)->toHaveCount(2);
    expect($models[0])->toBeInstanceOf(RelationTestRelatedModel::class);
    expect($models[0]->name)->toBe('First');

    $log = RelationTestRelatedModel::recordedQueries();
    expect($log)->toHaveCount(1);
    expect($log[0]['type'])->toBe('all');
    expect($log[0]['sql'])
        ->toBe('SELECT relation_test_related.* FROM relation_test_related WHERE user_id = :user_id AND deleted_at IS NULL');
    expect($log[0]['params'])->toBe(['user_id' => 42]);
});

it('retrieves a single model for hasOne relation', function () {
    RelationTestRelatedModel::fakeQueryOne(['id' => 5, 'user_id' => 99, 'name' => 'Singleton']);

    $relation = new Relation(RelationTestRelatedModel::class, 'user_id', 99, 'hasOne', 'id');

    $model = $relation->get();

    expect($model)->toBeInstanceOf(RelationTestRelatedModel::class);
    expect($model->name)->toBe('Singleton');

    $log = RelationTestRelatedModel::recordedQueries();
    expect($log)->toHaveCount(1);
    expect($log[0]['type'])->toBe('one');
    expect($log[0]['sql'])
        ->toBe('SELECT relation_test_related.* FROM relation_test_related WHERE user_id = :user_id');
    expect($log[0]['params'])->toBe(['user_id' => 99]);
});

it('fetches parent model for belongsTo relation', function () {
    RelationTestRelatedModel::fakeQueryOne(['id' => 7, 'name' => 'Parent']);

    $relation = new Relation(RelationTestRelatedModel::class, 'author_id', 7, 'belongsTo', null, 'id');

    $model = $relation->get();

    expect($model)->toBeInstanceOf(RelationTestRelatedModel::class);
    expect($model->id)->toBe(7);

    $log = RelationTestRelatedModel::recordedQueries();
    expect($log)->toHaveCount(1);
    expect($log[0]['type'])->toBe('one');
    expect($log[0]['sql'])
        ->toBe('SELECT relation_test_related.* FROM relation_test_related WHERE id = :id');
    expect($log[0]['params'])->toBe(['id' => 7]);
});

it('builds belongsToMany query with custom clauses', function () {
    RelationTestRelatedModel::enableSoftDeletes();
    RelationTestRelatedModel::fakeQueryAll([
        ['id' => 21, 'name' => 'Link A'],
        ['id' => 22, 'name' => 'Link B'],
    ]);

    $relation = new Relation(
        RelationTestRelatedModel::class,
        'user_id',
        9,
        'belongsToMany',
        'id',
        'id',
        'relation_user',
        'related_id'
    );

    $relation
        ->where(function (SelectQueryBuilder $query) {
            return $query->andWhere('relation_user.archived = 0');
        })
        ->join('profiles', 'profiles.related_id = relation_test_related.id', 'LEFT')
        ->join('audits', 'audits.related_id = relation_test_related.id', 'RIGHT')
        ->join('tags', 'tags.related_id = relation_test_related.id')
        ->orderBy('relation_test_related.name', 'DESC')
        ->limit(5)
    ->groupBy('relation_test_related.status')
    ->groupBy('relation_test_related.type')
        ->having('COUNT(*) > 1');

    $models = $relation->get();

    expect($models)->toHaveCount(2);

    $log = RelationTestRelatedModel::recordedQueries();
    expect($log)->toHaveCount(1);
    expect($log[0]['type'])->toBe('all');
    expect($log[0]['sql'])
        ->toBe('SELECT relation_test_related.* FROM relation_test_related '
            . 'INNER JOIN relation_user ON relation_user.related_id = relation_test_related.id '
            . 'LEFT JOIN profiles ON profiles.related_id = relation_test_related.id '
            . 'RIGHT JOIN audits ON audits.related_id = relation_test_related.id '
            . 'INNER JOIN tags ON tags.related_id = relation_test_related.id '
            . 'WHERE relation_user.user_id = :foreignKeyValue AND relation_user.archived = 0 AND deleted_at IS NULL '
            . 'GROUP BY relation_test_related.status, relation_test_related.type '
            . 'HAVING COUNT(*) > 1 ORDER BY relation_test_related.name DESC LIMIT 5');
    expect($log[0]['params'])->toBe(['foreignKeyValue' => 9]);
});

if (!class_exists('RelationTestRelatedModel')) {
    class RelationTestRelatedModel
    {
        public ?int $id = null;
        public ?int $user_id = null;
        public ?string $name = null;
        public ?string $status = null;
        public ?string $type = null;

        private static array $queuedResponses = [];
        private static array $queryLog = [];
        private static bool $softDeletes = false;

        public function __construct(array $attributes = [])
        {
            foreach ($attributes as $key => $value) {
                $this->$key = $value;
            }
        }

        public static function tableName(): string
        {
            return 'relation_test_related';
        }

        public static function primaryKey(): string
        {
            return 'id';
        }

        public static function resetFakes(): void
        {
            self::$queuedResponses = [];
            self::$queryLog = [];
            self::$softDeletes = false;
        }

        public static function enableSoftDeletes(): void
        {
            self::$softDeletes = true;
        }

        public static function fakeQueryAll(array $rows): void
        {
            self::$queuedResponses[] = ['mode' => 'all', 'data' => $rows];
        }

        public static function fakeQueryOne(?array $row): void
        {
            self::$queuedResponses[] = ['mode' => 'one', 'data' => $row];
        }

        public static function queryAll(SelectQueryBuilder $query, array $parameters = []): array
        {
            $sql = $query->build();
            self::$queryLog[] = ['type' => 'all', 'sql' => $sql, 'params' => $parameters];
            $rows = self::nextResponse('all');

            return array_map(fn (array $row) => new self($row), $rows);
        }

        public static function queryOne(SelectQueryBuilder $query, array $parameters = [])
        {
            $sql = $query->build();
            self::$queryLog[] = ['type' => 'one', 'sql' => $sql, 'params' => $parameters];
            $row = self::nextResponse('one');

            return $row === null ? null : new self($row);
        }

        public static function applySoftDeleteConstraint(SelectQueryBuilder $query): SelectQueryBuilder
        {
            if (self::$softDeletes) {
                $query->andWhere('deleted_at IS NULL');
            }

            return $query;
        }

        public static function recordedQueries(): array
        {
            return self::$queryLog;
        }

        private static function nextResponse(string $mode)
        {
            if (self::$queuedResponses === []) {
                return $mode === 'all' ? [] : null;
            }

            $response = array_shift(self::$queuedResponses);
            if ($response['mode'] !== $mode) {
                throw new RuntimeException("Expected {$mode} response, got {$response['mode']}");
            }

            return $response['data'];
        }
    }
}
