<?php

use Lalaz\Data\Relation;
use Lalaz\Data\Query\IQueryBuilder;
use Tests\Shared\Stubs\Entities\UserStub;

describe('RelationUnitTests', function() {
    beforeEach(function () {
        // Mock related class, query builder, and dependencies
        $this->relatedClass = UserStub::class;
        $this->foreignKey = 'user_id';
        $this->localValue = 1;
        $this->relationType = 'hasMany';
        $this->mockQueryBuilder = mock(IQueryBuilder::class);
    });

    it('should initialize relation with hasMany type', function () {
        // Arrange
        $relation = new Relation($this->relatedClass, $this->foreignKey, $this->localValue, $this->relationType);

        // Assert: Verifica se a relação foi inicializada corretamente
        expect($relation->getRelationType())->toBe('hasMany');
        expect($relation->getForeignKey())->toBe('user_id');
        expect($relation->getLocalKey())->toBeNull();
        expect($relation->getOwnerKey())->toBeNull();
    });

    it('should set a limit on the query', function () {
        // Arrange
        $relation = new Relation($this->relatedClass, $this->foreignKey, $this->localValue, $this->relationType);
        $relation->getQuery()->shouldReceive('limit')->once()->with(10);

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
        // Arrange
        $relation = new Relation($this->relatedClass, $this->foreignKey, $this->localValue, $this->relationType);
        $relation->getQuery()->shouldReceive('join')->once()->with('profiles', 'profiles.user_id = users.id', 'INNER');

        // Act: Adiciona a cláusula join
        $relation->join('profiles', 'profiles.user_id = users.id', 'INNER');
    });

    it('should throw an exception when retrieving related model for invalid relation type', function () {
        // Arrange: Define um tipo de relação inválido
        $invalidRelationType = 'invalid';
        $relation = new Relation($this->relatedClass, $this->foreignKey, $this->localValue, $invalidRelationType);

        // Assert: Verifica se uma exceção é lançada ao tentar buscar o modelo
        $this->expectException(Exception::class);
        $relation->get();
    });
});
