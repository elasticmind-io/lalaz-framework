<?php declare(strict_types=1);

namespace Lalaz\Validation;

use Lalaz\Validation\Concerns\Serializable;
use Lalaz\Validation\Concerns\HasValidation;
use Lalaz\Validation\Concerns\HasAttributes;

/**
 * Class Validatable
 *
 * Base class for objects that need validation, serialization, and attribute access.
 * Typically used for form inputs, API requests, and data transfer objects.
 *
 * This class provides a foundation for validating user input, converting data
 * to arrays/JSON, hiding sensitive properties, and accessing attributes safely.
 *
 * Example usage:
 * ```php
 * class LoginInput extends Validatable
 * {
 *     public string $email;
 *     public string $password;
 *
 *     protected function validates(): array
 *     {
 *         return [
 *             'email' => [self::VALIDATE_REQUIRED, self::VALIDATE_EMAIL],
 *             'password' => [self::VALIDATE_REQUIRED, ['min' => 8]]
 *         ];
 *     }
 * }
 *
 * $input = LoginInput::build($request->body());
 * if (!$input->validate()) {
 *     return $response->json(['errors' => $input->getErrors()], 422);
 * }
 * ```
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
abstract class Validatable
{
    use Serializable;
    use HasValidation;
    use HasAttributes;

    /** @var array The attributes that should be hidden for serialization. */
    protected array $hidden = [];

    /**
     * Constructor.
     *
     * @param array $attributes Initial attributes to set on the model.
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Magic method to get an attribute.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }
}
