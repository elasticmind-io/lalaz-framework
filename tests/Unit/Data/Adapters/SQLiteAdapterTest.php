<?php

use Lalaz\Data\Adapters\SQLiteAdapter;

if (!class_exists('SQLiteAdapterTestPdo')) {
    class SQLiteAdapterTestPdo extends \PDO
    {
        public function __construct()
        {
            parent::__construct('sqlite::memory:');
            $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }
    }
}

describe('SQLiteAdapter', function () {
    it('connects using provided path and executes statements', function () {
        $tempPath = tempnam(sys_get_temp_dir(), 'lalaz_sqlite_test_');
        $captured = [];
        $adapter = new SQLiteAdapter([
            'path' => $tempPath,
        ], function (string $dsn, array $options) use (&$captured) {
            $captured = compact('dsn', 'options');
            $pdo = new SQLiteAdapterTestPdo();
            foreach ($options as $attribute => $value) {
                $pdo->setAttribute($attribute, $value);
            }
            return $pdo;
        });

        expect(file_exists($tempPath))->toBeTrue();

        $adapter->connect();

        expect($captured['dsn'])->toBe('sqlite:' . $tempPath);
        expect($adapter->isConnected())->toBeTrue();

        $adapter->exec('CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $adapter->exec('INSERT INTO events (name) VALUES (:name)', ['name' => 'connect']);

        $statement = $adapter->query('SELECT name FROM events WHERE id = :id', ['id' => 1]);
        expect($statement->fetchColumn())->toBe('connect');
        expect($adapter->lastInsertId())->toBe('1');

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    });
});
