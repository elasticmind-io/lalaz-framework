<?php declare(strict_types=1);

use Lalaz\Data\Database;
use Lalaz\Data\Adapters\ConnectionAdapterInterface;
use PDOStatement;

describe('Database', function() {

    beforeEach(function() {
        // Create a mock adapter
        $this->adapterMock = mock(ConnectionAdapterInterface::class);
    });

    describe('Constructor', function() {
        it('constructs with adapter and calls connect', function() {
            $this->adapterMock->shouldReceive('connect')->once();

            $db = new Database($this->adapterMock);

            expect($db)->toBeInstanceOf(Database::class);
        });

        it('stores the adapter internally', function() {
            $this->adapterMock->shouldReceive('connect')->once();

            $db = new Database($this->adapterMock);

            expect($db->getAdapter())->toBe($this->adapterMock);
        });
    });

    describe('Transaction Methods', function() {
        beforeEach(function() {
            $this->adapterMock->shouldReceive('connect')->once();
            $this->db = new Database($this->adapterMock);
        });

        it('begins a transaction', function() {
            $this->adapterMock->shouldReceive('beginTransaction')
                ->once()
                ->andReturn(true);

            $result = $this->db->beginTransaction();

            expect($result)->toBeTrue();
        });

        it('commits a transaction', function() {
            $this->adapterMock->shouldReceive('commit')
                ->once()
                ->andReturn(true);

            $result = $this->db->commit();

            expect($result)->toBeTrue();
        });

        it('rolls back a transaction', function() {
            $this->adapterMock->shouldReceive('rollBack')
                ->once()
                ->andReturn(true);

            $result = $this->db->rollBack();

            expect($result)->toBeTrue();
        });

        it('handles transaction failure on begin', function() {
            $this->adapterMock->shouldReceive('beginTransaction')
                ->once()
                ->andReturn(false);

            $result = $this->db->beginTransaction();

            expect($result)->toBeFalse();
        });

        it('handles transaction failure on commit', function() {
            $this->adapterMock->shouldReceive('commit')
                ->once()
                ->andReturn(false);

            $result = $this->db->commit();

            expect($result)->toBeFalse();
        });

        it('handles transaction failure on rollback', function() {
            $this->adapterMock->shouldReceive('rollBack')
                ->once()
                ->andReturn(false);

            $result = $this->db->rollBack();

            expect($result)->toBeFalse();
        });
    });

    describe('Query Methods', function() {
        beforeEach(function() {
            $this->adapterMock->shouldReceive('connect')->once();
            $this->db = new Database($this->adapterMock);
        });

        it('prepares a SQL statement', function() {
            $pdoStatementMock = mock(PDOStatement::class);

            $this->adapterMock->shouldReceive('prepare')
                ->once()
                ->with('SELECT * FROM users')
                ->andReturn($pdoStatementMock);

            $result = $this->db->prepare('SELECT * FROM users');

            expect($result)->toBe($pdoStatementMock);
        });

        it('executes a query without bindings', function() {
            $expectedResult = [['id' => 1, 'name' => 'John']];

            $this->adapterMock->shouldReceive('query')
                ->once()
                ->with('SELECT * FROM users', [])
                ->andReturn($expectedResult);

            $result = $this->db->query('SELECT * FROM users');

            expect($result)->toBe($expectedResult);
        });

        it('executes a query with bindings', function() {
            $bindings = ['id' => 1];
            $expectedResult = [['id' => 1, 'name' => 'John']];

            $this->adapterMock->shouldReceive('query')
                ->once()
                ->with('SELECT * FROM users WHERE id = :id', $bindings)
                ->andReturn($expectedResult);

            $result = $this->db->query('SELECT * FROM users WHERE id = :id', $bindings);

            expect($result)->toBe($expectedResult);
        });

        it('executes a statement without bindings', function() {
            $this->adapterMock->shouldReceive('exec')
                ->once()
                ->with('DELETE FROM users', []);

            $this->db->exec('DELETE FROM users');

            expect(true)->toBeTrue(); // If no exception, test passes
        });

        it('executes a statement with bindings', function() {
            $bindings = ['id' => 1];

            $this->adapterMock->shouldReceive('exec')
                ->once()
                ->with('DELETE FROM users WHERE id = :id', $bindings);

            $this->db->exec('DELETE FROM users WHERE id = :id', $bindings);

            expect(true)->toBeTrue(); // If no exception, test passes
        });
    });

    describe('Utility Methods', function() {
        beforeEach(function() {
            $this->adapterMock->shouldReceive('connect')->once();
            $this->db = new Database($this->adapterMock);
        });

        it('returns last insert ID', function() {
            $this->adapterMock->shouldReceive('lastInsertId')
                ->once()
                ->andReturn('42');

            $result = $this->db->lastInsertId();

            expect($result)->toBe('42');
        });

        it('checks connection status when connected', function() {
            $this->adapterMock->shouldReceive('isConnected')
                ->once()
                ->andReturn(true);

            $result = $this->db->isConnected();

            expect($result)->toBeTrue();
        });

        it('checks connection status when not connected', function() {
            $this->adapterMock->shouldReceive('isConnected')
                ->once()
                ->andReturn(false);

            $result = $this->db->isConnected();

            expect($result)->toBeFalse();
        });

        it('returns the adapter instance', function() {
            $adapter = $this->db->getAdapter();

            expect($adapter)->toBe($this->adapterMock);
        });
    });

    describe('Real-world Scenarios', function() {
        beforeEach(function() {
            $this->adapterMock->shouldReceive('connect')->once();
            $this->db = new Database($this->adapterMock);
        });

        it('performs a complete transaction workflow', function() {
            $this->adapterMock->shouldReceive('beginTransaction')
                ->once()
                ->andReturn(true);

            $this->adapterMock->shouldReceive('exec')
                ->once()
                ->with('INSERT INTO users (name) VALUES (:name)', ['name' => 'John']);

            $this->adapterMock->shouldReceive('lastInsertId')
                ->once()
                ->andReturn('1');

            $this->adapterMock->shouldReceive('commit')
                ->once()
                ->andReturn(true);

            // Start transaction
            $begin = $this->db->beginTransaction();
            expect($begin)->toBeTrue();

            // Execute insert
            $this->db->exec('INSERT INTO users (name) VALUES (:name)', ['name' => 'John']);

            // Get inserted ID
            $id = $this->db->lastInsertId();
            expect($id)->toBe('1');

            // Commit
            $commit = $this->db->commit();
            expect($commit)->toBeTrue();
        });

        it('performs a rollback on error', function() {
            $this->adapterMock->shouldReceive('beginTransaction')
                ->once()
                ->andReturn(true);

            $this->adapterMock->shouldReceive('rollBack')
                ->once()
                ->andReturn(true);

            // Start transaction
            $begin = $this->db->beginTransaction();
            expect($begin)->toBeTrue();

            // Simulate error and rollback
            $rollback = $this->db->rollBack();
            expect($rollback)->toBeTrue();
        });

        it('executes multiple queries in sequence', function() {
            $this->adapterMock->shouldReceive('query')
                ->once()
                ->with('SELECT * FROM users', [])
                ->andReturn([['id' => 1]]);

            $this->adapterMock->shouldReceive('query')
                ->once()
                ->with('SELECT * FROM posts WHERE user_id = :id', ['id' => 1])
                ->andReturn([['id' => 1, 'title' => 'Post']]);

            $users = $this->db->query('SELECT * FROM users');
            expect($users)->toBeArray();
            expect($users)->toHaveCount(1);

            $posts = $this->db->query('SELECT * FROM posts WHERE user_id = :id', ['id' => 1]);
            expect($posts)->toBeArray();
            expect($posts)->toHaveCount(1);
        });

        it('prepares and executes a statement', function() {
            $pdoStatementMock = mock(PDOStatement::class);

            $this->adapterMock->shouldReceive('prepare')
                ->once()
                ->with('SELECT * FROM users WHERE id = :id')
                ->andReturn($pdoStatementMock);

            $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
            expect($stmt)->toBe($pdoStatementMock);
        });
    });

    describe('Edge Cases', function() {
        beforeEach(function() {
            $this->adapterMock->shouldReceive('connect')->once();
            $this->db = new Database($this->adapterMock);
        });

        it('handles empty query result', function() {
            $this->adapterMock->shouldReceive('query')
                ->once()
                ->with('SELECT * FROM users WHERE id = :id', ['id' => 999])
                ->andReturn([]);

            $result = $this->db->query('SELECT * FROM users WHERE id = :id', ['id' => 999]);

            expect($result)->toBe([]);
        });

        it('handles last insert ID as string zero', function() {
            $this->adapterMock->shouldReceive('lastInsertId')
                ->once()
                ->andReturn('0');

            $result = $this->db->lastInsertId();

            expect($result)->toBe('0');
        });

        it('handles query with null binding value', function() {
            $bindings = ['status' => null];

            $this->adapterMock->shouldReceive('query')
                ->once()
                ->with('SELECT * FROM users WHERE status = :status', $bindings)
                ->andReturn([]);

            $result = $this->db->query('SELECT * FROM users WHERE status = :status', $bindings);

            expect($result)->toBe([]);
        });

        it('handles exec with empty bindings array', function() {
            $this->adapterMock->shouldReceive('exec')
                ->once()
                ->with('TRUNCATE TABLE users', []);

            $this->db->exec('TRUNCATE TABLE users', []);

            expect(true)->toBeTrue();
        });

        it('handles complex SQL with multiple bindings', function() {
            $bindings = [
                'status' => 'active',
                'age' => 18,
                'country' => 'US'
            ];

            $this->adapterMock->shouldReceive('query')
                ->once()
                ->with(
                    'SELECT * FROM users WHERE status = :status AND age >= :age AND country = :country',
                    $bindings
                )
                ->andReturn([['id' => 1, 'name' => 'John']]);

            $result = $this->db->query(
                'SELECT * FROM users WHERE status = :status AND age >= :age AND country = :country',
                $bindings
            );

            expect($result)->toHaveCount(1);
        });
    });
});
