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

    it('should begin a transaction', function () {
        // Mock the beginTransaction method
        $this->pdoMock->shouldReceive('beginTransaction')->once()->andReturnTrue();

        // Act: Begin the transaction
        $result = $this->database->beginTransaction();

        // Assert: Ensure it returns true
        expect($result)->toBeTrue();
    });

    it('should commit a transaction', function () {
        // Mock the commit method
        $this->pdoMock->shouldReceive('commit')->once()->andReturnTrue();

        // Act: Commit the transaction
        $result = $this->database->commit();

        // Assert: Ensure it returns true
        expect($result)->toBeTrue();
    });

    it('should roll back a transaction', function () {
        // Mock the rollBack method
        $this->pdoMock->shouldReceive('rollBack')->once()->andReturnTrue();

        // Act: Rollback the transaction
        $result = $this->database->rollBack();

        // Assert: Ensure it returns true
        expect($result)->toBeTrue();
    });

    it('should prepare a query', function () {
        // Mock the prepare method
        $query = 'SELECT * FROM users';
        $statementMock = mock(PDOStatement::class);
        $this->pdoMock->shouldReceive('prepare')->with($query)->once()->andReturn($statementMock);

        // Act: Prepare the query
        $statement = $this->database->prepare($query);

        // Assert: Ensure a PDOStatement is returned
        expect($statement)->toBeInstanceOf(PDOStatement::class);
    });

    it('should execute a query', function () {
        // Mock the query method
        $query = 'SELECT * FROM users';
        $statementMock = mock(PDOStatement::class);
        $this->pdoMock->shouldReceive('query')->with($query)->once()->andReturn($statementMock);

        // Act: Execute the query
        $result = $this->database->query($query);

        // Assert: Ensure it returns a PDOStatement
        expect($result)->toBeInstanceOf(PDOStatement::class);
    });

    it('should execute a statement with exec', function () {
        // Mock the exec method
        $query = 'DELETE FROM users WHERE id = 1';
        $this->pdoMock->shouldReceive('exec')->with($query)->once()->andReturn(1);

        // Act: Execute the statement
        $this->database->exec($query);

        // Assert: No exceptions should be thrown
        expect(true)->toBeTrue(); // This just ensures the test completes without error
    });

    it('should return the last inserted id', function () {
        // Mock the lastInsertId method
        $this->pdoMock->shouldReceive('lastInsertId')->once()->andReturn('123');

        // Act: Get the last insert ID
        $result = $this->database->lastInsertId();

        // Assert: Ensure it returns the correct value
        expect($result)->toBe('123');
    });
});
