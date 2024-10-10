<?php

use Mockery as m;
use Lalaz\Data\Relation;
use Lalaz\Data\Query\QueryBuilderInterface;
use Tests\Shared\Stubs\Entities\UserStub;

describe('RelationUnitTests', function() {
    beforeEach(function () {
        // Mock related class, query builder, and dependencies
        $this->relatedClass = UserStub::class;
        $this->foreignKey = 'user_id';
        $this->localValue = 1;
        $this->relationType = 'hasMany';
    });

    it('should initialize relation with hasMany type', function () {
        // Arrange
        $relation = new Relation(
            $this->relatedClass,
            $this->foreignKey,
            $this->localValue,
            $this->relationType);

        // Assert: Verifica se a relação foi inicializada corretamente
        expect($relation->getRelationType())->toBe('hasMany');
        expect($relation->getForeignKey())->toBe('user_id');
        expect($relation->getLocalKey())->toBeNull();
        expect($relation->getOwnerKey())->toBeNull();
    });

    it('should set a limit on the query', function () {
        // Arrange: Mock the SelectQueryBuilder
        $mockQuery = m::mock(SelectQueryBuilder::class);

        // Define o comportamento esperado no método `limit`
        $mockQuery->shouldReceive('limit')->once()->with(10);

        // Mock the Relation class to return the mocked query builder
        $relation = m::mock(Relation::class, [
            $this->relatedClass, 'foreignKey', 'localValue', 'relationType'
        ])->makePartial();

        $relation->shouldReceive('getQuery')->andReturn($mockQuery);

        // Act: Define o limite
        $relation->limit(10);
    });

    it('should set an offset on the query', function () {
        // Arrange
        $relation = new Relation($this->relatedClass, $this->foreignKey, $this->localValue, $this->relationType);
        $relation->getQuery()->shouldReceive('offset')->once()->with(5);

        // Act: Define o offset
        $relation->offset(5);
    });

    it('should add an orderBy clause to the query', function () {
        // Arrange
        $relation = new Relation($this->relatedClass, $this->foreignKey, $this->localValue, $this->relationType);
        $relation->getQuery()->shouldReceive('orderBy')->once()->with('created_at', 'ASC');

        // Act: Adiciona a cláusula orderBy
        $relation->orderBy('created_at', 'ASC');
    });

    it('should add a where clause to the query', function () {
        // Arrange
        $relation = new Relation($this->relatedClass, $this->foreignKey, $this->localValue, $this->relationType);
        $relation->getQuery()->shouldReceive('where')->once()->with('age > 18');

        // Act: Adiciona a cláusula where
        $relation->where(function ($query) {
            return $query->where('age > 18');
        });
    });

    it('should add a join clause to the query', function () {
        // Arrange: Mock the SelectQueryBuilder
        $mockQuery = m::mock(SelectQueryBuilder::class);

        // Define o comportamento esperado no método `join`
        $mockQuery->shouldReceive('innerJoin')
            ->once()
            ->with('profiles', 'profiles.user_id = users.id', 'INNER');

        // Mock the Relation class e sobrescreve o método getQuery para retornar o mock de query
        $relation = new Relation(UserStub::class, 'foreignKey', 'localValue', 'relationType');

        // Sobrescreve o retorno do getQuery
        $reflection = new \ReflectionClass($relation);
        $property = $reflection->getProperty('query');
        $property->setAccessible(true);
        $property->setValue($relation, $mockQuery);

        // Act: Adiciona a cláusula join
        $relation->join('profiles', 'profiles.user_id = users.id', 'INNER');
    });
});
