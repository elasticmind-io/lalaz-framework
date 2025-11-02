<?php declare(strict_types=1);

use Lalaz\Core\Config;
use Lalaz\Logging\Logger;
use Lalaz\Logging\LoggerWriterInterface;
use Lalaz\Logging\Formatters\TextFormatter;
use Lalaz\Logging\Formatters\JsonFormatter;

describe('Logger', function() {
    beforeEach(function() {
        $this->mockWriter = Mockery::mock(LoggerWriterInterface::class);
    });

    afterEach(function() {
        Mockery::close();
    });

    describe('Creation', function() {
        it('creates logger with default text formatter', function() {
            $logger = Logger::create();

            expect($logger)->toBeInstanceOf(Logger::class);
        });

        it('creates logger with custom formatter', function() {
            $formatter = new JsonFormatter();
            $logger = Logger::create($formatter);

            expect($logger)->toBeInstanceOf(Logger::class);
        });
    });

    describe('Writer Management', function() {
        it('allows adding a writer', function() {
            $logger = Logger::create();

            $result = $logger->writeTo($this->mockWriter);

            expect($result)->toBeInstanceOf(Logger::class);
        });

        it('allows method chaining when adding writers', function() {
            $logger = Logger::create();
            $writer2 = Mockery::mock(LoggerWriterInterface::class);

            $result = $logger->writeTo($this->mockWriter)->writeTo($writer2);

            expect($result)->toBeInstanceOf(Logger::class);
        });

        it('can have multiple writers', function() {
            $this->mockWriter->shouldReceive('write')->once();
            $writer2 = Mockery::mock(LoggerWriterInterface::class);
            $writer2->shouldReceive('write')->once();

            $logger = Logger::create()
                ->writeTo($this->mockWriter)
                ->writeTo($writer2);

            $logger->info('Test message');
        });
    });

    describe('Info Logging', function() {
        it('writes info message to writer', function() {
            $this->mockWriter->shouldReceive('write')
                ->once()
                ->with(Mockery::on(function($message) {
                    return str_contains($message, 'INFO') && str_contains($message, 'Test info');
                }));

            $logger = Logger::create()->writeTo($this->mockWriter);
            $logger->info('Test info');
        });

        it('writes info with context', function() {
            $this->mockWriter->shouldReceive('write')
                ->once()
                ->with(Mockery::on(function($message) {
                    return str_contains($message, 'INFO')
                        && str_contains($message, 'User action')
                        && str_contains($message, '"user_id":123');
                }));

            $logger = Logger::create()->writeTo($this->mockWriter);
            $logger->info('User action', ['user_id' => 123]);
        });

        it('writes empty context as empty array', function() {
            $this->mockWriter->shouldReceive('write')
                ->once()
                ->with(Mockery::on(function($message) {
                    return str_contains($message, 'INFO') && str_contains($message, '[]');
                }));

            $logger = Logger::create()->writeTo($this->mockWriter);
            $logger->info('Simple message', []);
        });
    });

    describe('Error Logging', function() {
        it('writes error message to writer', function() {
            $this->mockWriter->shouldReceive('write')
                ->once()
                ->with(Mockery::on(function($message) {
                    return str_contains($message, 'ERROR') && str_contains($message, 'Test error');
                }));

            $logger = Logger::create()->writeTo($this->mockWriter);
            $logger->error('Test error');
        });

        it('writes error with context', function() {
            $this->mockWriter->shouldReceive('write')
                ->once()
                ->with(Mockery::on(function($message) {
                    return str_contains($message, 'ERROR')
                        && str_contains($message, 'Database error')
                        && str_contains($message, '"code":500');
                }));

            $logger = Logger::create()->writeTo($this->mockWriter);
            $logger->error('Database error', ['code' => 500]);
        });
    });

    describe('Debug Logging', function() {
        it('calls debug method without errors', function() {
            // Since Config::isDebug() is a static method that might already be loaded,
            // we test that the debug method exists and can be called
            $logger = Logger::create()->writeTo($this->mockWriter);

            // Debug might or might not write depending on Config::isDebug()
            // We just ensure it doesn't throw an exception
            expect(fn() => $logger->debug('Debug info'))->not->toThrow(Exception::class);
        });

        it('debug method accepts message and context parameters', function() {
            $reflection = new ReflectionMethod(Logger::class, 'debug');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(2);
            expect($params[0]->getName())->toBe('message');
            expect($params[1]->getName())->toBe('context');
        });

        it('debug method returns void', function() {
            $reflection = new ReflectionMethod(Logger::class, 'debug');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });
    });

    describe('Custom Formatter', function() {
        it('uses JSON formatter when specified', function() {
            $this->mockWriter->shouldReceive('write')
                ->once()
                ->with(Mockery::on(function($message) {
                    $decoded = json_decode($message, true);
                    return $decoded !== null
                        && isset($decoded['level'])
                        && $decoded['level'] === 'INFO';
                }));

            $logger = Logger::create(new JsonFormatter())->writeTo($this->mockWriter);
            $logger->info('JSON formatted');
        });

        it('formats with JSON formatter includes all fields', function() {
            $this->mockWriter->shouldReceive('write')
                ->once()
                ->with(Mockery::on(function($message) {
                    $decoded = json_decode($message, true);
                    return isset($decoded['timestamp'])
                        && isset($decoded['level'])
                        && isset($decoded['message'])
                        && isset($decoded['context']);
                }));

            $logger = Logger::create(new JsonFormatter())->writeTo($this->mockWriter);
            $logger->info('Test', ['key' => 'value']);
        });
    });

    describe('Multiple Writers', function() {
        it('writes to all registered writers', function() {
            $this->mockWriter->shouldReceive('write')->once();

            $writer2 = Mockery::mock(LoggerWriterInterface::class);
            $writer2->shouldReceive('write')->once();

            $writer3 = Mockery::mock(LoggerWriterInterface::class);
            $writer3->shouldReceive('write')->once();

            $logger = Logger::create()
                ->writeTo($this->mockWriter)
                ->writeTo($writer2)
                ->writeTo($writer3);

            $logger->info('Broadcast message');
        });
    });

    describe('Edge Cases', function() {
        it('handles empty message', function() {
            $this->mockWriter->shouldReceive('write')->once();

            $logger = Logger::create()->writeTo($this->mockWriter);
            $logger->info('');
        });

        it('handles special characters in message', function() {
            $this->mockWriter->shouldReceive('write')
                ->once()
                ->with(Mockery::on(function($message) {
                    return str_contains($message, '"quotes"') && str_contains($message, '\'apostrophes\'');
                }));

            $logger = Logger::create()->writeTo($this->mockWriter);
            $logger->info('Message with "quotes" and \'apostrophes\'');
        });

        it('handles unicode in message', function() {
            $this->mockWriter->shouldReceive('write')
                ->once()
                ->with(Mockery::on(function($message) {
                    return str_contains($message, '你好');
                }));

            $logger = Logger::create()->writeTo($this->mockWriter);
            $logger->info('Unicode: 你好');
        });

        it('handles complex context data', function() {
            $context = [
                'user' => [
                    'id' => 123,
                    'name' => 'John',
                    'roles' => ['admin', 'editor']
                ],
                'metadata' => [
                    'timestamp' => time(),
                    'ip' => '192.168.1.1'
                ]
            ];

            $this->mockWriter->shouldReceive('write')->once();

            $logger = Logger::create()->writeTo($this->mockWriter);
            $logger->info('Complex data', $context);
        });
    });

    describe('Logger without Writers', function() {
        it('does not throw when logging without writers', function() {
            $logger = Logger::create();

            expect(fn() => $logger->info('No writers'))->not->toThrow(Exception::class);
            expect(fn() => $logger->error('No writers'))->not->toThrow(Exception::class);
        });
    });
});
