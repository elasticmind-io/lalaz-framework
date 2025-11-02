<?php

use Lalaz\Core\LazyValue;
use Lalaz\View\ViewContext;

beforeEach(function () {
    ViewContext::reset();
});

afterEach(function () {
    ViewContext::reset();
});

describe('ViewContext', function () {
    describe('set and get', function () {
        it('stores and retrieves simple values', function () {
            ViewContext::set('key', 'value');
            expect(ViewContext::get('key'))->toBe('value');
        });

        it('stores multiple values independently', function () {
            ViewContext::set('name', 'John');
            ViewContext::set('age', 30);
            ViewContext::set('active', true);

            expect(ViewContext::get('name'))->toBe('John');
            expect(ViewContext::get('age'))->toBe(30);
            expect(ViewContext::get('active'))->toBeTrue();
        });

        it('overwrites existing values', function () {
            ViewContext::set('key', 'first');
            expect(ViewContext::get('key'))->toBe('first');

            ViewContext::set('key', 'second');
            expect(ViewContext::get('key'))->toBe('second');
        });

        it('returns null for non-existent keys', function () {
            expect(ViewContext::get('nonexistent'))->toBeNull();
        });

        it('returns default value for non-existent keys', function () {
            expect(ViewContext::get('missing', 'default'))->toBe('default');
        });

        it('returns actual value instead of default when key exists', function () {
            ViewContext::set('key', 'actual');
            expect(ViewContext::get('key', 'default'))->toBe('actual');
        });

        it('handles null values explicitly set', function () {
            ViewContext::set('nullable', null);
            expect(ViewContext::get('nullable', 'default'))->toBeNull();
        });
    });

    describe('callable handling', function () {
        it('executes callable values on get', function () {
            ViewContext::set('lazy', fn() => 'computed');
            $value = ViewContext::get('lazy');

            expect($value)->toBe('computed');
        });

        it('executes callables automatically on get', function () {
            $executed = false;
            ViewContext::set('lazy', function() use (&$executed) {
                $executed = true;
                return 'result';
            });

            ViewContext::get('lazy');
            expect($executed)->toBeTrue();
        });

        it('stores different types of callables', function () {
            ViewContext::set('closure', fn() => 'closure');
            ViewContext::set('arrow', fn() => 'arrow');
            ViewContext::set('callable_array', [new class {
                public function method() { return 'method'; }
            }, 'method']);

            expect(ViewContext::get('closure'))->toBe('closure');
            expect(ViewContext::get('arrow'))->toBe('arrow');
            expect(ViewContext::get('callable_array'))->toBe('method');
        });
    });

    describe('resolved', function () {
        it('returns non-callable values as is', function () {
            ViewContext::set('simple', 'value');
            $resolved = ViewContext::resolved();

            expect($resolved['simple'])->toBe('value');
        });

        it('wraps callable values in LazyValue', function () {
            ViewContext::set('lazy', fn() => 'computed');
            $resolved = ViewContext::resolved();

            expect($resolved['lazy'])->toBeInstanceOf(LazyValue::class);
        });

        it('executes LazyValue when accessed via __call', function () {
            ViewContext::set('lazy', fn() => (object)['value' => 'computed']);
            $resolved = ViewContext::resolved();

            // LazyValue uses __call to execute methods on the resolved value
            $lazyValue = $resolved['lazy'];
            $reflection = new ReflectionClass($lazyValue);
            $method = $reflection->getMethod('getValue');
            $method->setAccessible(true);
            $result = $method->invoke($lazyValue);

            expect($result)->toBeObject();
            expect($result->value)->toBe('computed');
        });

        it('returns all context values', function () {
            ViewContext::set('name', 'John');
            ViewContext::set('age', 30);
            ViewContext::set('lazy', fn() => 'lazy');

            $resolved = ViewContext::resolved();

            expect($resolved)->toHaveKey('name', 'John');
            expect($resolved)->toHaveKey('age', 30);
            expect($resolved)->toHaveKey('lazy');
        });

        it('handles mixed callable and non-callable values', function () {
            ViewContext::set('static', 'value');
            ViewContext::set('dynamic', fn() => (object)['data' => 'computed']);

            $resolved = ViewContext::resolved();

            expect($resolved['static'])->toBe('value');
            expect($resolved['dynamic'])->toBeInstanceOf(LazyValue::class);
            // Access property via LazyValue's __get
            expect($resolved['dynamic']->data)->toBe('computed');
        });

        it('preserves complex data structures', function () {
            ViewContext::set('user', [
                'name' => 'John',
                'profile' => [
                    'age' => 30,
                    'settings' => ['theme' => 'dark']
                ]
            ]);

            $resolved = ViewContext::resolved();

            expect($resolved['user']['profile']['settings']['theme'])->toBe('dark');
        });

        it('returns empty array when no context set', function () {
            $resolved = ViewContext::resolved();
            expect($resolved)->toBe([]);
        });
    });

    describe('reset', function () {
        it('clears all stored values', function () {
            ViewContext::set('key1', 'value1');
            ViewContext::set('key2', 'value2');

            ViewContext::reset();

            expect(ViewContext::get('key1'))->toBeNull();
            expect(ViewContext::get('key2'))->toBeNull();
        });

        it('returns empty array after reset', function () {
            ViewContext::set('key', 'value');
            ViewContext::reset();

            $resolved = ViewContext::resolved();
            expect($resolved)->toBe([]);
        });

        it('allows setting values after reset', function () {
            ViewContext::set('old', 'value');
            ViewContext::reset();
            ViewContext::set('new', 'value');

            expect(ViewContext::get('old'))->toBeNull();
            expect(ViewContext::get('new'))->toBe('value');
        });

        it('can be called multiple times safely', function () {
            ViewContext::set('key', 'value');
            ViewContext::reset();
            ViewContext::reset();
            ViewContext::reset();

            expect(ViewContext::resolved())->toBe([]);
        });
    });

    describe('data types', function () {
        it('handles string values', function () {
            ViewContext::set('testString', 'testValue');
            expect(ViewContext::get('testString'))->toEqual('testValue');
        });

        it('handles integer values', function () {
            ViewContext::set('int', 42);
            expect(ViewContext::get('int'))->toBe(42);
        });

        it('handles float values', function () {
            ViewContext::set('float', 3.14);
            expect(ViewContext::get('float'))->toBe(3.14);
        });

        it('handles boolean values', function () {
            ViewContext::set('true', true);
            ViewContext::set('false', false);

            expect(ViewContext::get('true'))->toBeTrue();
            expect(ViewContext::get('false'))->toBeFalse();
        });

        it('handles array values', function () {
            ViewContext::set('array', [1, 2, 3]);
            expect(ViewContext::get('array'))->toBe([1, 2, 3]);
        });

        it('handles object values', function () {
            $obj = new stdClass();
            $obj->prop = 'value';

            ViewContext::set('object', $obj);
            expect(ViewContext::get('object'))->toBe($obj);
        });
    });

    describe('edge cases', function () {
        it('handles empty string keys', function () {
            ViewContext::set('', 'value');
            expect(ViewContext::get(''))->toBe('value');
        });

        it('handles numeric string keys', function () {
            ViewContext::set('123', 'numeric');
            expect(ViewContext::get('123'))->toBe('numeric');
        });

        it('handles keys with special characters', function () {
            ViewContext::set('key-with-dashes', 'value1');
            ViewContext::set('key_with_underscores', 'value2');
            ViewContext::set('key.with.dots', 'value3');

            expect(ViewContext::get('key-with-dashes'))->toBe('value1');
            expect(ViewContext::get('key_with_underscores'))->toBe('value2');
            expect(ViewContext::get('key.with.dots'))->toBe('value3');
        });

        it('handles large arrays', function () {
            $largeArray = array_fill(0, 1000, 'item');
            ViewContext::set('large', $largeArray);

            expect(ViewContext::get('large'))->toHaveCount(1000);
        });

        it('handles deeply nested structures', function () {
            $deep = ['a' => ['b' => ['c' => ['d' => 'deep']]]];
            ViewContext::set('nested', $deep);

            expect(ViewContext::get('nested')['a']['b']['c']['d'])->toBe('deep');
        });

        it('handles callable that returns null', function () {
            ViewContext::set('nullable', fn() => null);

            // get() executes the callable
            expect(ViewContext::get('nullable'))->toBeNull();
        });

        it('handles callable that returns array', function () {
            ViewContext::set('array_func', fn() => [1, 2, 3]);

            // get() executes the callable
            expect(ViewContext::get('array_func'))->toBe([1, 2, 3]);
        });

        it('handles callable that returns callable', function () {
            ViewContext::set('nested_func', fn() => fn() => 'deep');

            // get() executes the first callable, returns another callable
            $result = ViewContext::get('nested_func');
            expect($result)->toBeCallable();
            expect($result())->toBe('deep');
        });
    });

    describe('static behavior', function () {
        it('maintains state across different method calls', function () {
            ViewContext::set('key1', 'value1');
            ViewContext::set('key2', 'value2');

            $resolved = ViewContext::resolved();

            expect($resolved)->toHaveKey('key1');
            expect($resolved)->toHaveKey('key2');
        });

        it('persists data until reset is called', function () {
            ViewContext::set('persistent', 'value');

            expect(ViewContext::get('persistent'))->toBe('value');
            $resolved1 = ViewContext::resolved();
            $resolved2 = ViewContext::resolved();

            expect($resolved1['persistent'])->toBe('value');
            expect($resolved2['persistent'])->toBe('value');

            ViewContext::reset();
            expect(ViewContext::get('persistent'))->toBeNull();
        });
    });
});
