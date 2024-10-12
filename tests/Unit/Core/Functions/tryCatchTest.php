<?php

require_once './src/Core/Functions/tryCatch.php';

describe('TryCatchUnitTests', function() {
    it('executes the try block successfully without exception', function () {
        // Arrange & Act
        $result = tryCatch(fn() => 'success', []);

        // Assert
        expect($result[0])->toBe('success');
        expect($result[1])->toBeNull();
    });
});
