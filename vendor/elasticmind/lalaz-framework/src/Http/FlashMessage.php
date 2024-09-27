<?php declare(strict_types=1);

namespace Lalaz\Http;

/**
 * Trait FlashMessage
 *
 * This trait provides functionality to handle flash messages in a web application.
 * Flash messages are temporary messages stored in the session and can be displayed
 * on the next request, such as success, error, or informational messages.
 *
 * @author  Elasticmind
 * @namespace Lalaz\Http
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
trait FlashMessage
{
    /** @var string FLASH The session key where flash messages are stored */
    public const FLASH = 'FLASH_MESSAGES';

    /** @var string FLASH_ERROR The key for error messages */
    public const FLASH_ERROR = 'error';

    /** @var string FLASH_WARNING The key for warning messages */
    public const FLASH_WARNING = 'warning';

    /** @var string FLASH_INFO The key for informational messages */
    public const FLASH_INFO = 'info';

    /** @var string FLASH_SUCCESS The key for success messages */
    public const FLASH_SUCCESS = 'success';

    /**
     * Creates a flash message and stores it in the session.
     *
     * @param string $name The name of the flash message.
     * @param string $message The content of the flash message.
     * @param string $type The type of flash message (e.g., success, error, warning, info).
     *
     * @return void
     */
    public function createFlashMessage(string $name, string $message, string $type): void
    {
        if (isset($_SESSION[self::FLASH][$name])) {
            unset($_SESSION[self::FLASH][$name]);
        }

        $_SESSION[self::FLASH][$name] = ['message' => $message, 'type' => $type];
    }

    /**
     * Displays a flash message by name and removes it from the session.
     *
     * @param string $name The name of the flash message to display.
     *
     * @return mixed The flash message array with 'message' and 'type', or false if not found.
     */
    public static function showFlashMessage(string $name): mixed
    {
        if (!isset($_SESSION[self::FLASH][$name])) {
            return false;
        }

        $flash_message = $_SESSION[self::FLASH][$name];

        unset($_SESSION[self::FLASH][$name]);

        return $flash_message;
    }
}
