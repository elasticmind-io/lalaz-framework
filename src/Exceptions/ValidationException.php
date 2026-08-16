<?php declare(strict_types=1);

namespace Lalaz\Exceptions;

/**
 * Class ValidationException
 *
 * Exception for validation errors with field-level error messages.
 * Typically used for form validation failures.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class ValidationException extends \Exception
{
    protected array $errors = [];
    protected array $context = [];

    public function __construct(
        array $errors,
        string $message = "Validation failed",
        array $context = [],
        \Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->errors = $errors;
        $this->context = $context;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    public function toArray(): array
    {
        return [
            'error' => true,
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'context' => $this->context,
        ];
    }
}
