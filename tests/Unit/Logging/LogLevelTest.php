<?php

use Lalaz\Logging\LogLevel;

describe('LogLevel', function () {
    it('defines all PSR-3 log level constants', function () {
        expect(LogLevel::EMERGENCY)->toBe('EMERGENCY');
        expect(LogLevel::ALERT)->toBe('ALERT');
        expect(LogLevel::CRITICAL)->toBe('CRITICAL');
        expect(LogLevel::ERROR)->toBe('ERROR');
        expect(LogLevel::WARNING)->toBe('WARNING');
        expect(LogLevel::NOTICE)->toBe('NOTICE');
        expect(LogLevel::INFO)->toBe('INFO');
        expect(LogLevel::DEBUG)->toBe('DEBUG');
    });

    it('returns correct numeric priorities for each level', function () {
        expect(LogLevel::getPriority('DEBUG'))->toBe(100);
        expect(LogLevel::getPriority('INFO'))->toBe(200);
        expect(LogLevel::getPriority('NOTICE'))->toBe(250);
        expect(LogLevel::getPriority('WARNING'))->toBe(300);
        expect(LogLevel::getPriority('ERROR'))->toBe(400);
        expect(LogLevel::getPriority('CRITICAL'))->toBe(500);
        expect(LogLevel::getPriority('ALERT'))->toBe(550);
        expect(LogLevel::getPriority('EMERGENCY'))->toBe(600);
    });

    it('handles case-insensitive level names', function () {
        expect(LogLevel::getPriority('error'))->toBe(400);
        expect(LogLevel::getPriority('ERROR'))->toBe(400);
        expect(LogLevel::getPriority('Error'))->toBe(400);
    });

    it('returns debug priority for unknown levels', function () {
        expect(LogLevel::getPriority('UNKNOWN'))->toBe(100);
        expect(LogLevel::getPriority('invalid'))->toBe(100);
    });

    it('correctly determines if message should be logged based on minimum level', function () {
        // DEBUG level (100) vs WARNING minimum (300)
        expect(LogLevel::shouldLog('DEBUG', 'WARNING'))->toBeFalse();
        expect(LogLevel::shouldLog('INFO', 'WARNING'))->toBeFalse();
        expect(LogLevel::shouldLog('NOTICE', 'WARNING'))->toBeFalse();

        // WARNING and above should log
        expect(LogLevel::shouldLog('WARNING', 'WARNING'))->toBeTrue();
        expect(LogLevel::shouldLog('ERROR', 'WARNING'))->toBeTrue();
        expect(LogLevel::shouldLog('CRITICAL', 'WARNING'))->toBeTrue();
        expect(LogLevel::shouldLog('ALERT', 'WARNING'))->toBeTrue();
        expect(LogLevel::shouldLog('EMERGENCY', 'WARNING'))->toBeTrue();
    });

    it('allows logging when message level equals minimum level', function () {
        expect(LogLevel::shouldLog('ERROR', 'ERROR'))->toBeTrue();
        expect(LogLevel::shouldLog('INFO', 'INFO'))->toBeTrue();
        expect(LogLevel::shouldLog('DEBUG', 'DEBUG'))->toBeTrue();
    });

    it('handles case-insensitive level comparison in shouldLog', function () {
        expect(LogLevel::shouldLog('error', 'WARNING'))->toBeTrue();
        expect(LogLevel::shouldLog('ERROR', 'warning'))->toBeTrue();
        expect(LogLevel::shouldLog('info', 'ERROR'))->toBeFalse();
    });

    it('returns all log levels ordered by severity', function () {
        $levels = LogLevel::all();

        expect($levels)->toBeArray();
        expect($levels)->toHaveCount(8);
        expect($levels[0])->toBe('DEBUG');
        expect($levels[1])->toBe('INFO');
        expect($levels[2])->toBe('NOTICE');
        expect($levels[3])->toBe('WARNING');
        expect($levels[4])->toBe('ERROR');
        expect($levels[5])->toBe('CRITICAL');
        expect($levels[6])->toBe('ALERT');
        expect($levels[7])->toBe('EMERGENCY');
    });

    it('maintains correct priority ordering', function () {
        $levels = LogLevel::all();

        for ($i = 0; $i < count($levels) - 1; $i++) {
            $currentPriority = LogLevel::getPriority($levels[$i]);
            $nextPriority = LogLevel::getPriority($levels[$i + 1]);

            expect($nextPriority)->toBeGreaterThan($currentPriority);
        }
    });
});
