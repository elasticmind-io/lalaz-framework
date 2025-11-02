<?php declare(strict_types=1);

use Lalaz\Logging\Formatters\JsonFormatter;

describe('JsonFormatter', function() {
    beforeEach(function() {
        $this->formatter = new JsonFormatter();
    });

    describe('Basic Formatting', function() {
        it('formats message as valid JSON', function() {
            $result = $this->formatter->format('INFO', 'Test message', []);

            $decoded = json_decode($result, true);
            expect($decoded)->toBeArray();
            expect(json_last_error())->toBe(JSON_ERROR_NONE);
        });

        it('includes all required fields', function() {
            $result = $this->formatter->format('INFO', 'Test message', []);
            $decoded = json_decode($result, true);

            expect($decoded)->toHaveKey('timestamp');
            expect($decoded)->toHaveKey('level');
            expect($decoded)->toHaveKey('message');
            expect($decoded)->toHaveKey('context');
        });

        it('formats timestamp in ISO 8601 format', function() {
            $result = $this->formatter->format('INFO', 'Test', []);
            $decoded = json_decode($result, true);

            // ISO 8601 format: 2024-11-01T12:34:56+00:00
            expect($decoded['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
        });

        it('preserves original log level case', function() {
            $result = $this->formatter->format('INFO', 'Test', []);
            $decoded = json_decode($result, true);

            expect($decoded['level'])->toBe('INFO');
        });
    });

    describe('Context Formatting', function() {
        it('formats empty context as empty object', function() {
            $result = $this->formatter->format('INFO', 'Test', []);
            $decoded = json_decode($result, true);

            expect($decoded['context'])->toBe([]);
        });

        it('formats simple context', function() {
            $context = ['user_id' => 123];
            $result = $this->formatter->format('INFO', 'User action', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context']['user_id'])->toBe(123);
        });

        it('formats complex nested context', function() {
            $context = [
                'user' => [
                    'id' => 456,
                    'name' => 'John Doe',
                    'roles' => ['admin', 'editor']
                ]
            ];
            $result = $this->formatter->format('INFO', 'Complex data', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context']['user']['id'])->toBe(456);
            expect($decoded['context']['user']['name'])->toBe('John Doe');
            expect($decoded['context']['user']['roles'])->toBe(['admin', 'editor']);
        });

        it('formats multiple context fields', function() {
            $context = [
                'user_id' => 789,
                'action' => 'login',
                'ip' => '192.168.1.1',
                'timestamp' => 1234567890
            ];
            $result = $this->formatter->format('INFO', 'User logged in', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context'])->toBe($context);
        });
    });

    describe('Log Levels', function() {
        it('formats INFO level', function() {
            $result = $this->formatter->format('INFO', 'Info message', []);
            $decoded = json_decode($result, true);

            expect($decoded['level'])->toBe('INFO');
        });

        it('formats ERROR level', function() {
            $result = $this->formatter->format('ERROR', 'Error message', []);
            $decoded = json_decode($result, true);

            expect($decoded['level'])->toBe('ERROR');
        });

        it('formats DEBUG level', function() {
            $result = $this->formatter->format('DEBUG', 'Debug message', []);
            $decoded = json_decode($result, true);

            expect($decoded['level'])->toBe('DEBUG');
        });

        it('formats custom levels', function() {
            $result = $this->formatter->format('CUSTOM', 'Custom message', []);
            $decoded = json_decode($result, true);

            expect($decoded['level'])->toBe('CUSTOM');
        });
    });

    describe('Data Types', function() {
        it('handles string values', function() {
            $context = ['message' => 'test string'];
            $result = $this->formatter->format('INFO', 'Test', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context']['message'])->toBe('test string');
        });

        it('handles integer values', function() {
            $context = ['count' => 42];
            $result = $this->formatter->format('INFO', 'Test', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context']['count'])->toBe(42);
        });

        it('handles float values', function() {
            $context = ['price' => 19.99];
            $result = $this->formatter->format('INFO', 'Test', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context']['price'])->toBe(19.99);
        });

        it('handles boolean values', function() {
            $context = ['active' => true, 'deleted' => false];
            $result = $this->formatter->format('INFO', 'Test', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context']['active'])->toBe(true);
            expect($decoded['context']['deleted'])->toBe(false);
        });

        it('handles null values', function() {
            $context = ['value' => null];
            $result = $this->formatter->format('INFO', 'Test', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context']['value'])->toBeNull();
        });

        it('handles array values', function() {
            $context = ['items' => [1, 2, 3, 4, 5]];
            $result = $this->formatter->format('INFO', 'Test', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context']['items'])->toBe([1, 2, 3, 4, 5]);
        });
    });

    describe('Edge Cases', function() {
        it('handles empty message', function() {
            $result = $this->formatter->format('INFO', '', []);
            $decoded = json_decode($result, true);

            expect($decoded['message'])->toBe('');
        });

        it('handles special characters in message', function() {
            $message = 'Message with "quotes" and \'apostrophes\'';
            $result = $this->formatter->format('INFO', $message, []);
            $decoded = json_decode($result, true);

            expect($decoded['message'])->toBe($message);
        });

        it('handles unicode characters', function() {
            $message = 'Unicode: 你好 мир שלום 🚀';
            $result = $this->formatter->format('INFO', $message, []);
            $decoded = json_decode($result, true);

            expect($decoded['message'])->toBe($message);
        });

        it('handles newlines in message', function() {
            $message = "Line 1\nLine 2\nLine 3";
            $result = $this->formatter->format('INFO', $message, []);
            $decoded = json_decode($result, true);

            expect($decoded['message'])->toContain("\n");
        });

        it('handles special characters in context', function() {
            $context = ['path' => '/usr/local/bin', 'url' => 'https://example.com?param=value&other=123'];
            $result = $this->formatter->format('INFO', 'Test', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context'])->toBe($context);
        });
    });

    describe('JSON Structure', function() {
        it('produces parseable JSON', function() {
            $result = $this->formatter->format('INFO', 'Test', ['key' => 'value']);

            expect(fn() => json_decode($result, true))->not->toThrow(Exception::class);
        });

        it('maintains data integrity through encode/decode', function() {
            $context = [
                'string' => 'test',
                'number' => 123,
                'float' => 45.67,
                'bool' => true,
                'array' => [1, 2, 3],
                'nested' => ['key' => 'value']
            ];

            $result = $this->formatter->format('INFO', 'Test message', $context);
            $decoded = json_decode($result, true);

            expect($decoded['context'])->toBe($context);
            expect($decoded['message'])->toBe('Test message');
        });
    });
});
