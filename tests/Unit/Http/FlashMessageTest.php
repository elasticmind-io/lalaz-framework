<?php declare(strict_types=1);

use Lalaz\Http\FlashMessage;

// Create a test class that uses the FlashMessage trait
class FlashMessageTestClass
{
    use FlashMessage;
}

describe('FlashMessage', function() {
    beforeEach(function() {
        // Clean up session
        $_SESSION = [];
        $this->flashHandler = new FlashMessageTestClass();
    });

    afterEach(function() {
        $_SESSION = [];
    });

    describe('Constants', function() {
        it('defines FLASH constant', function() {
            expect(FlashMessageTestClass::FLASH)->toBe('FLASH_MESSAGES');
        });

        it('defines message type constants', function() {
            expect(FlashMessageTestClass::FLASH_ERROR)->toBe('error');
            expect(FlashMessageTestClass::FLASH_WARNING)->toBe('warning');
            expect(FlashMessageTestClass::FLASH_INFO)->toBe('info');
            expect(FlashMessageTestClass::FLASH_SUCCESS)->toBe('success');
        });
    });

    describe('Creating Flash Messages', function() {
        it('creates flash message with name, message and type', function() {
            $this->flashHandler->createFlashMessage('notification', 'User created', 'success');

            expect($_SESSION['FLASH_MESSAGES'])->toHaveKey('notification');
            expect($_SESSION['FLASH_MESSAGES']['notification']['message'])->toBe('User created');
            expect($_SESSION['FLASH_MESSAGES']['notification']['type'])->toBe('success');
        });

        it('creates error flash message', function() {
            $this->flashHandler->createFlashMessage('error', 'Invalid credentials', FlashMessageTestClass::FLASH_ERROR);

            expect($_SESSION['FLASH_MESSAGES']['error']['type'])->toBe('error');
        });

        it('creates warning flash message', function() {
            $this->flashHandler->createFlashMessage('warn', 'Session expires soon', FlashMessageTestClass::FLASH_WARNING);

            expect($_SESSION['FLASH_MESSAGES']['warn']['type'])->toBe('warning');
        });

        it('creates info flash message', function() {
            $this->flashHandler->createFlashMessage('info', 'New features available', FlashMessageTestClass::FLASH_INFO);

            expect($_SESSION['FLASH_MESSAGES']['info']['type'])->toBe('info');
        });

        it('creates success flash message', function() {
            $this->flashHandler->createFlashMessage('success', 'Operation completed', FlashMessageTestClass::FLASH_SUCCESS);

            expect($_SESSION['FLASH_MESSAGES']['success']['type'])->toBe('success');
        });

        it('replaces existing flash message with same name', function() {
            $this->flashHandler->createFlashMessage('msg', 'First message', 'info');
            $this->flashHandler->createFlashMessage('msg', 'Second message', 'success');

            expect($_SESSION['FLASH_MESSAGES']['msg']['message'])->toBe('Second message');
            expect($_SESSION['FLASH_MESSAGES']['msg']['type'])->toBe('success');
        });
    });

    describe('Showing Flash Messages', function() {
        it('returns flash message and removes it from session', function() {
            $this->flashHandler->createFlashMessage('temp', 'Temporary message', 'info');

            $result = FlashMessageTestClass::showFlashMessage('temp');

            expect($result)->toBe(['message' => 'Temporary message', 'type' => 'info']);
            expect($_SESSION['FLASH_MESSAGES'])->not->toHaveKey('temp');
        });

        it('returns false for non-existent flash message', function() {
            $result = FlashMessageTestClass::showFlashMessage('nonexistent');

            expect($result)->toBe(false);
        });

        it('only removes shown message, keeps others', function() {
            $this->flashHandler->createFlashMessage('msg1', 'Message 1', 'info');
            $this->flashHandler->createFlashMessage('msg2', 'Message 2', 'success');

            FlashMessageTestClass::showFlashMessage('msg1');

            expect($_SESSION['FLASH_MESSAGES'])->not->toHaveKey('msg1');
            expect($_SESSION['FLASH_MESSAGES'])->toHaveKey('msg2');
        });
    });

    describe('Edge Cases', function() {
        it('handles empty message content', function() {
            $this->flashHandler->createFlashMessage('empty', '', 'info');

            expect($_SESSION['FLASH_MESSAGES']['empty']['message'])->toBe('');
        });

        it('handles empty message name', function() {
            $this->flashHandler->createFlashMessage('', 'No name message', 'info');

            expect($_SESSION['FLASH_MESSAGES']['']['message'])->toBe('No name message');
        });

        it('handles special characters in message', function() {
            $message = 'Special chars: <script>alert("xss")</script> & © ™';
            $this->flashHandler->createFlashMessage('special', $message, 'info');

            expect($_SESSION['FLASH_MESSAGES']['special']['message'])->toBe($message);
        });

        it('handles unicode characters in message', function() {
            $message = 'Unicode: 你好 مرحبا שלום';
            $this->flashHandler->createFlashMessage('unicode', $message, 'info');

            expect($_SESSION['FLASH_MESSAGES']['unicode']['message'])->toBe($message);
        });

        it('handles multiple flash messages', function() {
            for ($i = 1; $i <= 5; $i++) {
                $this->flashHandler->createFlashMessage("msg{$i}", "Message {$i}", 'info');
            }

            expect(count($_SESSION['FLASH_MESSAGES']))->toBe(5);
        });

        it('shows message only once', function() {
            $this->flashHandler->createFlashMessage('once', 'Show once', 'info');

            $first = FlashMessageTestClass::showFlashMessage('once');
            $second = FlashMessageTestClass::showFlashMessage('once');

            expect($first)->toBe(['message' => 'Show once', 'type' => 'info']);
            expect($second)->toBe(false);
        });
    });
});
