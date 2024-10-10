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

        // Assert
        expect($relation->getRelationType())->toBe('hasMany');
        expect($relation->getForeignKey())->toBe('user_id');
        expect($relation->getLocalKey())->toBeNull();
        expect($relation->getOwnerKey())->toBeNull();
    });
});
