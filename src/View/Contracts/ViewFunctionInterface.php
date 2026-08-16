<?php declare(strict_types=1);

namespace Lalaz\View\Contracts;

/**
 * Interface ViewFunctionInterface
 *
 * Contract for template engine function adapters.
 * Allows the framework to provide view helpers without being tied
 * to a specific template engine (Twig, Blade, etc.).
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
interface ViewFunctionInterface
{
    /**
     * Get the name of the function as it will be used in templates.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the callable that implements the function logic.
     *
     * @return callable
     */
    public function getCallable(): callable;

    /**
     * Get function options (e.g., is_safe for Twig).
     *
     * @return array
     */
    public function getOptions(): array;
}
