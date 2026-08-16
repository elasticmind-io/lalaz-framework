<?php

use Lalaz\Data\Relation;

/*
 * Coverage targets
 * - HasRelationships trait: relationship factory defaults and metadata
 * - Relation class: query setup and execution path shortcuts
 * - DatabaseReadable::eagerLoadRelations: batched hydration behaviour
 */

describe('HasRelationships', function () {
    beforeEach(fn () => resetRelationshipTestStubs());

    it('creates hasMany relations with inferred keys and values', function () {
        $user = new TestUser(['id' => 5]);

        $relation = $user->hasMany(TestPost::class);

        expect($relation)->toBeInstanceOf(Relation::class);
        expect($relation->getRelationType())->toBe('hasMany');
        expect($relation->getForeignKey())->toBe('testuser_id');
        expect($relation->getLocalKey())->toBe('id');
        expect(relationProperty($relation, 'localValue'))->toBe(5);
    });

    it('creates hasOne relations with inferred keys and values', function () {
        $user = new TestUser(['id' => 12]);

        $relation = $user->hasOne(TestProfile::class);

        expect($relation->getRelationType())->toBe('hasOne');
        expect($relation->getForeignKey())->toBe('testuser_id');
        expect($relation->getLocalKey())->toBe('id');
        expect(relationProperty($relation, 'localValue'))->toBe(12);
    });

    it('creates belongsTo relations with default foreign and owner keys', function () {
        $post = new TestPost(['testuser_id' => 7]);

        $relation = $post->belongsTo(TestUser::class);

        expect($relation->getRelationType())->toBe('belongsTo');
        expect($relation->getForeignKey())->toBe('testuser_id');
        expect($relation->getOwnerKey())->toBe('id');
        expect(relationProperty($relation, 'localValue'))->toBe(7);
    });

    it('creates belongsToMany relations with sensible defaults', function () {
        $user = new TestUser(['id' => 3]);

        $relation = $user->belongsToMany(TestRole::class);

        expect($relation->getRelationType())->toBe('belongsToMany');
        expect($relation->getPivotTable())->toBe('testrole_testuser');
        expect($relation->getForeignKey())->toBe('testuser_id');
        expect($relation->getRelatedPivotKey())->toBe('testrole_id');
        expect($relation->getOwnerKey())->toBe('id');
        expect($relation->getLocalKey())->toBe('id');
        expect(relationProperty($relation, 'localValue'))->toBe(3);
    });
});

describe('Relation', function () {
    beforeEach(fn () => resetRelationshipTestStubs());

    it('builds hasMany queries with where constraints', function () {
        $user = new TestUser(['id' => 8]);
        $relation = $user->hasMany(TestPost::class);

        $sql = $relation->getQuery()->build();

        expect($sql)->toBe('SELECT test_posts.* FROM test_posts WHERE testuser_id = :testuser_id');
    });

    it('builds belongsTo queries keyed by owner key', function () {
        $post = new TestPost(['testuser_id' => 4]);
        $relation = $post->belongsTo(TestUser::class);

        $sql = $relation->getQuery()->build();

        expect($sql)->toBe('SELECT test_users.* FROM test_users WHERE id = :id');
    });

    it('builds belongsToMany queries with pivot join', function () {
        $user = new TestUser(['id' => 9]);
        $relation = $user->belongsToMany(TestRole::class);

        $sql = $relation->getQuery()->build();

        expect($sql)->toBe(
            'SELECT test_roles.* FROM test_roles ' .
            'INNER JOIN testrole_testuser ON testrole_testuser.testrole_id = test_roles.id ' .
            'WHERE testrole_testuser.testuser_id = :foreignKeyValue'
        );
    });

    it('delegates retrieval to queryOne for singular relations', function () {
        $user = new TestUser(['id' => 15]);
        $profile = new TestProfile(['id' => 101, 'testuser_id' => 15]);

        TestProfile::setMockResults([$profile]);

        $relation = $user->hasOne(TestProfile::class);
        $result = $relation->get();

        expect($result)->toBeInstanceOf(TestProfile::class);
        $log = TestProfile::queryLog();
        expect($log)->toHaveCount(2);
        expect($log[0]['method'])->toBe('applySoftDeleteConstraint');
        expect($log[1]['method'])->toBe('queryOne');
        expect($log[1]['params'])->toBe(['testuser_id' => 15]);
    });

    it('delegates retrieval to queryAll for collection relations', function () {
        $user = new TestUser(['id' => 20]);
        $posts = [
            new TestPost(['id' => 1, 'testuser_id' => 20]),
            new TestPost(['id' => 2, 'testuser_id' => 20]),
        ];

        TestPost::setMockResults($posts);

        $relation = $user->hasMany(TestPost::class);
        $result = $relation->get();

        expect($result)->toHaveCount(2);
        $log = TestPost::queryLog();
        expect($log)->toHaveCount(2);
        expect($log[0]['method'])->toBe('applySoftDeleteConstraint');
        expect($log[1]['method'])->toBe('queryAll');
        expect($log[1]['params'])->toBe(['testuser_id' => 20]);
    });
});

describe('DatabaseReadable::eagerLoadRelations', function () {
    beforeEach(fn () => resetRelationshipTestStubs());

    it('hydrates hasMany relations in bulk', function () {
        $users = [
            new TestUser(['id' => 1]),
            new TestUser(['id' => 2]),
        ];

        TestPost::setMockResults([
            new TestPost(['id' => 10, 'testuser_id' => 1]),
            new TestPost(['id' => 11, 'testuser_id' => 1]),
            new TestPost(['id' => 12, 'testuser_id' => 2]),
        ]);

        TestUser::eagerLoad($users, ['posts']);

        expect($users[0]->posts)->toHaveCount(2);
        expect($users[0]->posts[0])->toBeInstanceOf(TestPost::class);
        expect($users[1]->posts)->toHaveCount(1);
        $log = TestPost::queryLog();
        expect($log)->toHaveCount(2);
        expect($log[0]['method'])->toBe('applySoftDeleteConstraint');
        expect($log[1]['method'])->toBe('queryAll');
        expect($log[1]['params'])->toBe(['id_0' => 1, 'id_1' => 2]);
    });

    it('hydrates hasOne relations and defaults missing ones to null', function () {
        $users = [
            new TestUser(['id' => 3]),
            new TestUser(['id' => 4]),
        ];

        TestProfile::setMockResults([
            new TestProfile(['id' => 91, 'testuser_id' => 3]),
        ]);

        TestUser::eagerLoad($users, ['profile']);

        expect($users[0]->profile)->toBeInstanceOf(TestProfile::class);
        expect($users[1]->profile)->toBeNull();
    });

    it('hydrates belongsTo relations using owner keys', function () {
        $users = [
            new TestUser(['id' => 5, 'testrole_id' => 201]),
            new TestUser(['id' => 6, 'testrole_id' => 202]),
        ];

        TestRole::setMockResults([
            new TestRole(['id' => 201, 'label' => 'Admin']),
            new TestRole(['id' => 202, 'label' => 'Editor']),
        ]);

        TestUser::eagerLoad($users, ['role']);

        expect($users[0]->role->label)->toBe('Admin');
        expect($users[1]->role->label)->toBe('Editor');
        $log = TestRole::queryLog();
        expect($log)->toHaveCount(2); // applySoftDelete + queryAll
        expect($log[0]['method'])->toBe('applySoftDeleteConstraint');
        expect($log[1]['method'])->toBe('queryAll');
    });

    it('hydrates belongsToMany relations and strips helper data', function () {
        $users = [
            new TestUser(['id' => 7]),
            new TestUser(['id' => 8]),
        ];

        TestRole::setMockResults([
            new TestRole(['id' => 301, 'label' => 'Reader', 'pivot_parent_id' => 7]),
            new TestRole(['id' => 302, 'label' => 'Writer', 'pivot_parent_id' => 8]),
            new TestRole(['id' => 303, 'label' => 'Auditor', 'pivot_parent_id' => 7]),
        ]);

        TestUser::eagerLoad($users, ['roles']);

        expect($users[0]->roles)->toHaveCount(2);
        expect($users[1]->roles)->toHaveCount(1);
        expect(isset($users[0]->roles[0]->pivot_parent_id))->toBeFalse();
    });

    it('ignores unknown relation names safely', function () {
        $users = [new TestUser(['id' => 9])];

        TestUser::eagerLoad($users, ['doesNotExist']);

        expect(property_exists($users[0], 'doesNotExist'))->toBeFalse();
    });
});

function relationProperty(Relation $relation, string $property): mixed
{
    $reflection = new \ReflectionProperty(Relation::class, $property);
    $reflection->setAccessible(true);

    return $reflection->getValue($relation);
}

function resetRelationshipTestStubs(): void
{
    TestUser::resetMocks();
    TestPost::resetMocks();
    TestProfile::resetMocks();
    TestRole::resetMocks();
}

if (!class_exists('RelationshipTestModel')) {
    #[\AllowDynamicProperties]
    abstract class RelationshipTestModel extends \Lalaz\Data\ActiveRecord
    {
        use \Lalaz\Data\Concerns\HasRelationships;
        use \Lalaz\Data\Concerns\DatabaseReadable;

        private static array $mockResults = [];
        private static array $queryLogs = [];
        private static array $softDeleteFlags = [];
        private static array $softDeleteCalls = [];

        public array $dirty = [];
        public array $original = [];
        protected array $hidden = [];
        public bool $exists = false;

        public function __construct(array $attributes = [])
        {
            foreach ($attributes as $key => $value) {
                $this->$key = $value;
            }
        }

        public function __get($key)
        {
            return $this->$key ?? null;
        }

        public function __set($key, $value): void
        {
            $this->$key = $value;
        }

        public function attributesToArray(): array
        {
            return get_object_vars($this);
        }

        public static function setMockResults(array $results): void
        {
            self::$mockResults[static::class] = array_map(
                fn ($item) => is_object($item) ? clone $item : $item,
                $results
            );
        }

        public static function queryLog(): array
        {
            return self::$queryLogs[static::class] ?? [];
        }

        public static function resetMocks(): void
        {
            unset(
                self::$mockResults[static::class],
                self::$queryLogs[static::class],
                self::$softDeleteFlags[static::class],
                self::$softDeleteCalls[static::class]
            );
        }

        public static function eagerLoad(array $models, array $relations): void
        {
            static::eagerLoadRelations($models, $relations);
        }

        public static function enableSoftDeletes(): void
        {
            self::$softDeleteFlags[static::class] = true;
        }

        public static function softDeleteCallCount(): int
        {
            return (int) (self::$softDeleteCalls[static::class] ?? 0);
        }

        protected function usesSoftDeletes(): bool
        {
            return self::$softDeleteFlags[static::class] ?? false;
        }

        public static function queryAll($builder, array $parameters = []): array
        {
            self::$queryLogs[static::class][] = [
                'method' => 'queryAll',
                'sql' => $builder->build(),
                'params' => $parameters,
            ];

            $results = self::$mockResults[static::class] ?? [];

            return array_map(
                fn ($item) => is_object($item) ? clone $item : $item,
                $results
            );
        }

        public static function queryOne($builder, array $parameters = [])
        {
            self::$queryLogs[static::class][] = [
                'method' => 'queryOne',
                'sql' => $builder->build(),
                'params' => $parameters,
            ];

            $results = self::$mockResults[static::class] ?? [];
            $first = $results[0] ?? null;

            if (!$first) {
                return null;
            }

            return is_object($first) ? clone $first : $first;
        }

    public static function applySoftDeleteConstraint(\Lalaz\Data\Query\SelectQueryBuilder $query): \Lalaz\Data\Query\SelectQueryBuilder
        {
            self::$queryLogs[static::class][] = ['method' => 'applySoftDeleteConstraint'];
            self::$softDeleteCalls[static::class] = (self::$softDeleteCalls[static::class] ?? 0) + 1;

            return $query;
        }
    }

    class TestUser extends RelationshipTestModel
    {
        public int $id;
        public ?int $testrole_id = null;

        public static function tableName(): string
        {
            return 'test_users';
        }

        public static function primaryKey(): string
        {
            return 'id';
        }

        public function posts(): Relation
        {
            return $this->hasMany(TestPost::class);
        }

        public function profile(): Relation
        {
            return $this->hasOne(TestProfile::class);
        }

        public function role(): Relation
        {
            return $this->belongsTo(TestRole::class);
        }

        public function roles(): Relation
        {
            return $this->belongsToMany(TestRole::class);
        }
    }

    class TestPost extends RelationshipTestModel
    {
        public int $id;
        public int $testuser_id;

        public static function tableName(): string
        {
            return 'test_posts';
        }

        public static function primaryKey(): string
        {
            return 'id';
        }
    }

    class TestProfile extends RelationshipTestModel
    {
        public int $id;
        public int $testuser_id;

        public static function tableName(): string
        {
            return 'test_profiles';
        }

        public static function primaryKey(): string
        {
            return 'id';
        }
    }

    class TestRole extends RelationshipTestModel
    {
        public int $id;
        public string $label;
        public ?int $pivot_parent_id = null;

        public static function tableName(): string
        {
            return 'test_roles';
        }

        public static function primaryKey(): string
        {
            return 'id';
        }
    }
}
