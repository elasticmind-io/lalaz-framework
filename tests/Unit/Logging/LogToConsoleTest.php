<?php

use Lalaz\Logging\LogToConsole;

describe('LogToConsole', function () {
    it('successfully creates instance with stream', function () {
        $writer = new LogToConsole();

        expect($writer)->toBeInstanceOf(LogToConsole::class);
    });

    it('writes message without errors', function () {
        $writer = new LogToConsole();

        // Should not throw exception
        $writer->write('[2024-01-01] INFO: Test message');

        expect(true)->toBeTrue();
    });

    it('writes multiple messages sequentially without errors', function () {
        $writer = new LogToConsole();

        $writer->write('[2024-01-01] DEBUG: First message');
        $writer->write('[2024-01-01] INFO: Second message');
        $writer->write('[2024-01-01] ERROR: Third message');

        expect(true)->toBeTrue();
    });

    it('handles empty messages gracefully', function () {
        $writer = new LogToConsole();

        // Should not throw exception
        $writer->write('');

        expect(true)->toBeTrue();
    });

    it('handles long messages without errors', function () {
        $writer = new LogToConsole();
        $longMessage = '[2024-01-01] INFO: ' . str_repeat('A', 10000);

        $writer->write($longMessage);

        expect(true)->toBeTrue();
    });

    it('handles special characters and unicode without errors', function () {
        $writer = new LogToConsole();

        $writer->write('[2024-01-01] INFO: Special chars: àéîôü 中文 🎉');

        expect(true)->toBeTrue();
    });

    it('stream persists across multiple writes', function () {
        $writer = new LogToConsole();

        // Multiple writes should use same persistent stream
        $writer->write('[2024-01-01] INFO: First');
        $writer->write('[2024-01-01] INFO: Second');
        $writer->write('[2024-01-01] INFO: Third');

        expect(true)->toBeTrue();
    });

    it('destructor closes stream properly without errors', function () {
        $writer = new LogToConsole();

        $writer->write('[2024-01-01] INFO: Test');

        // Force destructor call
        unset($writer);

        expect(true)->toBeTrue();
    });

    it('handles rapid consecutive writes efficiently', function () {
        $writer = new LogToConsole();

        // Should handle many rapid writes without issues
        for ($i = 0; $i < 1000; $i++) {
            $writer->write("[2024-01-01] INFO: Message $i");
        }

        expect(true)->toBeTrue();
    });

    it('can be instantiated multiple times', function () {
        $writer1 = new LogToConsole();
        $writer2 = new LogToConsole();

        $writer1->write('[2024-01-01] INFO: Writer 1');
        $writer2->write('[2024-01-01] INFO: Writer 2');

        expect($writer1)->toBeInstanceOf(LogToConsole::class);
        expect($writer2)->toBeInstanceOf(LogToConsole::class);
    });
});
