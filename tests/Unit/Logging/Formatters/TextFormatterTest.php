<?php declare(strict_types=1);

use Lalaz\Logging\Formatters\TextFormatter;

describe('TextFormatter', function() {
    beforeEach(function() {
        $this->formatter = new TextFormatter();
    });

    describe('Basic Formatting', function() {
        it('formats message with level and timestamp', function() {
            $result = $this->formatter->format('INFO', 'Test message', []);

            expect($result)->toContain('INFO');
            expect($result)->toContain('Test message');
            expect($result)->toMatch('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/');
        });

        it('converts level to uppercase', function() {
            $result = $this->formatter->format('info', 'Test message', []);

            expect($result)->toContain('INFO');
            expect($result)->not->toContain('info');
        });

        it('formats without context', function() {
            $result = $this->formatter->format('ERROR', 'Error occurred', []);

            expect($result)->toContain('ERROR');
            expect($result)->toContain('Error occurred');
            expect($result)->toContain('[]');
        });

        it('formats with empty context as empty array', function() {
            $result = $this->formatter->format('DEBUG', 'Debug info', []);

            expect($result)->toContain('[]');
        });
    });

    describe('Context Formatting', function() {
        it('formats message with simple context', function() {
            $context = ['user_id' => 123];
            $result = $this->formatter->format('INFO', 'User action', $context);

            expect($result)->toContain('User action');
            expect($result)->toContain('"user_id":123');
        });

        it('formats message with complex context', function() {
            $context = [
                'user_id' => 456,
                'action' => 'login',
                'ip' => '192.168.1.1'
            ];
            $result = $this->formatter->format('INFO', 'User logged in', $context);

            expect($result)->toContain('"user_id":456');
            expect($result)->toContain('"action":"login"');
            expect($result)->toContain('"ip":"192.168.1.1"');
        });

        it('formats message with nested context', function() {
            $context = [
                'user' => [
                    'id' => 789,
                    'name' => 'John Doe'
                ]
            ];
            $result = $this->formatter->format('INFO', 'Complex data', $context);

            expect($result)->toContain('"id":789');
            expect($result)->toContain('"name":"John Doe"');
        });

        it('formats message with array context', function() {
            $context = ['errors' => ['field1', 'field2', 'field3']];
            $result = $this->formatter->format('ERROR', 'Validation failed', $context);

            expect($result)->toContain('Validation failed');
            expect($result)->toContain('"errors":["field1","field2","field3"]');
        });
    });

    describe('Log Levels', function() {
        it('formats INFO level', function() {
            $result = $this->formatter->format('INFO', 'Information message', []);

            expect($result)->toContain('INFO');
        });

        it('formats ERROR level', function() {
            $result = $this->formatter->format('ERROR', 'Error message', []);

            expect($result)->toContain('ERROR');
        });

        it('formats DEBUG level', function() {
            $result = $this->formatter->format('DEBUG', 'Debug message', []);

            expect($result)->toContain('DEBUG');
        });

        it('formats WARNING level', function() {
            $result = $this->formatter->format('WARNING', 'Warning message', []);

            expect($result)->toContain('WARNING');
        });

        it('formats CRITICAL level', function() {
            $result = $this->formatter->format('CRITICAL', 'Critical message', []);

            expect($result)->toContain('CRITICAL');
        });
    });

    describe('Edge Cases', function() {
        it('handles empty message', function() {
            $result = $this->formatter->format('INFO', '', []);

            expect($result)->toContain('INFO');
            expect($result)->toContain('[]');
        });

        it('handles special characters in message', function() {
            $message = 'Message with "quotes" and \'apostrophes\'';
            $result = $this->formatter->format('INFO', $message, []);

            expect($result)->toContain($message);
        });

        it('handles unicode characters', function() {
            $message = 'Unicode: 你好 мир שלום';
            $result = $this->formatter->format('INFO', $message, []);

            expect($result)->toContain($message);
        });

        it('handles newlines in message', function() {
            $message = "Line 1\nLine 2\nLine 3";
            $result = $this->formatter->format('INFO', $message, []);

            expect($result)->toContain($message);
        });

        it('handles null values in context', function() {
            $context = ['value' => null];
            $result = $this->formatter->format('INFO', 'Null test', $context);

            expect($result)->toContain('"value":null');
        });

        it('handles boolean values in context', function() {
            $context = ['success' => true, 'failed' => false];
            $result = $this->formatter->format('INFO', 'Boolean test', $context);

            expect($result)->toContain('"success":true');
            expect($result)->toContain('"failed":false');
        });
    });

    describe('Format Structure', function() {
        it('follows expected format pattern', function() {
            $result = $this->formatter->format('INFO', 'Test', ['key' => 'value']);

            // Expected format: [YYYY-MM-DD HH:MM:SS] LEVEL: message context
            expect($result)->toMatch('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] [A-Z]+: .+ \{.+\}$/');
        });

        it('includes all components in correct order', function() {
            $result = $this->formatter->format('ERROR', 'Something failed', ['reason' => 'timeout']);

            // Should have: timestamp, level, message, context in that order
            $timestampPos = strpos($result, '[');
            $levelPos = strpos($result, 'ERROR');
            $messagePos = strpos($result, 'Something failed');
            $contextPos = strpos($result, '"reason"');

            expect($timestampPos)->toBeLessThan($levelPos);
            expect($levelPos)->toBeLessThan($messagePos);
            expect($messagePos)->toBeLessThan($contextPos);
        });
    });
});
