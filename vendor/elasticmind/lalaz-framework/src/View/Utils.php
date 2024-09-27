<?php declare(strict_types=1);

namespace Lalaz\View;

use Lalaz\Lalaz;
use Lalaz\Http\FlashMessage;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

/**
 * Class Utils
 *
 * This class provides utility functions for use with the Twig templating engine.
 * It includes functions for handling flash messages, generating route URLs, and
 * conditional rendering logic within Twig templates.
 *
 * @author  Elasticmind <ola@elasticmind.io>
 * @namespace Lalaz\View
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
class Utils
{
    use FlashMessage;

    /**
     * Registers a Twig function for displaying flash messages in templates.
     *
     * @return \Twig\TwigFunction A Twig function 'showFlashMessage' that displays a flash message.
     */
    public static function flashMessage(): \Twig\TwigFunction
    {
        return new \Twig\TwigFunction('showFlashMessage', function(string $name) {
            return self::showFlashMessage($name);
        });
    }

    /**
     * Registers a Twig function for generating route URLs in templates.
     *
     * @return \Twig\TwigFunction A Twig function 'routeUrl' that returns the route URL.
     */
    public static function routeUrl(): \Twig\TwigFunction
    {
        return new \Twig\TwigFunction('routeUrl', function(string $action) {
            return $action;
        });
    }

    /**
     * Registers a Twig function for conditional logic in templates.
     *
     * @return \Twig\TwigFunction A Twig function 'conditional' that returns a value based on a condition.
     */
    public static function conditional(): \Twig\TwigFunction
    {
        return new \Twig\TwigFunction('conditional', function(bool $condition, string $left, string $right) {
            return $condition ? $left : $right;
        });
    }

    /**
     * Registers a Twig function for conditional rendering in templates.
     *
     * @return \Twig\TwigFunction A Twig function 'renderIf' that renders a value if a condition is true.
     */
    public static function renderIf(): \Twig\TwigFunction
    {
        return new \Twig\TwigFunction('renderIf', function(string $left, bool $condition) {
            return $condition ? $left : null;
        });
    }

    public static function asset(): \Twig\TwigFunction
    {
        return new \Twig\TwigFunction('asset', function(string $path) {
            $manifestPath = './public/dist/.vite/manifest.json';
            $manifestContents = file_get_contents($manifestPath);
            $manifest = json_decode($manifestContents, true);
            $file = $manifest[$path]['file'];
            return "/public/dist/$file";
        });
    }


    /**
     * Registers all Twig functions provided by this class.
     *
     * @return array An array of Twig functions including flashMessage, routeUrl, conditional, and renderIf.
     */
    public static function all(): array
    {
        return array(
            static::asset(),
            static::flashMessage(),
            static::routeUrl(),
            static::conditional(),
            static::renderIf()
        );
    }
}
