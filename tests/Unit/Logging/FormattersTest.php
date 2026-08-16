<?php

use Lalaz\Logging\Formatters\TextFormatter;
use Lalaz\Logging\Formatters\JsonFormatter;

describe('TextFormatter', function () {
    beforeEach(function () {
        $this->formatter = new TextFormatter();
    });

    it('formats message with level and timestamp', function () {
        $result = $this->formatter->format('INFO', 'Test message', []);

        expect($result)->toMatch('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] INFO: Test message/');
    });

    it('does not show empty context', function () {
        $result = $this->formatter->format('ERROR', 'Error message', []);

        expect($result)->not->toContain('[]');
        expect($result)->not->toContain('{}');
        expect($result)->toContain('ERROR');
        expect($result)->toContain('Error message');
    });

    it('includes context when provided', function () {
        $result = $this->formatter->format('WARNING', 'Warning message', ['code' => 123]);

        expect($result)->toContain('WARNING');
        expect($result)->toContain('Warning message');
        expect($result)->toContain('"code":123');
    });

    it('handles unicode characters in message', function () {
        $result = $this->formatter->format('INFO', 'Mensagem com acentuação: àéîôü', []);

        expect($result)->toContain('Mensagem com acentuação: àéîôü');
        expect($result)->not->toContain('\u');
    });

    it('handles unicode characters in context', function () {
        $result = $this->formatter->format('INFO', 'Message', ['nome' => 'José', 'cidade' => 'São Paulo']);

        expect($result)->toContain('José');
        expect($result)->toContain('São Paulo');
        expect($result)->not->toContain('\u');
    });

    it('handles emoji in messages', function () {
        $result = $this->formatter->format('INFO', 'Success! 🎉', []);

        expect($result)->toContain('🎉');
    });

    it('handles large context arrays', function () {
        $largeContext = [];
        for ($i = 0; $i < 100; $i++) {
            $largeContext["key_$i"] = "value_$i";
        }

        $result = $this->formatter->format('DEBUG', 'Large context', $largeContext);

        expect($result)->toContain('Large context');
        expect($result)->toContain('key_0');
        expect($result)->toContain('key_99');
    });

    it('handles nested arrays in context', function () {
        $context = [
            'user' => [
                'id' => 123,
                'name' => 'John',
                'roles' => ['admin', 'user']
            ]
        ];

        $result = $this->formatter->format('INFO', 'Nested data', $context);

        expect($result)->toContain('Nested data');
        expect($result)->toContain('user');
        expect($result)->toContain('123');
    });

    it('handles special characters in context', function () {
        $context = ['path' => '/tmp/file with spaces.txt', 'query' => 'a=1&b=2'];

        $result = $this->formatter->format('DEBUG', 'Special chars', $context);

        expect($result)->toContain('file with spaces');
        expect($result)->toContain('a=1&b=2');
    });

    it('formats all PSR-3 log levels correctly', function () {
        $levels = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];

        foreach ($levels as $level) {
            $result = $this->formatter->format($level, 'Test', []);
            expect($result)->toContain($level);
        }
    });
});

describe('JsonFormatter', function () {
    beforeEach(function () {
        $this->formatter = new JsonFormatter();
    });

    it('returns valid JSON', function () {
        $result = $this->formatter->format('INFO', 'Test message', []);

        $decoded = json_decode($result, true);
        expect($decoded)->toBeArray();
    });

    it('includes level, message and timestamp', function () {
        $result = $this->formatter->format('ERROR', 'Error message', []);
        $data = json_decode($result, true);

        expect($data['level'])->toBe('ERROR');
        expect($data['message'])->toBe('Error message');
        // Timestamp is in ISO 8601 format (e.g., 2024-01-01T12:00:00+00:00)
        expect($data['timestamp'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    });

    it('includes context when provided', function () {
        $context = ['user_id' => 123, 'action' => 'delete'];
        $result = $this->formatter->format('WARNING', 'Warning', $context);
        $data = json_decode($result, true);

        expect($data['context'])->toBeArray();
        expect($data['context']['user_id'])->toBe(123);
        expect($data['context']['action'])->toBe('delete');
    });

    it('includes empty context array when no context provided', function () {
        $result = $this->formatter->format('INFO', 'Message', []);
        $data = json_decode($result, true);

        // Context is always included, even when empty
        expect(array_key_exists('context', $data))->toBeTrue();
        expect($data['context'])->toBeArray();
        expect($data['context'])->toBeEmpty();
    });

    it('handles unicode without escaping', function () {
        $result = $this->formatter->format('INFO', 'Mensagem com acentuação', ['nome' => 'José']);
        $data = json_decode($result, true);

        // Check decoded values
        expect($data['message'])->toBe('Mensagem com acentuação');
        expect($data['context']['nome'])->toBe('José');

        // Check raw JSON doesn't have escaped unicode
        expect($result)->toContain('Mensagem com acentuação');
        expect($result)->toContain('José');
        expect($result)->not->toContain('\u');
    });

    it('handles emoji characters', function () {
        $result = $this->formatter->format('INFO', 'Success! 🎉', ['emoji' => '🚀']);

        expect($result)->toContain('🎉');
        expect($result)->toContain('🚀');

        $data = json_decode($result, true);
        expect($data['message'])->toBe('Success! 🎉');
        expect($data['context']['emoji'])->toBe('🚀');
    });

    it('handles chinese and japanese characters', function () {
        $context = ['chinese' => '中文', 'japanese' => '日本語'];
        $result = $this->formatter->format('INFO', 'Multi-language', $context);

        $data = json_decode($result, true);
        expect($data['context']['chinese'])->toBe('中文');
        expect($data['context']['japanese'])->toBe('日本語');
    });

    it('handles nested objects in context', function () {
        $context = [
            'user' => [
                'id' => 123,
                'profile' => [
                    'name' => 'John',
                    'email' => 'john@example.com'
                ]
            ]
        ];

        $result = $this->formatter->format('DEBUG', 'Nested', $context);
        $data = json_decode($result, true);

        expect($data['context']['user']['id'])->toBe(123);
        expect($data['context']['user']['profile']['name'])->toBe('John');
    });

    it('handles large context arrays', function () {
        $largeContext = [];
        for ($i = 0; $i < 500; $i++) {
            $largeContext["key_$i"] = "value_$i";
        }

        $result = $this->formatter->format('INFO', 'Large', $largeContext);
        $data = json_decode($result, true);

        expect($data['context'])->toHaveCount(500);
        expect($data['context']['key_0'])->toBe('value_0');
        expect($data['context']['key_499'])->toBe('value_499');
    });

    it('does not escape forward slashes', function () {
        $context = ['url' => 'https://example.com/path/to/resource'];
        $result = $this->formatter->format('INFO', 'URL test', $context);

        expect($result)->toContain('https://example.com/path/to/resource');
        expect($result)->not->toContain('https:\/\/');
    });

    it('throws exception for unserializable data', function () {
        // Create a resource (cannot be JSON encoded)
        $resource = fopen('php://memory', 'r');

        expect(fn() => $this->formatter->format('ERROR', 'Test', ['resource' => $resource]))
            ->toThrow(RuntimeException::class);

        fclose($resource);
    });

    it('handles boolean values correctly', function () {
        $context = ['success' => true, 'failed' => false];
        $result = $this->formatter->format('INFO', 'Booleans', $context);
        $data = json_decode($result, true);

        expect($data['context']['success'])->toBeTrue();
        expect($data['context']['failed'])->toBeFalse();
    });

    it('handles null values correctly', function () {
        $context = ['value' => null];
        $result = $this->formatter->format('INFO', 'Null test', $context);
        $data = json_decode($result, true);

        expect($data['context']['value'])->toBeNull();
    });

    it('handles numeric values correctly', function () {
        $context = [
            'integer' => 123,
            'float' => 123.45,
            'negative' => -789
        ];
        $result = $this->formatter->format('INFO', 'Numbers', $context);
        $data = json_decode($result, true);

        expect($data['context']['integer'])->toBe(123);
        expect($data['context']['float'])->toBe(123.45);
        expect($data['context']['negative'])->toBe(-789);
    });
});
