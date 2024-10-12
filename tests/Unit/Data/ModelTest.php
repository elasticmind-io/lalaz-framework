<?php

use Lalaz\Data\Model;
use Lalaz\Lalaz;

describe('ModelUnitTests', function() {
    beforeEach(function () {
        // Mock any global dependencies like database connections, if necessary.
        $this->mockDb = mock(Lalaz::class)->makePartial();
        $this->mockDb->shouldReceive('getInstance->db->prepare')->andReturnUsing(function ($sql) {
            return mock(PDOStatement::class)->makePartial();
        });

        // Lalaz::swap($this->mockDb);
    });

    it('should build a model with provided data', function () {
        // Arrange: Create a simple model subclass
        $model = new class extends Model {
            public $name;
        };

        // Act: Use the `build` method to set properties
        $builtModel = $model::build(['name' => 'John Doe']);

        // Assert: Check if the properties were set correctly
        expect($builtModel->name)->toBe('John Doe');
    });

    it('should return default error messages', function () {
        // Act: Get the error messages
        $errorMessages = Model::errorMessages();

        // Assert: Check if the default error messages are correctly returned
        expect($errorMessages)->toBeArray()
            ->and($errorMessages[Model::VALIDATE_REQUIRED])->toBe('This field is required')
            ->and($errorMessages[Model::VALIDATE_INT])->toBe('This field must be a number');
    });

    it('should add an error message to a specific attribute', function () {
        // Arrange: Create a simple model subclass
        $model = new class extends Model {
            public $name;
        };

        // Act: Add an error message to the name attribute
        $model->addError('name', 'Name is required');

        // Assert: Check if the error message was added correctly
        expect($model->errors)->toHaveKey('name')
            ->and($model->errors['name'][0])->toBe('Name is required');
    });

    it('should check if an attribute has an error', function () {
        // Arrange: Create a simple model subclass
        $model = new class extends Model {
            public $name;
        };

        // Act: Add an error to the name attribute and check if it has an error
        $model->addError('name', 'Name is required');
        $hasError = $model->hasError('name');

        // Assert: Check if the method returns true for the attribute with an error
        expect($hasError)->toBeTrue();
    });

    it('should retrieve the first error message for an attribute', function () {
        // Arrange: Create a simple model subclass
        $model = new class extends Model {
            public $name;
        };

        // Act: Add multiple errors and get the first one
        $model->addError('name', 'Name is required');
        $model->addError('name', 'Name should be at least 3 characters');
        $firstError = $model->getFirstError('name');

        // Assert: Ensure the first error message is returned
        expect($firstError)->toBe('Name is required');
    });

    it('should validate required field', function () {
        // Arrange: Create a model subclass with validation rules
        $model = new class extends Model {
            public $name;

            protected function validates(): array
            {
                return [
                    'name' => [Model::VALIDATE_REQUIRED]
                ];
            }
        };

        // Act: Run validation without setting the required field
        $isValid = $model->validate();

        // Assert: Validation should fail and an error should be added
        expect($isValid)->toBeFalse()
            ->and($model->getFirstError('name'))->toBe('This field is required');
    });

    it('should validate int field', function () {
        // Arrange: Create a model subclass with validation rules
        $model = new class extends Model {
            public $age;

            protected function validates(): array
            {
                return [
                    'age' => [Model::VALIDATE_INT]
                ];
            }
        };

        // Act: Set an invalid value and validate
        $model->age = 'invalid';
        $isValid = $model->validate();

        // Assert: Validation should fail and an error should be added
        expect($isValid)->toBeFalse()
            ->and($model->getFirstError('age'))->toBe('This field must be a number');
    });
});
