<?php

use Lalaz\Validation\Validatable;

class SerializableTestModel extends Validatable
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public int $age = 0;

    protected function validates(): array
    {
        return [];
    }
}

describe('Serializable', function () {
    it('converts model to array with all public properties', function () {
        $model = new SerializableTestModel([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret',
            'age' => 30
        ]);

        $array = $model->toArray();

        expect($array)->toBeArray();
        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('email');
        expect($array)->toHaveKey('password');
        expect($array)->toHaveKey('age');
        expect($array['name'])->toBe('John Doe');
        expect($array['email'])->toBe('john@example.com');
        expect($array['age'])->toBe(30);
    });

    it('converts model to JSON string', function () {
        $model = new SerializableTestModel([
            'name' => 'Jane',
            'email' => 'jane@example.com'
        ]);

        $json = $model->toJson();

        expect($json)->toBeString();

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded['name'])->toBe('Jane');
        expect($decoded['email'])->toBe('jane@example.com');
    });

    it('hides specified properties from serialization', function () {
        $model = new SerializableTestModel([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'secret123'
        ]);

        $array = $model->hide('password')->toArray();

        expect($array)->not->toHaveKey('password');
        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('email');
    });

    it('can hide multiple properties', function () {
        $model = new SerializableTestModel([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'secret123',
            'age' => 25
        ]);

        $array = $model->hide('password', 'age')->toArray();

        expect($array)->not->toHaveKey('password');
        expect($array)->not->toHaveKey('age');
        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('email');
    });

    it('returns the same instance after hide for method chaining', function () {
        $model = new SerializableTestModel();
        $result = $model->hide('password');

        expect($result)->toBe($model);
    });

    it('hides properties in JSON output', function () {
        $model = new SerializableTestModel([
            'name' => 'Test',
            'password' => 'hidden'
        ]);

        $json = $model->hide('password')->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->not->toHaveKey('password');
        expect($decoded)->toHaveKey('name');
    });

    it('handles empty model gracefully', function () {
        $model = new SerializableTestModel();

        $array = $model->toArray();

        expect($array)->toBeArray();
        expect($array)->toHaveKey('name');
        expect($array['name'])->toBe('');
    });

    it('handles hiding non-existent properties without error', function () {
        $model = new SerializableTestModel(['name' => 'Test']);

        $array = $model->hide('nonexistent', 'also_fake')->toArray();

        expect($array)->toBeArray();
        expect($array)->toHaveKey('name');
    });

    it('can chain multiple hide calls', function () {
        $model = new SerializableTestModel([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'secret',
            'age' => 30
        ]);

        $array = $model
            ->hide('password')
            ->hide('age')
            ->toArray();

        expect($array)->not->toHaveKey('password');
        expect($array)->not->toHaveKey('age');
        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('email');
    });

    it('preserves data types in array conversion', function () {
        $model = new SerializableTestModel([
            'name' => 'Test',
            'age' => 42
        ]);

        $array = $model->toArray();

        expect($array['name'])->toBeString();
        expect($array['age'])->toBeInt();
        expect($array['age'])->toBe(42);
    });
});
