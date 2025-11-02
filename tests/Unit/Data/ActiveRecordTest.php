<?php declare(strict_types=1);

use Lalaz\Data\ActiveRecord;
use Lalaz\Data\Model;

// Stub class for testing
class TestUser extends ActiveRecord
{
    public $id;
    public $name;
    public $email;
    public $age;
    public $is_active;
    public $score;
    public $metadata;
    public $created_at;
    public $deleted_at;

    protected array $casts = [
        'age' => 'int',
        'is_active' => 'bool',
        'score' => 'float',
        'metadata' => 'json',
        'created_at' => 'datetime',
    ];

    public static function tableName(): string
    {
        return 'users';
    }

    public static function primaryKey()
    {
        return 'id';
    }

    // Hook counters for testing
    public static $beforeSaveCalled = 0;
    public static $afterSaveCalled = 0;
    public static $beforeCreateCalled = 0;
    public static $afterCreateCalled = 0;
    public static $beforeUpdateCalled = 0;
    public static $afterUpdateCalled = 0;
    public static $beforeDeleteCalled = 0;
    public static $afterDeleteCalled = 0;

    protected function beforeSave(): void
    {
        self::$beforeSaveCalled++;
    }

    protected function afterSave(): void
    {
        self::$afterSaveCalled++;
    }

    protected function beforeCreate(): void
    {
        self::$beforeCreateCalled++;
    }

    protected function afterCreate(): void
    {
        self::$afterCreateCalled++;
    }

    protected function beforeUpdate(): void
    {
        self::$beforeUpdateCalled++;
    }

    protected function afterUpdate(): void
    {
        self::$afterUpdateCalled++;
    }

    protected function beforeDelete(): void
    {
        self::$beforeDeleteCalled++;
    }

    protected function afterDelete(): void
    {
        self::$afterDeleteCalled++;
    }

    public static function resetHookCounters(): void
    {
        self::$beforeSaveCalled = 0;
        self::$afterSaveCalled = 0;
        self::$beforeCreateCalled = 0;
        self::$afterCreateCalled = 0;
        self::$beforeUpdateCalled = 0;
        self::$afterUpdateCalled = 0;
        self::$beforeDeleteCalled = 0;
        self::$afterDeleteCalled = 0;
    }

    // Accessor for testing
    public function getFullNameAttribute()
    {
        return strtoupper($this->name);
    }

    // Mutator for testing
    public function setEmailAttribute($value)
    {
        $this->email = strtolower($value);
    }
}

describe('ActiveRecord', function() {

    beforeEach(function() {
        TestUser::resetHookCounters();
    });

    describe('Basic Functionality', function() {
        it('extends Model class', function() {
            $user = new TestUser();
            expect($user)->toBeInstanceOf(Model::class);
        });

        it('returns correct table name', function() {
            expect(TestUser::tableName())->toBe('users');
        });

        it('returns correct primary key', function() {
            expect(TestUser::primaryKey())->toBe('id');
        });

        it('constructs with attributes', function() {
            $user = new TestUser([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            expect($user->name)->toBe('John Doe');
            expect($user->email)->toBe('john@example.com');
        });

        it('stores original attributes on construction', function() {
            $user = new TestUser(['name' => 'John']);

            // Access protected property via reflection
            $reflection = new ReflectionClass($user);
            $property = $reflection->getProperty('original');
            $property->setAccessible(true);
            $original = $property->getValue($user);

            expect($original)->toHaveKey('name');
        });
    });

    describe('Magic Methods', function() {
        it('uses magic __get for properties', function() {
            $user = new TestUser();
            $user->name = 'John';

            expect($user->__get('name'))->toBe('John');
        });

        it('uses magic __set for properties', function() {
            $user = new TestUser();
            $user->__set('name', 'Jane');

            expect($user->name)->toBe('Jane');
        });

        it('calls accessor methods with __get', function() {
            $user = new TestUser(['name' => 'john']);

            expect($user->__get('fullName'))->toBe('JOHN');
        });

        it('calls mutator methods with __set', function() {
            $user = new TestUser();
            $user->__set('email', 'JOHN@EXAMPLE.COM');

            expect($user->email)->toBe('john@example.com');
        });

        it('returns null for non-existent properties', function() {
            $user = new TestUser();

            expect($user->__get('nonExistent'))->toBeNull();
        });
    });

    describe('Attribute Casting', function() {
        it('casts integer attributes', function() {
            $user = new TestUser();
            $user->age = '25';

            expect($user->__get('age'))->toBe(25);
            expect($user->__get('age'))->toBeInt();
        });

        it('casts boolean attributes', function() {
            $user = new TestUser();
            $user->is_active = 1;

            expect($user->__get('is_active'))->toBeTrue();
            expect($user->__get('is_active'))->toBeBool();
        });

        it('casts float attributes', function() {
            $user = new TestUser();
            $user->score = '99.5';

            expect($user->__get('score'))->toBe(99.5);
            expect($user->__get('score'))->toBeFloat();
        });

        it('casts JSON attributes', function() {
            $user = new TestUser();
            $user->metadata = '{"key": "value", "count": 5}';

            $result = $user->__get('metadata');
            expect($result)->toBeArray();
            expect($result)->toHaveKey('key');
            expect($result['key'])->toBe('value');
            expect($result['count'])->toBe(5);
        });

        it('casts datetime attributes', function() {
            $user = new TestUser();
            $user->created_at = '2024-01-01 10:00:00';

            $result = $user->__get('created_at');
            expect($result)->toBeInstanceOf(DateTime::class);
        });

        it('returns null when casting null values', function() {
            $user = new TestUser();
            $user->age = null;

            expect($user->__get('age'))->toBeNull();
        });

        it('does not cast attributes without cast definition', function() {
            $user = new TestUser();
            $user->name = 'John';

            expect($user->__get('name'))->toBe('John');
            expect($user->__get('name'))->toBeString();
        });
    });

    describe('Soft Deletes', function() {
        it('detects soft deletes when deleted_at exists', function() {
            $user = new TestUser();
            $user->deleted_at = null;

            // Access protected method via reflection
            $reflection = new ReflectionClass($user);
            $method = $reflection->getMethod('usesSoftDeletes');
            $method->setAccessible(true);

            expect($method->invoke($user))->toBeTrue();
        });
    });

    describe('Lifecycle Hooks', function() {
        it('has beforeSave hook method', function() {
            expect(method_exists(TestUser::class, 'beforeSave'))->toBeTrue();
        });

        it('has afterSave hook method', function() {
            expect(method_exists(TestUser::class, 'afterSave'))->toBeTrue();
        });

        it('has beforeCreate hook method', function() {
            expect(method_exists(TestUser::class, 'beforeCreate'))->toBeTrue();
        });

        it('has afterCreate hook method', function() {
            expect(method_exists(TestUser::class, 'afterCreate'))->toBeTrue();
        });

        it('has beforeUpdate hook method', function() {
            expect(method_exists(TestUser::class, 'beforeUpdate'))->toBeTrue();
        });

        it('has afterUpdate hook method', function() {
            expect(method_exists(TestUser::class, 'afterUpdate'))->toBeTrue();
        });

        it('has beforeDelete hook method', function() {
            expect(method_exists(TestUser::class, 'beforeDelete'))->toBeTrue();
        });

        it('has afterDelete hook method', function() {
            expect(method_exists(TestUser::class, 'afterDelete'))->toBeTrue();
        });
    });

    describe('Transaction Methods', function() {
        it('has beginTransaction static method', function() {
            expect(method_exists(TestUser::class, 'beginTransaction'))->toBeTrue();
        });

        it('has commit static method', function() {
            expect(method_exists(TestUser::class, 'commit'))->toBeTrue();
        });

        it('has rollBack static method', function() {
            expect(method_exists(TestUser::class, 'rollBack'))->toBeTrue();
        });

        it('has transaction static method', function() {
            expect(method_exists(TestUser::class, 'transaction'))->toBeTrue();
        });
    });

    describe('Traits Usage', function() {
        it('uses Presentable trait', function() {
            expect(method_exists(TestUser::class, 'toArray'))->toBeTrue();
        });

        it('uses DatabaseQueryable trait', function() {
            expect(method_exists(TestUser::class, 'prepare'))->toBeTrue();
            expect(method_exists(TestUser::class, 'prepareAndBindParameters'))->toBeTrue();
        });

        it('uses DatabaseReadable trait', function() {
            expect(method_exists(TestUser::class, 'findById'))->toBeTrue();
            expect(method_exists(TestUser::class, 'findAll'))->toBeTrue();
            expect(method_exists(TestUser::class, 'findOneByExpression'))->toBeTrue();
        });

        it('uses DatabaseWritable trait', function() {
            expect(method_exists(TestUser::class, 'save'))->toBeTrue();
            expect(method_exists(TestUser::class, 'delete'))->toBeTrue();
        });

        it('uses HasRelationships trait', function() {
            expect(method_exists(TestUser::class, 'hasOne'))->toBeTrue();
            expect(method_exists(TestUser::class, 'hasMany'))->toBeTrue();
            expect(method_exists(TestUser::class, 'belongsTo'))->toBeTrue();
        });

        it('uses HasFillableAttributes trait', function() {
            expect(method_exists(TestUser::class, 'fill'))->toBeTrue();
        });
    });

    describe('Edge Cases', function() {
        it('handles empty constructor', function() {
            $user = new TestUser();
            expect($user)->toBeInstanceOf(TestUser::class);
        });

        it('handles multiple attribute types in constructor', function() {
            $user = new TestUser([
                'name' => 'John',
                'age' => 25,
                'is_active' => true,
                'score' => 99.5,
            ]);

            expect($user->name)->toBe('John');
            expect($user->__get('age'))->toBe(25);
            expect($user->__get('is_active'))->toBeTrue();
            expect($user->__get('score'))->toBe(99.5);
        });

        it('handles special characters in attributes', function() {
            $user = new TestUser(['name' => "O'Brien"]);
            expect($user->name)->toBe("O'Brien");
        });

        it('handles numeric zero as attribute value', function() {
            $user = new TestUser(['age' => 0]);
            expect($user->__get('age'))->toBe(0);
        });
    });
});
