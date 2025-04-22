<?php

use Lalaz\Data\Database;
use PDO;
use PDOStatement;
use Mockery\MockInterface;

describe('DatabaseUnitTests', function() {
    beforeEach(function () {
        // Mock PDO and setup the database configuration
        $this->pdoMock = mock(PDO::class);
        $this->dbConfig = [
            'dsn' => 'sqlite::memory:',
            'user' => '',
            'password' => '',
        ];

        // Instantiate Database with mocked PDO
        $this->database = new Database($this->dbConfig);
        $reflection = new ReflectionClass(Database::class);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($this->database, $this->pdoMock);
    });
});
