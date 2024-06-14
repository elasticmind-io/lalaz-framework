<?php declare(strict_types=1);

namespace Lalaz;

const FLASH = 'FLASH_MESSAGES';

const FLASH_ERROR = 'error';
const FLASH_WARNING = 'warning';
const FLASH_INFO = 'info';
const FLASH_SUCCESS = 'success';

trait FlashMessage 
{
    function createFlashMessage(string $name, string $message, string $type): void
    {
        if (isset($_SESSION[FLASH][$name])) {
            unset($_SESSION[FLASH][$name]);
        }

        $_SESSION[FLASH][$name] = ['message' => $message, 'type' => $type];
    }

    function showFlashMessage(string $name): string
    {
        if (!isset($_SESSION[FLASH][$name])) {
            return '';
        }

        $flash_message = $_SESSION[FLASH][$name];

        unset($_SESSION[FLASH][$name]);

        return $flash_message;
    }
}