<?php

use Lalaz\Data\Concerns\DatabaseReadable;
use Lalaz\Data\Model;
use Lalaz\Data\PagedResult;
use Lalaz\Data\Query\Expressions;
use Lalaz\Data\Query\SelectQueryBuilder;

beforeEach(function () {
    DatabaseReadableTestModel::resetFakes();
});

describe('DatabaseReadable', function () {
    it('finds a model by id and hydrates attributes', function () {
        DatabaseReadableTestModel::fakeNextResult([
            ['id' => 5, 'name' => 'Unit']
        ]);

        $model = DatabaseReadableTestModel::findById(5);

        expect($model)->toBeInstanceOf(DatabaseReadableTestModel::class);
        expect($model->id)->toBe(5);
        expect($model->name)->toBe('Unit');
        expect($model->exists)->toBeTrue();
        expect($model->dirty)->toBeEmpty();
        expect($model->original)->toBe(['id' => 5, 'name' => 'Unit']);

        $queries = DatabaseReadableTestModel::recordedQueries();
        expect($queries)->toHaveCount(1);
        expect($queries[0]['sql'])->toBe('SELECT * FROM test_models WHERE id = :id');
        expect($queries[0]['params'])->toBe(['id' => 5]);
    });

    it('returns null when record is not found', function () {
        DatabaseReadableTestModel::fakeNextResult([]);

        $model = DatabaseReadableTestModel::findById(99);

        expect($model)->toBeNull();
    });

    it('retrieves all models with ordering and soft delete constraint', function () {
        DatabaseReadableTestModel::enableSoftDeletes();
        DatabaseReadableTestModel::fakeNextResult([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $models = DatabaseReadableTestModel::findAll(orderBy: ['name' => 'DESC']);

        expect($models)->toHaveCount(2);
        expect($models[0])->toBeInstanceOf(DatabaseReadableTestModel::class);
        expect($models[0]->name)->toBe('Alice');

        $queries = DatabaseReadableTestModel::recordedQueries();
        expect($queries[0]['sql'])
            ->toContain('SELECT * FROM test_models WHERE deleted_at IS NULL ORDER BY name DESC');
    });

    it('builds paged results with correct metadata', function () {
        DatabaseReadableTestModel::enableSoftDeletes();
        DatabaseReadableTestModel::fakeNextResult([
            ['count' => 3],
        ]);

        DatabaseReadableTestModel::fakeNextResult([
            ['id' => 10, 'name' => 'Foo'],
            ['id' => 11, 'name' => 'Bar'],
        ]);

        /** @var PagedResult $paged */
        $paged = DatabaseReadableTestModel::findAllPaged(currentPage: 2, take: 2);

        expect($paged)->toBeInstanceOf(PagedResult::class);
        expect($paged->totalRecords)->toBe(3);
        expect($paged->currentPage)->toBe(2);
        expect($paged->pageSize)->toBe(2);
        expect($paged->totalPages())->toBe(2);
        expect($paged->records)->toHaveCount(2);

        $queries = DatabaseReadableTestModel::recordedQueries();
        expect($queries[0]['sql'])->toBe('SELECT COUNT(*) AS count FROM test_models WHERE deleted_at IS NULL');
        expect($queries[1]['sql'])
            ->toBe('SELECT * FROM test_models WHERE deleted_at IS NULL LIMIT 2 OFFSET 2');
    });

    it('counts records by expression with bound parameters', function () {
        DatabaseReadableTestModel::enableSoftDeletes();
        DatabaseReadableTestModel::fakeNextResult([
            ['count' => 4],
        ]);

        $expr = Expressions::create()->eq('status', 'active');
        $count = DatabaseReadableTestModel::countByExpression($expr);

        expect($count)->toBe(4);

        $queries = DatabaseReadableTestModel::recordedQueries();
        expect($queries[0]['sql'])
            ->toBe('SELECT COUNT(*) AS count FROM test_models WHERE status = :status AND deleted_at IS NULL');
        expect($queries[0]['params'])->toBe(['status' => 'active']);
    });

    it('retrieves collections matching expression', function () {
        DatabaseReadableTestModel::enableSoftDeletes();
        DatabaseReadableTestModel::fakeNextResult([
            ['id' => 7, 'name' => 'First'],
            ['id' => 8, 'name' => 'Second'],
        ]);

        $expr = Expressions::create()->gt('id', 5);
        $models = DatabaseReadableTestModel::findAllByExpression($expr, orderBy: ['id' => 'DESC']);

        expect($models)->toHaveCount(2);
        expect($models[0]->id)->toBe(7);

        $queries = DatabaseReadableTestModel::recordedQueries();
        expect($queries[0]['sql'])
            ->toBe('SELECT * FROM test_models WHERE id > :id AND deleted_at IS NULL ORDER BY id DESC');
        expect($queries[0]['params'])->toBe(['id' => 5]);
    });
});

if (!class_exists('FakePDOStatementForReadableTests')) {
    class FakePDOStatementForReadableTests extends PDOStatement
    {
        public array $executedParameters = [];
        public array $boundValues = [];
        private array $allRows = [];
        private array $rows = [];
        private array $defaultExecuteParams = [];

        protected function __construct() {}

        public static function make(array $rows, array $defaultExecuteParams = []): self
        {
            /** @var self $instance */
            $instance = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
            $instance->allRows = $rows;
            $instance->rows = $rows;
            $instance->defaultExecuteParams = $defaultExecuteParams;
            return $instance;
        }

        public function execute(?array $params = null): bool
        {
            $this->executedParameters[] = $params ?? $this->defaultExecuteParams;
            return true;
        }

        public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
        {
            $row = array_shift($this->rows);
            return $row ?? false;
        }

        public function fetchAll(int $mode = PDO::FETCH_DEFAULT, ...$args): array
        {
            return $this->allRows;
        }

        public function fetchColumn(int $column = 0): mixed
        {
            if ($this->allRows === []) {
                return false;
            }

            $values = array_values($this->allRows[0]);
            return $values[$column] ?? false;
        }

        public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
        {
            $this->boundValues[$param] = $value;
            return true;
        }
    }
}

if (!class_exists('DatabaseReadableTestModel')) {
    class DatabaseReadableTestModel extends Model
    {
        use DatabaseReadable;

        public ?int $id = null;
        public ?string $name = null;
        public array $dirty = [];
        public array $original = [];
        protected array $hidden = [];
        public bool $exists = false;

        private static array $queuedResults = [];
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
            return 'test_models';
        }

        public static function primaryKey(): string
        {
            return 'id';
        }

        public static function resetFakes(): void
        {
            self::$queuedResults = [];
            self::$queryLog = [];
            self::$softDeletes = false;
        }

        public static function fakeNextResult(array $rows): void
        {
            self::$queuedResults[] = $rows;
        }

        public static function enableSoftDeletes(): void
        {
            self::$softDeletes = true;
        }

        public static function recordedQueries(): array
        {
            return self::$queryLog;
        }

        protected static function nextRows(): array
        {
            return array_shift(self::$queuedResults) ?? [];
        }

        protected static function prepare(string $sql): PDOStatement
        {
            self::$queryLog[] = ['sql' => $sql, 'params' => null];
            return FakePDOStatementForReadableTests::make(self::nextRows());
        }

        protected static function prepareAndBindParameters(string $sql, array $parameters = []): PDOStatement
        {
            self::$queryLog[] = ['sql' => $sql, 'params' => $parameters];
            $statement = FakePDOStatementForReadableTests::make(self::nextRows(), $parameters);
            foreach ($parameters as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            return $statement;
        }

        protected static function bindParameters(PDOStatement $statement, array $parameters = []): void
        {
            foreach ($parameters as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
        }

        public static function applySoftDeleteConstraint(SelectQueryBuilder $query): SelectQueryBuilder
        {
            if (self::$softDeletes) {
                $query->andWhere('deleted_at IS NULL');
            }

            return $query;
        }

        public function attributesToArray(): array
        {
            return ['id' => $this->id, 'name' => $this->name];
        }
    }
}
