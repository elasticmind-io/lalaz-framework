<?php declare(strict_types=1);

namespace Lalaz\View;

/**
 * Class Utils
 *
 * @deprecated This class has been replaced by ViewHelpers.
 *             It remains for backward compatibility but should not be used.
 *             Will be removed in version 2.0.
 *
 * This class was tightly coupled to Twig and has been replaced with
 * ViewHelpers which is template-engine-agnostic.
 *
 * Migration Guide:
 * ---------------
 * Instead of using Utils directly, use ViewHelpers in your template provider:
 *
 * ```php
 * // In your TwigTemplateEngine or provider:
 * foreach (ViewHelpers::all() as $helper) {
 *     $twig->addFunction(new \Twig\TwigFunction(
 *         $helper->getName(),
 *         $helper->getCallable(),
 *         $helper->getOptions()
 *     ));
 * }
 * ```
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 * @see ViewHelpers For the new implementation
 */
class Utils
{
    /**
     * @deprecated Use ViewHelpers::all() instead
     */
    public static function all(): array
    {
        trigger_error(
            'Utils::all() is deprecated. Use ViewHelpers::all() instead.',
            E_USER_DEPRECATED
        );

        return ViewHelpers::all();
    }
}
