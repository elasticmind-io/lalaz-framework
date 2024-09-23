<?php declare(strict_types=1);

namespace {{namespace}};

use Lalaz\Data\ActiveRecord;

class {{name}} extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{tableName}}';
    }

    protected function validates(): array
    {
        return [
            'fieldName' => [self::VALIDATE_REQUIRED]
        ];
    }

    /**
     * Example of a potential relationship: User can have many posts.
     * Uncomment if there is a `posts` table and relation.
     */
    // public function getPosts(): Relation
    // {
    //     return $this->hasMany(Post::class, 'user_id');
    // }

    /**
     * Example of another relationship, like belongsTo.
     */
    // public function getRole(): Relation
    // {
    //     return $this->belongsTo(Role::class, 'role_id');
    // }
}
