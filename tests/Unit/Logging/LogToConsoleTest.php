<?php declare(strict_types=1);

use Lalaz\Logging\LogToConsole;
use Lalaz\Logging\LoggerWriterInterface;

describe('LogToConsole', function() {
    beforeEach(function() {
        $this->writer = new LogToConsole();
    });

    describe('Implementation', function() {
        it('implements LoggerWriterInterface', function() {
            expect($this->writer)->toBeInstanceOf(LoggerWriterInterface::class);
        });

        it('is a final class', function() {
            $reflection = new ReflectionClass(LogToConsole::class);
            expect($reflection->isFinal())->toBeTrue();
        });

        it('has write method', function() {
            expect(method_exists($this->writer, 'write'))->toBeTrue();
        });
    });

    describe('Write Method Signature', function() {
        it('write method accepts string parameter', function() {
            $reflection = new ReflectionMethod(LogToConsole::class, 'write');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(1);
            expect($params[0]->getName())->toBe('message');
            expect($params[0]->getType()->getName())->toBe('string');
        });

        it('write method returns void', function() {
            $reflection = new ReflectionMethod(LogToConsole::class, 'write');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });

        it('write method is public', function() {
            $reflection = new ReflectionMethod(LogToConsole::class, 'write');
            expect($reflection->isPublic())->toBeTrue();
        });
    });

    describe('Basic Functionality', function() {
        it('write method can be called without throwing exception', function() {
            expect(fn() => $this->writer->write('Test message'))->not->toThrow(Exception::class);
        });

        it('accepts empty string without error', function() {
            expect(fn() => $this->writer->write(''))->not->toThrow(Exception::class);
        });

        it('accepts special characters without error', function() {
            expect(fn() => $this->writer->write('Special: @#$%^&*()'))->not->toThrow(Exception::class);
        });

        it('accepts unicode characters without error', function() {
            expect(fn() => $this->writer->write('Unicode: 你好 мир 🚀'))->not->toThrow(Exception::class);
        });

        it('accepts newlines without error', function() {
            expect(fn() => $this->writer->write("Line 1\nLine 2\nLine 3"))->not->toThrow(Exception::class);
        });
    });

    describe('Multiple Calls', function() {
        it('can be called multiple times sequentially', function() {
            expect(function() {
                $this->writer->write('First');
                $this->writer->write('Second');
                $this->writer->write('Third');
            })->not->toThrow(Exception::class);
        });

        it('handles rapid successive writes', function() {
            expect(function() {
                for ($i = 1; $i <= 100; $i++) {
                    $this->writer->write("Message {$i}");
                }
            })->not->toThrow(Exception::class);
        });
    });

    describe('Message Types', function() {
        it('handles formatted log messages', function() {
            $formattedLog = '[2024-11-01 12:34:56] INFO: User logged in {"user_id":123}';
            expect(fn() => $this->writer->write($formattedLog))->not->toThrow(Exception::class);
        });

        it('handles JSON formatted messages', function() {
            $jsonLog = '{"timestamp":"2024-11-01T12:34:56+00:00","level":"ERROR","message":"Failed"}';
            expect(fn() => $this->writer->write($jsonLog))->not->toThrow(Exception::class);
        });

        it('handles very long messages', function() {
            $message = str_repeat('A', 10000);
            expect(fn() => $this->writer->write($message))->not->toThrow(Exception::class);
        });
    });

    describe('Edge Cases', function() {
        it('handles quotes and apostrophes', function() {
            expect(fn() => $this->writer->write('Text with "quotes" and \'apostrophes\''))
                ->not->toThrow(Exception::class);
        });

        it('handles tabs', function() {
            expect(fn() => $this->writer->write("Tab\there\tand\tthere"))
                ->not->toThrow(Exception::class);
        });

        it('handles null bytes', function() {
            expect(fn() => $this->writer->write("Before\x00After"))
                ->not->toThrow(Exception::class);
        });

        it('handles backslashes', function() {
            expect(fn() => $this->writer->write('Path\\to\\file'))
                ->not->toThrow(Exception::class);
        });
    });
});
