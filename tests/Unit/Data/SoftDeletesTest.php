<?php

use Lalaz\Data\ActiveRecord;
use Tests\Shared\Stubs\Entities\UserStub;
use Tests\Shared\Stubs\Entities\PostStub;

describe('SoftDeletesUnitTests', function() {
    it('should soft delete a record', function () {
        // Arrange
        $user = $this->getMockBuilder(UserStub::class)
            ->onlyMethods(['save'])
            ->getMock();

        // Define behavior for the save method
        $user->expects($this->once())
            ->method('save')
            ->willReturn(true);

        // Ensure the 'deleted_at' property is initially null
        expect($user->deleted_at)->toBeNull();

        // Act
        $result = $user->softDelete();

        // Assert
        expect($result)->toBeTrue();
        expect($user->deleted_at)->not()->toBeNull();
    });

    it('should restore a soft deleted record', function () {
        // Arrange
        $user = $this->getMockBuilder(UserStub::class)
            ->onlyMethods(['save'])
            ->getMock();

        // Simulate the user being soft deleted
        $user->deleted_at = date('Y-m-d H:i:s');

        // Mock the save method
        $user->expects($this->once())
            ->method('save')
            ->willReturn(true);

        // Act
        $result = $user->restore();

        // Assert
        expect($result)->toBeTrue();
        expect($user->deleted_at)->toBeNull();
    });

    it('should not soft delete if deleted_at does not exist', function () {
        // Arrange
        $modelWithoutSoftDeletes = $this->getMockBuilder(PostStub::class)
            ->onlyMethods(['save'])
            ->getMock();

        // Act & Assert
        expect(fn () => $modelWithoutSoftDeletes->softDelete())->toThrow(\Error::class);
    });

    it('should check if a record is deleted', function () {
        // Arrange
        $user = $this->getMockBuilder(UserStub::class)
            ->onlyMethods(['save'])
            ->getMock();

        // Simulate the user being soft deleted
        $user->deleted_at = date('Y-m-d H:i:s');

        // Act
        $isDeleted = $user->isDeleted();

        // Assert
        expect($isDeleted)->toBeTrue();
    });

    it('should force delete a record', function () {
        // Arrange
        $user = $this->getMockBuilder(UserStub::class)
            ->onlyMethods(['forceDelete'])
            ->getMock();

        // Define behavior for the forceDelete method
        $user->expects($this->once())
            ->method('forceDelete')
            ->willReturn(true);

        // Act
        $result = $user->forceDelete();

        // Assert
        expect($result)->toBeTrue();
    });
});
