<?php declare(strict_types=1);

namespace Lalaz\View;

/**
 * Class ViewFunction
 *
 * Generic implementation of a view function that can be adapted
 * to any template engine (Twig, Blade, etc.).
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class ViewFunction implements Contracts\ViewFunctionInterface
{
    /**
     * @param string $name The function name
     * @param callable $callable The function implementation
     * @param array $options Additional options (e.g., is_safe for Twig)
     */
    public function __construct(
        private string $name,
        private $callable,
        private array $options = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }

    /**
     * @inheritDoc
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
