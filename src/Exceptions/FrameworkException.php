<?php declare(strict_types=1);

namespace Lalaz\Exceptions;

/**
 * Class FrameworkException
 *
 * Exception for framework-level errors (controller not found, configuration errors, etc).
 * These are typically developer errors that should be caught during development.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class FrameworkException extends \Exception
{
    protected array $context = [];

    public function __construct(
        string $message = "",
        array $context = [],
        \Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->context = $context;
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
            'context' => $this->context,
        ];
    }

    public static function controllerNotFound(string $controllerName): self
    {
        return new self(
            "Controller '{$controllerName}' was not found",
            ['controller' => $controllerName]
        );
    }

    public static function configurationError(string $message, array $context = []): self
    {
        return new self($message, $context);
    }
}
