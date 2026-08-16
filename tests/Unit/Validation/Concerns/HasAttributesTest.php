<?php

use Lalaz\Validation\Validatable;

class HasAttributesTestModel extends Validatable
{
    public string $name = '';
    public int $age = 0;
    public ?string $email = null;
    private string $privateField = 'private';

    protected function validates(): array
    {
        return [];
    }
}

describe('HasAttributes', function () {
    it('returns attribute value using get method', function () {
        $model = new HasAttributesTestModel(['name' => 'John', 'age' => 30]);

        expect($model->get('name'))->toBe('John');
        expect($model->get('age'))->toBe(30);
    });

    it('returns empty string for non-existent attribute', function () {
        $model = new HasAttributesTestModel();

        $result = $model->get('nonexistent');

        expect($result)->toBe('');
    });

    it('handles null values correctly', function () {
        $model = new HasAttributesTestModel(['name' => 'Test']);

        expect($model->get('email'))->toBeNull();
    });

    it('can access attribute directly via magic __get', function () {
        $model = new HasAttributesTestModel(['name' => 'Jane']);

        // Validatable uses __get which calls get()
        expect($model->name)->toBe('Jane');
    });

    it('returns default values for uninitialized attributes', function () {
        $model = new HasAttributesTestModel();

        expect($model->get('name'))->toBe('');
        expect($model->get('age'))->toBe(0);
    });

    it('can get attributes with different types', function () {
        $model = new HasAttributesTestModel([
            'name' => 'Test',
            'age' => 42
        ]);

        expect($model->get('name'))->toBeString();
        expect($model->get('age'))->toBeInt();
    });

    it('handles updated attribute values', function () {
        $model = new HasAttributesTestModel(['name' => 'Original']);

        expect($model->get('name'))->toBe('Original');

        $model->name = 'Updated';

        expect($model->get('name'))->toBe('Updated');
    });

    it('works with constructor-set attributes', function () {
        $model = new HasAttributesTestModel([
            'name' => 'Constructor',
            'age' => 25,
            'email' => 'test@example.com'
        ]);

        expect($model->get('name'))->toBe('Constructor');
        expect($model->get('age'))->toBe(25);
        expect($model->get('email'))->toBe('test@example.com');
    });

    it('returns empty string for attributes not in constructor', function () {
        $model = new HasAttributesTestModel();

        // Trying to get non-existent property
        $result = $model->get('fakeProperty');

        expect($result)->toBe('');
    });

    it('preserves type safety', function () {
        $model = new HasAttributesTestModel(['age' => 100]);

        $age = $model->get('age');

        expect($age)->toBeInt();
        expect($age)->toBe(100);
    });
});
