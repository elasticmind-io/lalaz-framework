<?php

use Lalaz\Data\Adapters\PostgresAdapter;

if (!class_exists('PostgresAdapterTestPdo')) {
    class PostgresAdapterTestPdo extends \PDO
    {
        public array $loggedStatements = [];

        public function __construct()
        {
            parent::__construct('sqlite::memory:');
            $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }

        public function exec($statement): int|false
        {
            $this->loggedStatements[] = $statement;
            if (str_starts_with($statement, 'SET search_path')) {
                return 0;
            }

            return parent::exec($statement);
        }
    }
}

if (!class_exists('PostgresAdapterFakeStatement')) {
    class PostgresAdapterFakeStatement extends \PDOStatement
    {
        public array $executedParams = [];
        private array $rows = [];

        protected function __construct() {}

        public static function withRows(array $rows): self
        {
            /** @var self $instance */
            $instance = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
            $instance->rows = $rows;
            return $instance;
        }

        public function execute(?array $params = null): bool
        {
            $this->executedParams[] = $params ?? [];
            return true;
        }

        public function fetch(int $mode = \PDO::FETCH_DEFAULT, int $cursorOrientation = \PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
        {
            if ($this->rows === []) {
                return false;
            }

            return array_shift($this->rows);
        }
    }
}

if (!class_exists('PostgresAdapterFakePdo')) {
    class PostgresAdapterFakePdo extends \PDO
    {
        /** @var PostgresAdapterFakeStatement[] */
        private array $preparedStatements;
        public array $setAttributes = [];
        public array $execStatements = [];
        public array $preparedSql = [];
        public ?string $lastInsertSequence = null;
        public string $lastInsertReturn = 'generated-id';

        public function __construct(array $preparedStatements = [])
        {
            $this->preparedStatements = $preparedStatements;
        }

        public function setAttribute($attribute, $value): bool
        {
            $this->setAttributes[$attribute] = $value;
            return true;
        }

        public function exec($statement): int|false
        {
            $this->execStatements[] = $statement;
            return 0;
        }

        public function prepare($statement, $options = []): \PDOStatement|false
        {
            $this->preparedSql[] = $statement;
            return array_shift($this->preparedStatements);
        }

        public function beginTransaction(): bool
        {
            $this->execStatements[] = '__begin';
            return true;
        }

        public function commit(): bool
        {
            $this->execStatements[] = '__commit';
            return true;
        }

        public function rollBack(): bool
        {
            $this->execStatements[] = '__rollback';
            return true;
        }

        public function lastInsertId(?string $name = null): string
        {
            $this->lastInsertSequence = $name;
            return $name ? "seq:$name" : $this->lastInsertReturn;
        }
    }
}

describe('PostgresAdapter', function () {
    it('connects, sets search path and tracks returning inserts', function () {
        $captured = [];
        $pdoInstance = null;
        $adapter = new PostgresAdapter([
            'host' => 'pg.internal',
            'port' => '5433',
            'database' => 'analytics',
            'user' => 'reporter',
            'password' => 'topsecret',
            'schema' => 'custom',
        ], function (string $dsn, string $user, string $password, array $options) use (&$captured, &$pdoInstance) {
            $captured = compact('dsn', 'user', 'password', 'options');
            $pdo = new PostgresAdapterTestPdo();
            $pdoInstance = $pdo;
            foreach ($options as $attribute => $value) {
                $pdo->setAttribute($attribute, $value);
            }
            return $pdo;
        });

        $adapter->connect();

        expect($captured['dsn'])->toBe('pgsql:host=pg.internal;port=5433;dbname=analytics');
        expect($captured['user'])->toBe('reporter');
        expect($adapter->isConnected())->toBeTrue();
        expect($captured['options'][PDO::ATTR_EMULATE_PREPARES] ?? null)->toBeFalse();

        expect($pdoInstance)->not->toBeNull();
        expect($pdoInstance)->toBeInstanceOf(PostgresAdapterTestPdo::class);
        /** @var PostgresAdapterTestPdo $pdoInstance */
        $pdoInstance = $pdoInstance;
        expect($pdoInstance->loggedStatements)->toContain('SET search_path TO custom, public');

        $adapter->exec('CREATE TABLE messages (id INTEGER PRIMARY KEY AUTOINCREMENT, body TEXT)');
        $statement = $adapter->query('INSERT INTO messages (body) VALUES (:body) RETURNING id', ['body' => 'hello']);
        expect($statement)->toBeInstanceOf(\PDOStatement::class);
        expect($adapter->lastInsertId())->toBe('1');
    });

    it('captures returning ids via query using fake pdo', function () {
        $statement = PostgresAdapterFakeStatement::withRows([['id' => 42]]);
        $pdo = new PostgresAdapterFakePdo([$statement]);

        $adapter = new PostgresAdapter([
            'database' => 'db',
            'user' => 'user',
            'password' => 'pass',
            'schema' => 'reporting',
        ], static fn () => $pdo);

        $adapter->connect();
        expect($pdo->execStatements)->toContain('SET search_path TO reporting, public');

        $result = $adapter->query('INSERT foo RETURNING id', ['foo' => 'bar']);
        expect($result)->toBeInstanceOf(\PDOStatement::class);
        expect($adapter->lastInsertId())->toBe('42');
        expect($statement->executedParams[0])->toBe(['foo' => 'bar']);
    });

    it('captures returning ids via exec using fake pdo', function () {
        $statement = PostgresAdapterFakeStatement::withRows([['id' => 99]]);
        $pdo = new PostgresAdapterFakePdo([$statement]);

        $adapter = new PostgresAdapter([
            'database' => 'db',
            'user' => 'user',
            'password' => 'pass',
        ], static fn () => $pdo);

        $adapter->connect();
        $adapter->exec('UPDATE foo SET bar = :bar RETURNING id', ['bar' => 'baz']);

        expect($adapter->lastInsertId())->toBe('99');
        expect($statement->executedParams[0])->toBe(['bar' => 'baz']);
    });

    it('delegates lastInsertId to PDO when no cached value and sequence provided', function () {
        $pdo = new PostgresAdapterFakePdo();
        $pdo->lastInsertReturn = '14';

        $adapter = new PostgresAdapter([
            'database' => 'db',
            'user' => 'user',
            'password' => 'pass',
        ], static fn () => $pdo);

        $adapter->connect();

        expect($adapter->lastInsertId('custom_seq'))->toBe('seq:custom_seq');
        expect($pdo->lastInsertSequence)->toBe('custom_seq');
        expect($adapter->lastInsertId())->toBe('14');
    });

    it('throws runtime exception when connection fails', function () {
        $adapter = new PostgresAdapter([
            'database' => 'db',
            'user' => 'user',
            'password' => 'pass',
        ], static function () {
            throw new \PDOException('boom');
        });

        expect(fn () => $adapter->connect())->toThrow(RuntimeException::class, 'Failed to connect to PostgreSQL: boom');
    });

    it('proxies transaction helpers and exposes PDO instance', function () {
        $pdo = new PostgresAdapterFakePdo();

        $adapter = new PostgresAdapter([
            'database' => 'db',
            'user' => 'user',
            'password' => 'pass',
        ], static fn () => $pdo);

        $adapter->connect();

        expect($adapter->isConnected())->toBeTrue();
        expect($adapter->getPdo())->toBe($pdo);

        expect($adapter->beginTransaction())->toBeTrue();
        expect($adapter->commit())->toBeTrue();
        expect($adapter->rollBack())->toBeTrue();

        expect($pdo->execStatements)->toContain('__begin');
        expect($pdo->execStatements)->toContain('__commit');
        expect($pdo->execStatements)->toContain('__rollback');
    });
});
