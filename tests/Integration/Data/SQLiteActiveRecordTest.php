<?php

use Lalaz\Data\Database;
use Lalaz\Data\Adapters\SQLiteAdapter;
use Lalaz\Data\Query\Expressions;

beforeEach(function () {
    $this->sqlitePath = sys_get_temp_dir() . '/lalaz_sqlite_' . uniqid('', true) . '.db';
    $adapter = new SQLiteAdapter(['path' => $this->sqlitePath]);
    $database = new Database($adapter);

    $database->exec('CREATE TABLE integration_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        status TEXT NOT NULL,
        views INTEGER DEFAULT 0,
        deleted_at TEXT DEFAULT NULL,
        created_at TEXT DEFAULT NULL,
        updated_at TEXT DEFAULT NULL
    )');

    SQLiteIntegrationModel::useDatabase($database);
});

afterEach(function () {
    SQLiteIntegrationModel::clearDatabase();

    if (isset($this->sqlitePath) && file_exists($this->sqlitePath)) {
        unlink($this->sqlitePath);
    }
});

it('performs ActiveRecord operations end-to-end against SQLite', function () {
    $db = SQLiteIntegrationModel::database();
    expect($db->isConnected())->toBeTrue();

    expect($db->beginTransaction())->toBeTrue();
    $db->exec('INSERT INTO integration_posts (title, status, views) VALUES (:title, :status, :views)', [
        'title' => 'tx',
        'status' => 'draft',
        'views' => 1,
    ]);
    $db->rollBack();

    $stmt = $db->query('SELECT COUNT(*) FROM integration_posts');
    expect((int) $stmt->fetchColumn())->toBe(0);

    $post = new SQLiteIntegrationModel([
        'title' => 'First Post',
        'status' => 'draft',
        'views' => 5,
    ]);

    expect($post->save())->toBeTrue();
    expect($post->exists)->toBeTrue();
    expect($post->id)->toBeGreaterThan(0);
    expect($post->created_at)->not->toBeNull();
    expect($post->updated_at)->not->toBeNull();

    $loaded = SQLiteIntegrationModel::findById($post->id);
    expect($loaded)->toBeInstanceOf(SQLiteIntegrationModel::class);
    expect($loaded->title)->toBe('First Post');
    expect($loaded->exists)->toBeTrue();

    $previousUpdatedAt = $post->updated_at;
    $post->fill([
        'status' => 'published',
        'views' => 12,
    ]);
    expect($post->status)->toBe('published');
    expect($post->isDirty())->toBeTrue();
    expect($post->save())->toBeTrue();
    expect(strtotime($post->updated_at))->toBeGreaterThanOrEqual(strtotime($previousUpdatedAt));

    expect(SQLiteIntegrationModel::count())->toBe(1);

    $statusStmt = $db->query('SELECT status FROM integration_posts WHERE id = :id', ['id' => $post->id]);
    expect($statusStmt->fetchColumn())->toBe('published');

    $publishedExpr = Expressions::create()->eq('status', 'published');
    expect(SQLiteIntegrationModel::countByExpression($publishedExpr))->toBe(1);

    $collection = SQLiteIntegrationModel::findAllByExpression(Expressions::create()->eq('status', 'published'));
    expect($collection)->toHaveCount(1);
    expect($collection[0]->views)->toBe(12);

    $paged = SQLiteIntegrationModel::findAllPaged(currentPage: 1, take: 1);
    expect($paged->totalRecords)->toBe(1);
    expect($paged->records)->toHaveCount(1);

    $viewsExpr = Expressions::create()->gte('views', 10);
    $filtered = SQLiteIntegrationModel::findAllByExpression($viewsExpr, orderBy: ['views' => 'DESC']);
    expect($filtered)->toHaveCount(1);
    expect($filtered[0]->status)->toBe('published');

    expect($post->delete())->toBeTrue();
    expect($post->deleted_at)->not->toBeNull();
    expect(SQLiteIntegrationModel::count())->toBe(0);

    $restored = SQLiteIntegrationModel::partialUpdate(['deleted_at' => null], ['id' => $post->id]);
    expect($restored)->toBe(1);
    expect(SQLiteIntegrationModel::count())->toBe(1);

    $db->exec('DELETE FROM integration_posts WHERE id = :id', ['id' => $post->id]);
    expect(SQLiteIntegrationModel::count())->toBe(0);
});

if (!class_exists('SQLiteIntegrationModel')) {
    #[\AllowDynamicProperties]
    class SQLiteIntegrationModel extends \Lalaz\Data\ActiveRecord
    {
        use \Lalaz\Data\Concerns\HasTimestamps;

        protected static ?Database $database = null;

        public ?int $id = null;
        public string $title = '';
        public string $status = '';
        public int $views = 0;
        public ?string $deleted_at = null;
        public ?string $created_at = null;
        public ?string $updated_at = null;

        protected array $fillable = ['title', 'status', 'views', 'deleted_at', 'created_at', 'updated_at'];
        protected array $guarded = ['id'];

        public static function tableName(): string
        {
            return 'integration_posts';
        }

        public static function primaryKey(): string
        {
            return 'id';
        }

        public static function useDatabase(Database $database): void
        {
            static::$database = $database;
        }

        public static function clearDatabase(): void
        {
            static::$database = null;
        }

        public static function database(): Database
        {
            if (!static::$database) {
                throw new \RuntimeException('Database not configured for SQLiteIntegrationModel.');
            }

            return static::$database;
        }

        protected static function prepare(string $sql): \PDOStatement
        {
            return static::database()->prepare($sql);
        }

        protected static function prepareAndBindParameters(string $sql, array $parameters = []): \PDOStatement
        {
            $statement = static::prepare($sql);
            static::bindParameters($statement, $parameters);
            return $statement;
        }

        protected static function bindParameters(\PDOStatement $statement, array $parameters = []): void
        {
            foreach ($parameters as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
        }

        protected function performInsert(): bool
        {
            if (!$this->validate('create')) {
                return false;
            }

            $this->beforeCreate();
            $this->updateTimestamps();

            $attributes = $this->getFillableAttributes();
            $tableName = static::tableName();

            $columns = array_keys($attributes);
            $params = array_map(fn ($attr) => ":$attr", $columns);

            $sql = "INSERT INTO $tableName (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $params) . ")";
            $statement = static::prepare($sql);

            foreach ($attributes as $attribute => $value) {
                $statement->bindValue(":" . $attribute, $value);
            }

            try {
                $statement->execute();
                $primaryKey = static::primaryKey();

                if (is_string($primaryKey) && empty($this->$primaryKey)) {
                    $this->$primaryKey = static::database()->lastInsertId();
                }

                $this->exists = true;
                $this->original = $this->attributesToArray();
                $this->dirty = [];
                $this->afterCreate();
                return true;
            } catch (\PDOException $e) {
                throw new \Exception('Error inserting record: ' . $e->getMessage(), 0, $e);
            }
        }

        protected function validates(): array
        {
            return [];
        }

        protected function softDelete(): bool
        {
            $this->fill(['deleted_at' => date('Y-m-d H:i:s')]);
            return $this->performUpdate();
        }
    }
}
