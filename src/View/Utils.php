<?php declare(strict_types=1);

namespace Lalaz\View;

use Lalaz\Lalaz;
use Lalaz\Http\FlashMessage;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\TwigFunction;

/**
 * Class Utils
 *
 * This class provides utility functions for use with the Twig templating engine.
 * It includes functions for handling flash messages, generating route URLs, and
 * conditional rendering logic within Twig templates.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Utils
{
    use FlashMessage;

    /**
     * Registers a Twig function for displaying flash messages in templates.
     *
     * @return \Twig\TwigFunction A Twig function 'showFlashMessage' that displays a flash message.
     */
    public static function flashMessage(): TwigFunction
    {
        return new TwigFunction('showFlashMessage', function(string $name) {
            return self::showFlashMessage($name);
        });
    }

    /**
     * Registers a Twig function for generating route URLs in templates.
     *
     * @return \Twig\TwigFunction A Twig function 'routeUrl' that returns the route URL.
     */
    public static function routeUrl(): TwigFunction
    {
        return new TwigFunction('routeUrl', function(string $action) {
            return $action;
        });
    }

    /**
     * Registers a Twig function for conditional logic in templates.
     *
     * @return \Twig\TwigFunction A Twig function 'conditional' that returns a value based on a condition.
     */
    public static function conditional(): TwigFunction
    {
        return new TwigFunction('conditional', function(bool $condition, string $left, string $right) {
            return $condition ? $left : $right;
        });
    }

    /**
     * Registers a Twig function for conditional rendering in templates.
     *
     * @return \Twig\TwigFunction A Twig function 'renderIf' that renders a value if a condition is true.
     */
    public static function renderIf(): TwigFunction
    {
        return new TwigFunction('renderIf', function(string $left, bool $condition) {
            return $condition ? $left : null;
        });
    }

    /**
     * Returns a Twig function for resolving asset paths using a manifest file.
     *
     * This function generates a new Twig function called "asset", which takes an asset path,
     * looks it up in the manifest.json file, and returns the resolved path in the public/dist directory.
     * The manifest will be reloaded whenever the manifest.json file is modified.
     *
     * @return \Twig\TwigFunction The Twig function to be used in templates.
     */
    public static function asset(): TwigFunction
    {
        return new TwigFunction('asset', function (string $path) {
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

            if (!isset($manifest[$path])) {
                return '';
            }

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
