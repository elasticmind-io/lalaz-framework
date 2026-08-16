<?php declare(strict_types=1);

namespace Lalaz\View;

use Lalaz\Http\Concerns\FlashMessage;
use Lalaz\Security\CsrfProtection;

/**
 * Class ViewHelpers
 *
 * Provides template-engine-agnostic view helper functions.
 * These are pure PHP callables that can be adapted to any template engine
 * (Twig, Blade, Plates, etc.) by the specific provider implementation.
 *
 * Usage in provider:
 * ```php
 * foreach (ViewHelpers::all() as $helper) {
 *     // Adapt to your template engine
 *     $twig->addFunction(new TwigFunction(
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
 */
class ViewHelpers
{
    use FlashMessage;

    /**
     * Flash message helper.
     *
     * @return ViewFunction
     */
    public static function flashMessage(): ViewFunction
    {
        return new ViewFunction(
            'showFlashMessage',
            fn(string $name) => self::showFlashMessage($name)
        );
    }

    /**
     * Route URL helper.
     *
     * @return ViewFunction
     */
    public static function routeUrl(): ViewFunction
    {
        return new ViewFunction(
            'routeUrl',
            fn(string $action) => $action
        );
    }

    /**
     * Conditional helper - returns one of two values based on condition.
     *
     * @return ViewFunction
     */
    public static function conditional(): ViewFunction
    {
        return new ViewFunction(
            'conditional',
            fn(bool $condition, string $left, string $right) => $condition ? $left : $right
        );
    }

    /**
     * Render if helper - returns value only if condition is true.
     *
     * @return ViewFunction
     */
    public static function renderIf(): ViewFunction
    {
        return new ViewFunction(
            'renderIf',
            fn(string $left, bool $condition) => $condition ? $left : null
        );
    }

    /**
     * Asset helper - resolves asset paths using manifest.json.
     *
     * @return ViewFunction
     */
    public static function asset(): ViewFunction
    {
        return new ViewFunction(
            'asset',
            function (string $path) {
                static $manifest = null;
                static $lastModifiedTime = null;

                $manifestPath = './public/dist/manifest.json';

                if (!file_exists($manifestPath)) {
                    return '';
                }

                $currentModifiedTime = filemtime($manifestPath);

                if ($manifest === null || $currentModifiedTime !== $lastModifiedTime) {
                    $manifestContents = file_get_contents($manifestPath);
                    $manifest = json_decode($manifestContents, true);
                    $lastModifiedTime = $currentModifiedTime;

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return '';
                    }
                }

                $fileKey = "App/Assets/{$path}";

                if (!isset($manifest[$fileKey])) {
                    return '';
                }

                $file = $manifest[$fileKey]['file'];
                return "/public/dist/$file";
            }
        );
    }

    /**
     * CSRF token helper.
     *
     * @return ViewFunction
     */
    public static function csrfToken(): ViewFunction
    {
        return new ViewFunction(
            'csrfToken',
            fn() => CsrfProtection::getToken()
        );
    }

    /**
     * CSRF field helper - returns hidden input with CSRF token.
     *
     * @return ViewFunction
     */
    public static function csrfField(): ViewFunction
    {
        $callable = function() {
            $token = CsrfProtection::getToken();
            $fieldName = CsrfProtection::getTokenFieldName();
            return '<input type="hidden" name="' .
                   htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') .
                   '" value="' .
                   htmlspecialchars($token, ENT_QUOTES, 'UTF-8') .
                   '">';
        };

        return new ViewFunction(
            'csrfField',
            $callable,
            ['is_safe' => ['html']] // Hint for template engines that support it
        );
    }

    /**
     * Get all view helpers.
     *
     * @return array<ViewFunction>
     */
    public static function all(): array
    {
        return [
            self::asset(),
            self::flashMessage(),
            self::routeUrl(),
            self::conditional(),
            self::renderIf(),
            self::csrfToken(),
            self::csrfField(),
        ];
    }
}
