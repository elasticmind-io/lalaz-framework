<?php

use Lalaz\Validation\Validatable;

// Helper test classes
class RequiredFieldTest extends Validatable
{
    public string $name = '';
    protected function validates(): array
    {
        return ['name' => [self::VALIDATE_REQUIRED]];
    }
}

class EmailValidationTest extends Validatable
{
    public string $email = '';
    protected function validates(): array
    {
        return ['email' => [self::VALIDATE_EMAIL]];
    }
}

class IntValidationTest extends Validatable
{
    public $age = 0;
    protected function validates(): array
    {
        return ['age' => [self::VALIDATE_INT]];
    }
}

class MinLengthTest extends Validatable
{
    public string $password = '';
    protected function validates(): array
    {
        return ['password' => [[self::VALIDATE_MIN, 'min' => 8]]];
    }
}

class MaxLengthTest extends Validatable
{
    public string $name = '';
    protected function validates(): array
    {
        return ['name' => [[self::VALIDATE_MAX, 'max' => 20]]];
    }
}

class UrlValidationTest extends Validatable
{
    public string $website = '';
    protected function validates(): array
    {
        return ['website' => [self::VALIDATE_URL]];
    }
}

class IpValidationTest extends Validatable
{
    public string $ip = '';
    protected function validates(): array
    {
        return ['ip' => [self::VALIDATE_IP]];
    }
}

class DecimalValidationTest extends Validatable
{
    public $price = 0;
    protected function validates(): array
    {
        return ['price' => [self::VALIDATE_DECIMAL]];
    }
}

class MatchValidationTest extends Validatable
{
    public string $password = '';
    public string $password_confirmation = '';
    protected function validates(): array
    {
        return ['password_confirmation' => [[self::VALIDATE_MATCH, 'match' => 'password']]];
    }
}

class RegexValidationTest extends Validatable
{
    public string $phone = '';
    protected function validates(): array
    {
        return ['phone' => [[self::VALIDATE_REGEX, 'pattern' => '/^\d{3}-\d{4}$/']]];
    }
}

class CustomValidationTest extends Validatable
{
    public int $age = 0;
    protected function validates(): array
    {
        return ['age' => [[self::VALIDATE_CUSTOM, 'callback' => fn($value) => $value >= 18]]];
    }
}

class CustomMessageTest extends Validatable
{
    public string $email = '';
    protected function validates(): array
    {
        return ['email' => [[self::VALIDATE_EMAIL, 'message' => 'Custom error message']]];
    }
}

class MultipleRulesTest extends Validatable
{
    public string $password = '';
    protected function validates(): array
    {
        return [
            'password' => [
                self::VALIDATE_REQUIRED,
                [self::VALIDATE_MIN, 'min' => 8]
            ]
        ];
    }
}

class ContextValidationTest extends Validatable
{
    public string $password = '';
    protected function validates(): array
    {
        return ['password' => [[self::VALIDATE_REQUIRED, 'on' => 'create']]];
    }
}

describe('HasValidation', function () {
    it('validates required fields and fails when empty', function () {
        $instance = new RequiredFieldTest();
        $result = $instance->validate();

        expect($result)->toBeFalse();
        expect($instance->hasError('name'))->toBeTrue();
        expect($instance->getFirstError('name'))->toContain('required');
    });

    it('passes required validation when field has value', function () {
        $instance = new RequiredFieldTest(['name' => 'John']);
        $result = $instance->validate();

        expect($result)->toBeTrue();
        expect($instance->getErrors())->toBeEmpty();
    });

    it('validates email format and fails with invalid email', function () {
        $instance = new EmailValidationTest(['email' => 'invalid-email']);
        $result = $instance->validate();

        expect($result)->toBeFalse();
        expect($instance->hasError('email'))->toBeTrue();
    });

    it('passes email validation with valid email', function () {
        $instance = new EmailValidationTest(['email' => 'test@example.com']);
        expect($instance->validate())->toBeTrue();
    });

    it('validates integer type and fails with non-integer', function () {
        $instance = new IntValidationTest();
        $instance->age = 'not-a-number';

        expect($instance->validate())->toBeFalse();
        expect($instance->hasError('age'))->toBeTrue();
    });

    it('passes integer validation with valid integer', function () {
        $instance = new IntValidationTest();
        $instance->age = 25;

        expect($instance->validate())->toBeTrue();
    });

    it('validates minimum length and fails when too short', function () {
        $instance = new MinLengthTest(['password' => 'abc']);
        $result = $instance->validate();

        expect($result)->toBeFalse();
        expect($instance->hasError('password'))->toBeTrue();
        expect($instance->getFirstError('password'))->toContain('8');
    });

    it('validates maximum length and fails when too long', function () {
        $instance = new MaxLengthTest(['name' => 'This is a very long name that exceeds the maximum']);

        expect($instance->validate())->toBeFalse();
        expect($instance->hasError('name'))->toBeTrue();
    });

    it('validates URL format and fails with invalid URL', function () {
        $instance = new UrlValidationTest(['website' => 'not-a-url']);
        expect($instance->validate())->toBeFalse();
    });

    it('passes URL validation with valid URL', function () {
        $instance = new UrlValidationTest(['website' => 'https://example.com']);
        expect($instance->validate())->toBeTrue();
    });

    it('validates IP address and fails with invalid IP', function () {
        $instance = new IpValidationTest(['ip' => '999.999.999.999']);
        expect($instance->validate())->toBeFalse();
    });

    it('passes IP validation with valid IP', function () {
        $instance = new IpValidationTest(['ip' => '192.168.1.1']);
        expect($instance->validate())->toBeTrue();
    });

    it('validates decimal format and fails with non-decimal', function () {
        $instance = new DecimalValidationTest();
        $instance->price = 'not-a-decimal';

        expect($instance->validate())->toBeFalse();
    });

    it('validates matching fields and fails when different', function () {
        $instance = new MatchValidationTest([
            'password' => 'secret123',
            'password_confirmation' => 'different'
        ]);

        expect($instance->validate())->toBeFalse();
        expect($instance->hasError('password_confirmation'))->toBeTrue();
    });

    it('passes match validation when fields are equal', function () {
        $instance = new MatchValidationTest([
            'password' => 'secret123',
            'password_confirmation' => 'secret123'
        ]);

        expect($instance->validate())->toBeTrue();
    });

    it('validates using regex pattern and fails when pattern does not match', function () {
        $instance = new RegexValidationTest(['phone' => '123-invalid']);
        expect($instance->validate())->toBeFalse();
    });

    it('validates using custom callback and fails when callback returns false', function () {
        $instance = new CustomValidationTest();
        $instance->age = 15;

        expect($instance->validate())->toBeFalse();
        expect($instance->hasError('age'))->toBeTrue();
    });

    it('allows custom error messages', function () {
        $instance = new CustomMessageTest(['email' => 'invalid']);
        $instance->validate();

        expect($instance->getFirstError('email'))->toBe('Custom error message');
    });

    it('validates multiple rules for same field', function () {
        $instance = new MultipleRulesTest(['password' => '']);
        $instance->validate();

        expect($instance->hasError('password'))->toBeTrue();
        expect($instance->getErrors()['password'])->toHaveCount(2);
    });

    it('can build instance from array data', function () {
        $instance = RequiredFieldTest::build(['name' => 'John']);

        expect($instance->name)->toBe('John');
    });

    it('supports validation context', function () {
        $instance = new ContextValidationTest(['password' => '']);

        // Should fail on 'create' context
        expect($instance->validate('create'))->toBeFalse();

        // Should pass on 'update' context
        $instance->errors = [];
        expect($instance->validate('update'))->toBeTrue();
    });

    it('can add errors manually', function () {
        $instance = new RequiredFieldTest();
        $instance->addError('custom_field', 'Custom error message');

        expect($instance->hasError('custom_field'))->toBeTrue();
        expect($instance->getFirstError('custom_field'))->toBe('Custom error message');
    });

    it('returns all errors with getErrors', function () {
        $instance = new MultipleRulesTest(['password' => '']);
        $instance->validate();

        $errors = $instance->getErrors();
        expect($errors)->toBeArray();
        expect($errors)->toHaveKey('password');
    });
});
