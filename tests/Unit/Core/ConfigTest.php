<?php

use Lalaz\Core\Config;

describe('ConfigUnitTests', function() {
    beforeEach(function () {
        $_ENV = [];
        Config::clearCache();
    });

    it('loads environment variables from a file', function () {
        // Arrange
        $envFile = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($envFile, "DB_HOST=localhost\nDB_USER=root\nDB_PASSWORD=secret\n");

        // Act
        Config::load($envFile);

        // Assert
        expect($_ENV['DB_HOST'])->toBe('localhost');
        expect($_ENV['DB_USER'])->toBe('root');
        expect($_ENV['DB_PASSWORD'])->toBe('secret');

        unlink($envFile);
    });

    it('does not reload environment variables if already loaded', function () {
        // Arrange
        $_ENV['EXISTING_VAR'] = 'value1';

        // Act
        Config::load('/fake/path/to/env');

        // Assert
        expect($_ENV['EXISTING_VAR'])->toBe('value1');
    });

    it('returns null when environment variable key does not exist', function () {
        // Arrange
        Config::load('/fake/path/to/env');

        // Act
        $value = Config::get('NON_EXISTENT_VAR');

        // Assert
        expect($value)->toBeNull();
    });

    it('returns a typed value of an environment variable', function () {
        // Arrange
        $_ENV['INTEGER_VAR'] = '123';
        $_ENV['BOOLEAN_TRUE'] = 'true';
        $_ENV['BOOLEAN_FALSE'] = 'false';
        $_ENV['FLOAT_VAR'] = '12.34';

        // Act & Assert
        expect(Config::getTyped('INTEGER_VAR', null, 'int'))->toBeInt()->toBe(123);
        expect(Config::getTyped('BOOLEAN_TRUE', null, 'bool'))->toBeBool()->toBeTrue();
        expect(Config::getTyped('BOOLEAN_FALSE', null, 'bool'))->toBeBool()->toBeFalse();
        expect(Config::getTyped('FLOAT_VAR', null, 'float'))->toBeFloat()->toBe(12.34);
    });

    it('validates environment variable with a callback function', function () {
        // Arrange
        $_ENV['SECRET_KEY'] = '123456';

        // Act
        $isValid = Config::validate('SECRET_KEY', function ($value) {
            return strlen($value) === 6;
        });

        // Assert
        expect($isValid)->toBeTrue();
    });

    it('returns all loaded environment variables', function () {
        // Arrange
        $_ENV['APP_NAME'] = 'MyApp';
        $_ENV['APP_VERSION'] = '1.0.0';

        // Act
        $variables = Config::all();

        // Assert
        expect($variables)->toMatchArray([
            'APP_NAME' => 'MyApp',
            'APP_VERSION' => '1.0.0'
        ]);
    });

    it('checks the current environment with isEnv()', function () {
        // Arrange
        $_ENV['ENV'] = 'production';

        // Act & Assert
        expect(Config::isEnv('production'))->toBeTrue();
        expect(Config::isEnv('development'))->toBeFalse();
    });

    it('checks if the environment is development', function () {
        // Arrange
        $_ENV['ENV'] = 'development';

        // Act & Assert
        expect(Config::isDevelopment())->toBeTrue();
    });

    it('checks if the environment is debug', function () {
        // Arrange
        $_ENV['ENV'] = 'debug';

        // Act & Assert
        expect(Config::isDebug())->toBeTrue();
    });

    it('checks if the environment is production', function () {
        // Arrange
        $_ENV['ENV'] = 'production';

        // Act & Assert
        expect(Config::isProduction())->toBeTrue();
    });
});
