<?php

use Lalaz\Logging\Logger;
use Lalaz\Logging\LogToConsole;
use Lalaz\Logging\Formatters\TextFormatter;
use Lalaz\Logging\Formatters\JsonFormatter;
use Psr\Log\LoggerInterface;

describe('Logger', function () {
    it('implements PSR-3 LoggerInterface', function () {
        $logger = Logger::create();

        expect($logger)->toBeInstanceOf(LoggerInterface::class);
    });

    it('supports all PSR-3 log levels', function () {
        $logger = Logger::create();

        expect(method_exists($logger, 'emergency'))->toBeTrue();
        expect(method_exists($logger, 'alert'))->toBeTrue();
        expect(method_exists($logger, 'critical'))->toBeTrue();
        expect(method_exists($logger, 'error'))->toBeTrue();
        expect(method_exists($logger, 'warning'))->toBeTrue();
        expect(method_exists($logger, 'notice'))->toBeTrue();
        expect(method_exists($logger, 'info'))->toBeTrue();
        expect(method_exists($logger, 'debug'))->toBeTrue();
        expect(method_exists($logger, 'log'))->toBeTrue();
    });    it('can create logger with default text formatter', function () {
        $logger = Logger::create();

        expect($logger)->toBeInstanceOf(Logger::class);
    });

    it('can create logger with custom formatter', function () {
        $formatter = new JsonFormatter();
        $logger = Logger::create($formatter);

        expect($logger)->toBeInstanceOf(Logger::class);
    });

    it('supports fluent interface for adding writers', function () {
        $logger = Logger::create();
        $result = $logger->writeTo(new LogToConsole());

        expect($result)->toBe($logger);
    });

    it('interpolates context values into message placeholders', function () {
        $output = '';
        $writer = new class($output) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}

            public function write(string $message): void {
                $this->output = $message;
            }
        };

        $logger = Logger::create()->writeTo($writer);
        $logger->info('User {username} logged in from {ip}', [
            'username' => 'john',
            'ip' => '192.168.1.1'
        ]);

        expect($output)->toContain('User john logged in from 192.168.1.1');
    });

    it('does not show empty context in text formatter', function () {
        $output = '';
        $writer = new class($output) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}

            public function write(string $message): void {
                $this->output = $message;
            }
        };

        $logger = Logger::create()->writeTo($writer);
        $logger->info('Simple message');

        expect($output)->not->toContain('[]');
        expect($output)->not->toContain('{}');
        expect($output)->toContain('Simple message');
    });

    it('shows context when provided in text formatter', function () {
        $output = '';
        $writer = new class($output) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}

            public function write(string $message): void {
                $this->output = $message;
            }
        };

        $logger = Logger::create()->writeTo($writer);
        $logger->info('Message with context', ['user_id' => 123]);

        expect($output)->toContain('Message with context');
        expect($output)->toContain('user_id');
        expect($output)->toContain('123');
    });

    it('encodes json with unicode support', function () {
        $output = '';
        $writer = new class($output) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}

            public function write(string $message): void {
                $this->output = $message;
            }
        };

        $logger = Logger::create(new JsonFormatter())->writeTo($writer);
        $logger->info('Mensagem com acentuação', ['nome' => 'José']);

        $data = json_decode($output, true);

        expect($data)->toBeArray();
        expect($data['message'])->toBe('Mensagem com acentuação');
        expect($data['context']['nome'])->toBe('José');
        expect($output)->toContain('Mensagem com acentuação'); // Not escaped
        expect($output)->toContain('José'); // Not escaped
    });

    it('throws exception if json encoding fails', function () {
        $formatter = new JsonFormatter();

        // Create a resource that cannot be JSON encoded
        $resource = fopen('php://memory', 'r');

        expect(fn() => $formatter->format('ERROR', 'Test', ['resource' => $resource]))
            ->toThrow(RuntimeException::class);

        fclose($resource);
    });

    // Advanced Logger tests

    it('filters logs below minimum level', function () {
        $output = '';
        $writer = new class($output) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}

            public function write(string $message): void {
                $this->output .= $message . '|';
            }
        };

        $logger = Logger::create()->writeTo($writer);
        $logger->setMinLevel('ERROR');

        $logger->debug('Debug message');   // Should be filtered
        $logger->info('Info message');     // Should be filtered
        $logger->warning('Warning');        // Should be filtered
        $logger->error('Error message');   // Should log
        $logger->critical('Critical');     // Should log

        expect($output)->not->toContain('Debug message');
        expect($output)->not->toContain('Info message');
        expect($output)->not->toContain('Warning');
        expect($output)->toContain('Error message');
        expect($output)->toContain('Critical');
    });

    it('allows logging at exactly the minimum level', function () {
        $output = '';
        $writer = new class($output) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}

            public function write(string $message): void {
                $this->output = $message;
            }
        };

        $logger = Logger::create()->writeTo($writer);
        $logger->setMinLevel('WARNING');
        $logger->warning('Warning at min level');

        expect($output)->toContain('Warning at min level');
    });

    it('can get current minimum level', function () {
        $logger = Logger::create();

        expect($logger->getMinLevel())->toBe('DEBUG'); // default

        $logger->setMinLevel('ERROR');
        expect($logger->getMinLevel())->toBe('ERROR');

        $logger->setMinLevel('INFO');
        expect($logger->getMinLevel())->toBe('INFO');
    });

    it('can switch formatter at runtime', function () {
        $output = '';
        $writer = new class($output) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}

            public function write(string $message): void {
                $this->output = $message;
            }
        };

        $logger = Logger::create()->writeTo($writer);

        // Default text formatter
        $logger->info('First message');
        $firstOutput = $output;

        // Switch to JSON formatter
        $logger->setFormatter(new JsonFormatter());
        $logger->info('Second message');
        $secondOutput = $output;

        // First should be text format
        expect($firstOutput)->toContain('INFO');
        expect($firstOutput)->toContain('First message');

        // Second should be JSON
        $data = json_decode($secondOutput, true);
        expect($data)->toBeArray();
        expect($data['level'])->toBe('INFO');
        expect($data['message'])->toBe('Second message');
    });

    it('returns current formatter via getter', function () {
        $textFormatter = new TextFormatter();
        $logger = Logger::create($textFormatter);

        expect($logger->getFormatter())->toBe($textFormatter);

        $jsonFormatter = new JsonFormatter();
        $logger->setFormatter($jsonFormatter);

        expect($logger->getFormatter())->toBe($jsonFormatter);
    });

    it('can write to multiple writers simultaneously', function () {
        $output1 = '';
        $output2 = '';
        $output3 = '';

        $writer1 = new class($output1) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}
            public function write(string $message): void {
                $this->output = $message;
            }
        };

        $writer2 = new class($output2) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}
            public function write(string $message): void {
                $this->output = $message;
            }
        };

        $writer3 = new class($output3) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}
            public function write(string $message): void {
                $this->output = $message;
            }
        };

        $logger = Logger::create()
            ->writeTo($writer1)
            ->writeTo($writer2)
            ->writeTo($writer3);

        $logger->info('Multi-writer test');

        expect($output1)->toContain('Multi-writer test');
        expect($output2)->toContain('Multi-writer test');
        expect($output3)->toContain('Multi-writer test');
    });

    it('minimum level filtering applies to all writers', function () {
        $output1 = '';
        $output2 = '';

        $writer1 = new class($output1) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}
            public function write(string $message): void {
                $this->output .= $message . '|';
            }
        };

        $writer2 = new class($output2) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}
            public function write(string $message): void {
                $this->output .= $message . '|';
            }
        };

        $logger = Logger::create()
            ->writeTo($writer1)
            ->writeTo($writer2);

        $logger->setMinLevel('ERROR');

        $logger->info('Info message');    // Filtered for both
        $logger->error('Error message');  // Written to both

        expect($output1)->not->toContain('Info message');
        expect($output2)->not->toContain('Info message');
        expect($output1)->toContain('Error message');
        expect($output2)->toContain('Error message');
    });

    it('handles case-insensitive log level names', function () {
        $output = '';
        $writer = new class($output) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}
            public function write(string $message): void {
                $this->output = $message;
            }
        };

        $logger = Logger::create()->writeTo($writer);

        $logger->setMinLevel('error');  // lowercase
        $logger->log('INFO', 'Info message');  // Should be filtered
        $logger->log('ERROR', 'Error message'); // Should log

        expect($output)->not->toContain('Info message');
        expect($output)->toContain('Error message');
    });

    it('default minimum level is DEBUG allowing all messages', function () {
        $output = '';
        $writer = new class($output) implements \Lalaz\Logging\Contracts\LoggerWriterInterface {
            public function __construct(private string &$output) {}
            public function write(string $message): void {
                $this->output .= $message . '|';
            }
        };

        $logger = Logger::create()->writeTo($writer);

        // Default should be DEBUG (allow all)
        $logger->debug('Debug');
        $logger->info('Info');
        $logger->warning('Warning');
        $logger->error('Error');
        $logger->emergency('Emergency');

        expect($output)->toContain('Debug');
        expect($output)->toContain('Info');
        expect($output)->toContain('Warning');
        expect($output)->toContain('Error');
        expect($output)->toContain('Emergency');
    });
});

