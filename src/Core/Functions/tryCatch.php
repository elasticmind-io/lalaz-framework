<?php declare(strict_types=1);

use Lalaz\Logging\Log;

/**
 * Executes a try-catch block with customizable exception handlers.
 *
 * This function allows you to encapsulate try-catch logic with optional handlers
 * for specific exception types, a default handler for uncaught exceptions,
 * and the ability to log or rethrow exceptions.
 *
 * @param callable $tryBlock The main block of code to execute within the try block.
 * @param array<class-string, callable> $catchHandlers An associative array mapping exception class names to their handlers.
 * @param callable|null $defaultHandler A default handler to execute if no specific handler matches the thrown exception.
 * @param bool $logExceptions Whether to log exceptions that are not handled by a specific handler.
 * @param bool $rethrow Whether to rethrow the exception after handling.
 *
 * @return array{mixed, Throwable|null} Returns an array containing the result of the try block or handler, and the exception caught (if any).
 *
 * @throws Throwable Rethrows the exception if $rethrow is set to true.
 */
function tryCatch(
    callable $tryBlock,
    array $catchHandlers = [],
    ?callable $defaultHandler = null,
    bool $logExceptions = true,
    bool $rethrow = false
): array {
    try {
        $result = $tryBlock();
        return [$result, null];
    } catch (Throwable $e) {
        foreach ($catchHandlers as $exceptionClass => $handler) {
            if ($e instanceof $exceptionClass) {
                $result = $handler($e);
                if ($rethrow) throw $e;
                return [$result, $e];
            }
        }

        if ($defaultHandler) {
            $result = $defaultHandler($e);
            if ($rethrow) throw $e;
            return [$result, $e];
        }

        if ($logExceptions) {
            Log::error('Exception caught in tryCatch: ' . $e->getMessage(), ['exception' => $e]);
        }

        if ($rethrow) throw $e;

        return [null, $e];
    }
}
