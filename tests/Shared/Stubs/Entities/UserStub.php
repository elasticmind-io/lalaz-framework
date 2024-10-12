<?php declare(strict_types=1);

namespace Tests\Shared\Stubs\Entities;

use Lalaz\Data\ActiveRecord;
use Lalaz\Data\Concerns\SoftDeletes;
use Lalaz\Data\Query\Expressions;
use Lalaz\Security\Authenticable;

class UserStub extends ActiveRecord
{
    use Authenticable;
    use SoftDeletes;

    public $id = null;
    public $username = null;
    public $password = null;
    public $role = null;
    public $active = null;
    public $metadata = '{}';
    public $created_at = null;
    public $updated_at = null;

    protected array $fillable = [
        'username',
        'password',
        'role',
        'active',
        'metadata',
        'create_at',
        'updated_at'
    ];

    public static function tableName(): string
    {
        return 'users';
    }

    public static function findByUsername(string $username): User|bool
    {
        $filter = Expressions::create()->eq('username', $username);
        return self::findOneByExpression($filter);
    }

    protected function validates(): array
    {
        return [
            'username' => [
                self::VALIDATE_REQUIRED,
                [self::VALIDATE_UNIQUE, 'class' => $this]
            ],
            'password' => [
                [self::VALIDATE_REQUIRED, 'on' => 'create']
            ],
            'role' => [self::VALIDATE_REQUIRED],
            'active' => [self::VALIDATE_BOOL]
        ];
    }

    private static function usernamePropertyName(): string
    {
        return 'username';
    }

    private static function passwordPropertyName(): string
    {
        return 'password';
    }

    public function beforeCreate(): void
    {
        $this->password = self::generateHash($this->password);
    }

    public function getPosts(): Relation
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}
