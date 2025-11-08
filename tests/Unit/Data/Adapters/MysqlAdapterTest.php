<?php

use Lalaz\Data\Adapters\MysqlAdapter;

if (!class_exists('MysqlAdapterTestPdo')) {
    class MysqlAdapterTestPdo extends \PDO
    {
        public array $executedStatements = [];

        public function __construct()
        {
            parent::__construct('sqlite::memory:');
            $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }

        public function exec($statement): int|false
        {
            $this->executedStatements[] = $statement;
            return parent::exec($statement);
        }
    }
}

describe('MysqlAdapter', function () {
    it('connects using configuration and runs statements', function () {
        $captured = [];
        $adapter = new MysqlAdapter([
            'host' => 'db.internal',
            'port' => '3307',
            'database' => 'app',
            'user' => 'writer',
            'password' => 'secret',
        ], function (string $dsn, string $user, string $password, array $options) use (&$captured) {
            $captured = compact('dsn', 'user', 'password', 'options');
            $pdo = new MysqlAdapterTestPdo();
            foreach ($options as $attribute => $value) {
                $pdo->setAttribute($attribute, $value);
            }
            return $pdo;
        });

        $adapter->connect();

        expect($captured['dsn'])->toBe('mysql:host=db.internal;port=3307;dbname=app;charset=utf8mb4');
        expect($captured['user'])->toBe('writer');
        expect($captured['password'])->toBe('secret');
        expect($captured['options'][\PDO::ATTR_ERRMODE] ?? null)->toBe(\PDO::ERRMODE_EXCEPTION);
        expect($adapter->isConnected())->toBeTrue();

        $adapter->exec('CREATE TABLE messages (id INTEGER PRIMARY KEY AUTOINCREMENT, body TEXT)');

        $adapter->beginTransaction();
        $adapter->exec('INSERT INTO messages (body) VALUES (:body)', ['body' => 'first']);
        $adapter->commit();

        expect($adapter->lastInsertId())->toBe('1');

        $adapter->beginTransaction();
        $adapter->exec('INSERT INTO messages (body) VALUES (:body)', ['body' => 'rolled back']);
        $adapter->rollBack();

        $statement = $adapter->query('SELECT body FROM messages WHERE id = :id', ['id' => 1]);
        expect($statement->fetchColumn())->toBe('first');

        $count = $adapter->query('SELECT COUNT(*) FROM messages')->fetchColumn();
        expect((int) $count)->toBe(1);
    });
});
