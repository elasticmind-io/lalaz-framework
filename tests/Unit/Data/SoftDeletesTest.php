<?php

use PHPUnit\Framework\TestCase;
use Lalaz\Data\ActiveRecord;
use Tests\Stubs\Entities\UserStub;
use Tests\Stubs\Entities\PostStub;


class SoftDeletesTest extends TestCase
{
    /**
     * Test that soft delete marks the record as deleted.
     */
    public function testShouldSoftDeleteARecord()
    {
        // Arrange: Create a mock of the User class and mock the save method
        $user = $this->getMockBuilder(UserStub::class)
            ->onlyMethods(['save'])
            ->getMock();

        // Define behavior for the save method
        $user->expects($this->once())
            ->method('save')
            ->willReturn(true);

        // Ensure the 'deleted_at' property is initially null
        $this->assertNull($user->deleted_at);

        // Act: Call the softDelete method
        $result = $user->softDelete();

        // Assert: Ensure the soft delete was successful
        $this->assertTrue($result);
        $this->assertNotNull($user->deleted_at); // 'deleted_at' should now have a timestamp
    }

    /**
     * Test that a soft deleted record can be restored.
     */
    public function testShouldRestoreASoftDeletedRecord()
    {
        // Arrange: Create a mock of the User class and mock the save method
        $user = $this->getMockBuilder(UserStub::class)
            ->onlyMethods(['save'])
            ->getMock();

        // Simulate the user being soft deleted
        $user->deleted_at = date('Y-m-d H:i:s');

        // Mock the save method
        $user->expects($this->once())
            ->method('save')
            ->willReturn(true);

        // Act: Call the restore method
        $result = $user->restore();

        // Assert: Ensure the record was restored
        $this->assertTrue($result);
        $this->assertNull($user->deleted_at); // 'deleted_at' should now be null
    }

    /**
     * Test that soft delete throws an exception if deleted_at is not present.
     */
    public function testShouldNotSoftDeleteIfDeletedAtDoesNotExist()
    {
        // Arrange: Create a mock of a class that does not support SoftDeletes
        $modelWithoutSoftDeletes = $this->getMockBuilder(PostStub::class)
            ->onlyMethods(['save'])
            ->getMock();

        // Act & Assert: Expect an exception when softDelete is called on a model without 'deleted_at'
        $this->expectException(\Error::class);

        $modelWithoutSoftDeletes->softDelete();  // Attempt to soft delete but throw an exception
    }

    /**
     * Test that the isDeleted method correctly detects a soft deleted record.
     */
    public function testShouldCheckIfARecordIsDeleted()
    {
        // Arrange: Create a mock of the User class
        $user = $this->getMockBuilder(UserStub::class)
            ->onlyMethods(['save'])
            ->getMock();

        // Simulate the user being soft deleted
        $user->deleted_at = date('Y-m-d H:i:s');

        // Act: Check if the isDeleted method returns true
        $isDeleted = $user->isDeleted();

        // Assert: Ensure the record is marked as deleted
        $this->assertTrue($isDeleted);
    }

    /**
     * Test that a record can be force deleted (permanently deleted).
     */
    public function testShouldForceDeleteARecord()
    {
        // Arrange: Create a mock of the User class and mock the deleteFromDatabase method
        $user = $this->getMockBuilder(UserStub::class)
            ->onlyMethods(['forceDelete'])
            ->getMock();

        // Define behavior for the deleteFromDatabase method
        $user->expects($this->once())
            ->method('forceDelete')
            ->willReturn(true);

        // Act: Call the forceDelete method
        $result = $user->forceDelete();

        // Assert: Ensure the force delete was successful
        $this->assertTrue($result);
    }
}
