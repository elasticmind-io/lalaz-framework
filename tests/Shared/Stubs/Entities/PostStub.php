<?php declare(strict_types=1);

namespace Tests\Shared\Stubs\Entities;

use Lalaz\Data\ActiveRecord;

class PostStub extends ActiveRecord
{
    public $id = null;
    public $title = null;
    public $description = null;
    public $created_at = null;
    public $updated_at = null;

    protected array $fillable = [
        'title',
        'description',
        'create_at',
        'updated_at'
    ];

    public static function tableName(): string
    {
        return 'posts';
    }

    protected function validates(): array
    {
        return [];
    }
}
