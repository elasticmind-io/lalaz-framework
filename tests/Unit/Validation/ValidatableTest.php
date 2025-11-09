<?php

use Lalaz\Validation\Validatable;

class CustomErrorMessageTest extends Validatable
{
    public int $age = 0;

    protected function validates(): array
    {
        return [
            'age' => [[self::VALIDATE_MIN, 'min' => 18]]
        ];
    }

    public function errorMessage($rule)
    {
        if ($rule === self::VALIDATE_MIN) {
            return 'You must be at least 18 years old.';
        }
        return parent::errorMessage($rule);
    }
}

describe('Validatable', function () {
    it('can be instantiated with attributes', function () {
        $validatable = new class(['name' => 'John', 'email' => 'john@example.com']) extends Validatable {
            public string $name = '';
            public string $email = '';

            protected function validates(): array
            {
                return [];
            }
        };

        expect($validatable->name)->toBe('John');
        expect($validatable->email)->toBe('john@example.com');
    });

    it('can be instantiated without attributes', function () {
        $validatable = new class extends Validatable {
            public string $name = '';

            protected function validates(): array
            {
                return [];
            }
        };

        expect($validatable->name)->toBe('');
    });

    it('has hasAttribute method from HasAttributes trait', function () {
        $validatable = new class(['name' => 'Jane']) extends Validatable {
            public string $name = '';

            protected function validates(): array
            {
                return [];
            }
        };

        expect($validatable->hasAttribute('name'))->toBeTrue();
        expect($validatable->hasAttribute('nonexistent'))->toBeFalse();
    });

    it('has get method from HasAttributes trait', function () {
        $validatable = new class(['age' => 30]) extends Validatable {
            public int $age = 0;

            protected function validates(): array
            {
                return [];
            }
        };

        expect($validatable->get('age'))->toBe(30);
    });

    it('has validate method from HasValidation trait', function () {
        $validatable = new class(['email' => 'invalid']) extends Validatable {
            public string $email = '';

            protected function validates(): array
            {
                return [
                    'email' => [self::VALIDATE_EMAIL]
                ];
            }
        };

        $result = $validatable->validate();

        expect($result)->toBeFalse();
        expect($validatable->getErrors())->not->toBeEmpty();
    });

    it('has toArray method from Serializable trait', function () {
        $validatable = new class(['name' => 'Test', 'value' => 123]) extends Validatable {
            public string $name = '';
            public int $value = 0;

            protected function validates(): array
            {
                return [];
            }
        };

        $array = $validatable->toArray();

        expect($array)->toBeArray();
        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('value');
        expect($array['name'])->toBe('Test');
        expect($array['value'])->toBe(123);
    });

    it('has toJson method from Serializable trait', function () {
        $validatable = new class(['status' => 'active']) extends Validatable {
            public string $status = '';

            protected function validates(): array
            {
                return [];
            }
        };

        $json = $validatable->toJson();

        expect($json)->toBeString();
        $decoded = json_decode($json, true);
        expect($decoded['status'])->toBe('active');
    });

    it('has hide method from Serializable trait', function () {
        $validatable = new class(['password' => 'secret', 'name' => 'User']) extends Validatable {
            public string $password = '';
            public string $name = '';

            protected function validates(): array
            {
                return [];
            }
        };

        $array = $validatable->hide('password')->toArray();

        expect($array)->not->toHaveKey('password');
        expect($array)->toHaveKey('name');
    });    it('validates successfully with valid data', function () {
        $validatable = new class(['email' => 'valid@example.com']) extends Validatable {
            public string $email = '';

            protected function validates(): array
            {
                return [
                    'email' => [self::VALIDATE_EMAIL]
                ];
            }
        };

        $result = $validatable->validate();

        expect($result)->toBeTrue();
        expect($validatable->getErrors())->toBeEmpty();
    });

    it('can set custom error messages', function () {
        $validatable = CustomErrorMessageTest::build(['age' => 15]);

        $validatable->validate();
        $errors = $validatable->getErrors();

        expect($errors['age'][0])->toBe('You must be at least 18 years old.');
    });
});
